<?php
namespace Postmatic\Commentium\Commands;

use Prompt_Interface_Command;
use Prompt_Logging;
use Prompt_Comment_Mailing;

/**
 * Command to handle comment moderation.
 * @since 0.1.0
 */
class Comment_Moderation implements Prompt_Interface_Command {

	/**
	 * @since 0.1.0
	 * @var string
	 */
	protected static $approve_method = 'approve';
	/**
	 * @since 0.1.0
	 * @var string
	 */
	protected static $spam_method = 'spam';
	/**
	 * @since 0.1.0
	 * @var string
	 */
	protected static $trash_method = 'trash';

	/** @var array */
	protected $keys = array( 0 );
	/** @var  int */
	protected $comment_id;
	/** @var  int */
	protected $moderator_id;
	/** @var  object */
	protected $message;
	/** @var  string */
	protected $message_text;

	/**
	 * @since 0.1.0
	 * @param $keys
	 */
	public function set_keys( $keys ) {
		$this->keys = $keys;
	}

	/**
	 * @since 0.1.0
	 * @return array
	 */
	public function get_keys() {
		return $this->keys;
	}

	/**
	 * @since 0.1.0
	 * @param $message
	 */
	public function set_message( $message ) {
		$this->message = $message;
	}

	/**
	 * @since 0.1.0
	 * @return object
	 */
	public function get_message() {
		return $this->message;
	}

	/**
	 * @since 0.1.0
	 */
	public function execute() {

		if ( !$this->validate() )
			return;

		$text_command = $this->get_text_command();
		if ( $text_command ) {
			$this->$text_command();
			return;
		}

		// Approve this comment AND add a new one
		$this->approve();
		$this->add_comment();
	}

	/**
	 * @since 0.1.0
	 * @param $id
	 */
	public function set_comment_id( $id ) {
		$this->comment_id = intval( $id );
		$this->keys[0] = $this->comment_id;
	}

	/**
	 * @since 0.1.0
	 * @param $id
	 */
	public function set_moderator_id( $id ) {
		$this->moderator_id = intval( $id );
		$this->keys[1] = $this->moderator_id;
	}

	/**
	 * @since 2.0.9
	 * @return bool
	 */
	public function moderator_is_capable() {
		return user_can( $this->moderator_id, 'moderate_comments' ) or
			user_can( $this->moderator_id, 'edit_comment', $this->comment_id );
	}

	/**
	 * @since 0.1.0
	 * @return bool
	 */
	protected function validate() {

		if ( !is_array( $this->keys ) or count( $this->keys ) != 2 ) {
			Prompt_Logging::add_error(
				'invalid_comment_moderation_keys',
				__( 'Received a comment moderation command with invalid keys.', 'Postmatic' ),
				array( 'keys' => $this->keys )
			);
			return false;
		}

		$this->comment_id = intval( $this->keys[0] );
		$this->moderator_id = intval( $this->keys[1] );

		if ( ! $this->moderator_is_capable() ) {
			Prompt_Logging::add_error(
				'moderator_capability_error',
				__(
					'Received a comment moderation command from a user with insufficient capabilities.',
					'Postmatic'
				),
				array( 'keys' => $this->keys )
			);
			return false;
		}

		return true;
	}

	/**
	 * @since 0.1.0
	 * @return string
	 */
	protected function get_message_text() {
		if ( !$this->message_text ) {
			$this->message_text = $this->message->message;
		}

		return $this->message_text;
	}

	/**
	 * Get text command from the message, if any.
	 *
	 * A blank message is treated as a publish command.
	 *
	 * @return string Text command if found, otherwise empty.
	 */
	protected function get_text_command() {

		$message_text = $this->get_message_text();

		if ( preg_match( '/^\s*$/i', $message_text, $matches ) )
			return self::$approve_method;

		/* translators: this is the response used to approve a moderated comment */
		$approve_command = preg_quote( __( 'approve', 'Postmatic' ), '/' );

		if ( preg_match( "/^\\s*$approve_command\\s*$/i", $message_text ) )
			return self::$approve_method;

		/* translators: this is the response used to flag a moderated comment as spam */
		$spam_command = preg_quote( __( 'spam', 'Postmatic' ), '/' );

		if ( preg_match( "/\\s*$spam_command\\s*$/i", $message_text ) )
			return self::$spam_method;

		/* translators: this is the response used to put a moderated comment in the trash */
		$trash_command = preg_quote( __( 'trash', 'Postmatic' ), '/' );

		if ( preg_match( "/\\s*$trash_command\\s*$/i", $message_text ) )
			return self::$trash_method;

		// English misspellings
		if ( preg_match( '/^\s*(ap[pr]..ve|ap..ve)\s*$/i', $message_text, $matches ) )
			return self::$approve_method;

		if ( preg_match( '/^\s*(p.[bp]..[sc]h|p.b..[sh]|p.b..hs|p.bls.h)\s*$/i', $message_text, $matches ) )
			return self::$approve_method;

		if ( preg_match( '/^\s*(sp[am]m?m?|sam)\s*$/i', $message_text, $matches ) )
			return self::$spam_method;

		if ( preg_match( '/^\s*(tr..[sc]h|tr.[hsc][hs]|t[ar][ars][hs])\s*$/i', $message_text, $matches ) )
			return self::$trash_method;

		return '';
	}

	/**
	 * @since 0.1.0
	 */
	protected function approve() {

		wp_set_comment_status( $this->comment_id, 'approve' );

	}

	/**
	 * @since 0.1.0
	 */
	protected function spam() {

		wp_spam_comment( $this->comment_id );

	}

	/**
	 * @since 0.1.0
	 */
	protected function trash() {

		wp_trash_comment( $this->comment_id );

	}

	/**
	 * @since 0.1.0
	 */
	protected function add_comment() {

		$text = $this->get_message_text();

		$parent_comment = get_comment( $this->comment_id );

		$post_id = $parent_comment->comment_post_ID;

		$post = get_post( $post_id );

		if ( !$post ) {
			trigger_error(
				sprintf( __( 'rejected comment on unqualified post %s', 'Postmatic' ), $post_id ),
				E_USER_NOTICE
			);
			Prompt_Comment_Mailing::send_rejected_notification( $this->moderator_id, $post_id );
			return;
		}

		$user = get_userdata( $this->moderator_id );
		$comment_data = array(
			'user_id' => $user->ID,
			'comment_post_ID' => $post_id,
			'comment_content' => $text,
			'comment_agent' => __CLASS__,
			'comment_author' => $user->display_name,
			'comment_author_IP' => '',
			'comment_author_url' => $user->user_url,
			'comment_author_email' => $user->user_email,
			'comment_parent' => $parent_comment->comment_ID,
			'comment_approved' => 1,
		);

		$comment_data = apply_filters( 'prompt_preprocess_comment', $comment_data );
		$comment_data = wp_filter_comment( $comment_data );

		wp_insert_comment( $comment_data );
	}
}
