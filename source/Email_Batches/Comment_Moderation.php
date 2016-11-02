<?php
namespace Postmatic\Commentium\Email_Batches;

use Postmatic\Commentium\Templates;
use Postmatic\Commentium\Commands;
use Prompt_Email_Batch;
use Prompt_Enum_Message_Types;
use Prompt_Content_Handling;
use Prompt_Command_Handling;

use WP_Comment;
use WP_Post;
/**
 * Email batch that knows how to render a comment moderation email.
 * @since 0.1.0
 */
class Comment_Moderation extends Prompt_Email_Batch {

	/** @var WP_Comment */
	protected $comment;

	/**
	 * @since 0.1.0
	 * @param int|WP_Comment $comment_id_or_object
	 */
	public function __construct( $comment_id_or_object ) {

		$this->comment = $comment = get_comment( $comment_id_or_object );

		$type = empty( $comment->comment_type ) ? 'comment' : $comment->comment_type;

		$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);

		$comment_author = get_user_by( 'email', $comment->comment_author_email );
		$commenter_name = $comment_author ? $comment_author->display_name : $comment->comment_author;
		$commenter_name = $commenter_name ? $commenter_name : __( 'Anonymous' );

		$post = get_post( $comment->comment_post_ID );

		$subject = sprintf( __( 'Please moderate "%s"', 'postmatic-premium' ), $post->post_title );

		$comment_header = true;

		$template_data = compact(
			'comment',
			'type',
			'post',
			'comment_author_domain',
			'commenter_name',
			'subject',
			'comment_header'
		);

		/**
		 * Filter comment moderation email template data.
		 *
		 * @param array $template_data {
		 * @type object $comment
		 * @type string $type 'comment', 'pingback', 'trackback', etc.
		 * @type WP_Post $post
		 * @type string $comment_author_domain
		 * @type string $commenter_name
		 * @type string $subject
		 * @type bool $comment_header
		 * }
		 */
		$template_data = apply_filters( 'prompt/comment_moderation_email/template_data', $template_data );

		$html_template = new Templates\HTML( 'comment-moderation-email.php' );
		$text_template = new Templates\Text( 'comment-moderation-email-text.php' );

		$footnote_html = sprintf(
			__(
				'You received this email because you are the author of %1$s. To turn off Postmatic comment moderation please <a href="%2$s">visit your discussion settings in WordPress</a>.',
				'postmatic-premium'
			),
			$post->post_title,
			admin_url( 'options-discussion.php' )
		);

		parent::__construct( array(
			'subject' => $subject,
			'text_content' => $text_template->render( $template_data ),
			'html_content' => $html_template->render( $template_data ),
			'message_type' => Prompt_Enum_Message_Types::COMMENT_MODERATION,
			'reply_to' => '{{{reply_to}}}',
			'footnote_html' => $footnote_html,
			'footnote_text' => Prompt_Content_Handling::reduce_html_to_utf8( $footnote_html ),
		) );
	}

	/**
	 * Add multiple recipients by email address.
	 * @since 0.1.0
	 * @param array $addresses
	 */
	public function add_recipient_addresses( $addresses ) {

		foreach ( $addresses as $recipient_address ) {

			$moderator = get_user_by( 'email', $recipient_address );

			if ( !$moderator ) {
				continue;
			}

			$command = new Commands\Comment_Moderation();
			$command->set_comment_id( $this->comment->comment_ID );
			$command->set_moderator_id( $moderator->ID );

			if ( ! $command->moderator_is_capable() ) {
				continue;
			}

			$this->add_individual_message_values( array(
				'to_address' => $recipient_address,
				'reply_to' => Prompt_Email_Batch::trackable_address(
					Prompt_Command_Handling::get_command_metadata( $command )
				),
			) );
		}
	}
}