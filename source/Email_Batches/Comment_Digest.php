<?php
namespace Postmatic\Commentium\Email_Batches;

use Postmatic\Commentium\Lists;
use Postmatic\Commentium\Templates;
use Prompt_Email_Batch;
use Prompt_Enum_Message_Types;
use Prompt_Enum_Content_Types;
use Prompt_Unsubscribe_Matcher;
use Prompt_Comment_Command;
use Prompt_Command_Handling;
use WP_User;

/**
 * An email batch that knows how to render a comment digest for a post.
 * @since 0.4.0
 */
class Comment_Digest extends Prompt_Email_Batch {

	/**
	 * @since 0.4.0
	 * @var Lists\Posts\Post
	 */
	protected $post_list;
	/**
	 * @since 0.4.0
	 * @var array
	 */
	protected $comments;
	/**
	 * @since 0.4.0
	 * @var array
	 */
	protected $parent_comments;
	/**
	 * @since 0.4.0
	 * @var string
	 */
	protected $original_digested_date_gmt;

	/**
	 * @since 0.4.0
	 * @param Lists\Posts\Post $post_list
	 */
	public function __construct( Lists\Posts\Post $post_list ) {

		$this->post_list = $post_list;

		$post_title = $post_list->get_wp_post()->post_title;
		
		$post_title_link = sprintf( 
			'<a href="%s">%s</a>',
			get_permalink( $post_list->id() ),
			$post_title
		);
		
		$post_author = get_userdata( $post_list->get_wp_post()->post_author );
		$post_author_name = $post_author ? $post_author->display_name : __( 'Anonymous', 'Postmatic' );
		
		$comments = get_comments( array(
			'post_id' => $post_list->id(),
			'date_query' => array( $post_list->undigested_comments_date_clauses() ),
			'status' => 'approve',
			'order' => 'ASC',
		) );
		
		$this->comments = $comments;

		$this->parent_comments = $this->get_parent_comments( $comments );

		\Prompt_Email_Comment_Rendering::classify_comments( $this->comments, 'featured' );
		\Prompt_Email_Comment_Rendering::classify_comments( $this->parent_comments, 'contextual' );

		$template_data = array( 
			'post_list' => $post_list, 
			'comments' => $comments,
			'parent_comments' => $this->parent_comments,
			'new_comment_count' => count( $comments ),
			'subscribed_post_title' => $post_title,
			'subscribed_post_title_link' => $post_title_link,
			'subscribed_post_author_name' => $post_author_name,
		);

		$html_template = new Templates\HTML( "comment-digest-email.php" );
		$text_template = new Templates\Text( "comment-digest-email-text.php" );

		/* translators: first %s is post title, second is blog name */
		$subject = html_entity_decode(
			sprintf( 
				__( 'Daily comment digest for %s from %s', 'postmatic-premium' ), 
				$post_list->get_wp_post()->post_title,
				get_bloginfo( 'name' )
			)
		);

		/* translators: %1$s is a subscription list title, %2$s the unsubscribe command */
		$footnote_format = __(
			'You received this email because you\'re subscribed to %1$s. To no longer receive other comments or replies in this discussion reply with the word \'%2$s\'.',
			'Postmatic'
		);

		$batch_message_template = array(
			'subject' => $subject,
			'from_name' => html_entity_decode( get_option( 'blogname' ) ),
			'text_content' => $text_template->render( $template_data ),
			'html_content' => $html_template->render( $template_data ),
			'reply_to' => '{{{reply_to}}}',
			'message_type' => Prompt_Enum_Message_Types::COMMENT,
			'footnote_html' => sprintf(
				$footnote_format,
				$this->post_list->subscription_object_label(),
				"<a href=\"{$this->unsubscribe_mailto()}\">" . Prompt_Unsubscribe_Matcher::target() . "</a>"
			),
			'footnote_text' => sprintf(
				$footnote_format,
				$this->post_list->subscription_object_label( Prompt_Enum_Content_Types::TEXT ),
				Prompt_Unsubscribe_Matcher::target()
			),
		);

		parent::__construct( $batch_message_template );
	}

	/**
	 * The post list this batch is based on.
	 * @since 0.4.0
	 * @return Lists\Posts\Post
	 */
	public function get_post_list() {
		return $this->post_list;
	}
	
	/**
	 * The comments in this digest.
	 * @since 0.4.0
	 * @return array
	 */
	public function get_comments() {
		return $this->comments;
	}

	/**
	 * Generate message values for each post subscriber.
	 * @since 0.4.0
	 * @return $this;
	 */
	public function compile_recipients() {

		$this->set_individual_message_values( array() );

		$recipient_ids = $this->post_list->subscriber_ids();

		foreach ( $recipient_ids as $recipient_id ) {
			$recipient = get_userdata( $recipient_id );

			if ( !$recipient or !$recipient->user_email )
				continue;

			$this->add_recipient( $recipient );
		}
		
		return $this;
	}

	/**
	 * Record current comment digest date.
	 * 
	 * Should make it so each time we lock for sending the next digest will include only newer comments.
	 *
	 * @since 0.4.0
	 *
	 * @return $this;
	 */
	public function lock_for_sending() {
		
		$this->original_digested_date_gmt = $this->post_list->get_digested_comments_date_gmt();
		
		$this->post_list->set_digested_comments_date_gmt( get_gmt_from_date( 'now' ) );
		
		return $this;
	}

	/**
	 * Reset the current commment digest date so a mailing for this batch can be retried.
	 * 
	 * @since 0.4.0
	 *
	 * @return $this;
	 */
	public function clear_for_retry() {
		
		if ( $this->original_digested_date_gmt ) {
			$this->post_list->set_digested_comments_date_gmt( $this->original_digested_date_gmt );
		}
		
		return $this;
	}

	/**
	 * Add recipient-specific values for an email.
	 *
	 * @since 0.4.0
	 *
	 * @param WP_User $recipient
	 * @return $this
	 */
	protected function add_recipient( WP_User $recipient ) {

		$command = new Prompt_Comment_Command();
		$command->set_post_id( $this->post_list->id() );
		$command->set_user_id( $recipient->ID );

		$values = array(
			'id' => $recipient->ID,
			'to_name' => $recipient->display_name,
			'to_address' => $recipient->user_email,
			'reply_to' => $this->trackable_address( Prompt_Command_Handling::get_command_metadata( $command ) ),
		);

		$values = array_merge(
			$values,
			Prompt_Command_Handling::get_comment_reply_macros( $this->parent_comments, $recipient->ID ),
			Prompt_Command_Handling::get_comment_reply_macros( $this->comments, $recipient->ID )
		);

		return $this->add_individual_message_values( $values );
	}

	/**
	 * @since 0.4.0
	 * @param array $comments
	 * @return array
	 */
	protected function get_parent_comments( array $comments ) {

		$parent_comments = array();

		$comment_ids = wp_list_pluck( $comments, 'comment_ID' );

		foreach ( $comments as $comment ) {
			$parent_comments = array_merge( $parent_comments, $this->get_missing_thread_comments( $comment, $comment_ids ) );
		}

		return $parent_comments;
	}

	/**
	 * @since 0.4.0
	 * @param object|\\WP_Comment $comment
	 * @param array $present_comment_ids
	 * @return array
	 */
	protected function get_missing_thread_comments( $comment, $present_comment_ids ) {

		$thread_comments = array();

		if ( !$comment->comment_parent ) {
			return $thread_comments;
		}

		do {
			$comment = get_comment( $comment->comment_parent );
			if ( !in_array( $comment->comment_ID, $present_comment_ids ) ) {
				$thread_comments[] = $comment;
			}
		} while( $comment->comment_parent );

		return $thread_comments;
	}
}