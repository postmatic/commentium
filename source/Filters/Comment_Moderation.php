<?php
namespace Postmatic\Commentium\Filters;

use Postmatic\Commentium\Email_Batches;
use Prompt_Core;
use Prompt_Enum_Message_Types;
use Prompt_Factory;

/**
 * Override native comment moderation.
 * @since 0.1.0
 */
class Comment_Moderation {

	/**
	 * Override native comment moderation notifications.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/comment_moderation_recipients/
	 *
	 * @param array $addresses
	 * @param int $comment_id
	 * @return array Empty array to short circuit native notifications.
	 */
	public static function recipients( $addresses, $comment_id ) {

		$enabled_message_types = Prompt_Core::$options->get( 'enabled_message_types' );

		if ( ! in_array( Prompt_Enum_Message_Types::COMMENT_MODERATION, $enabled_message_types ) ) {
			return $addresses;
		}

		$comment = get_comment( $comment_id );

		// Auto-approve comments from moderators
		if ( in_array( $comment->comment_author_email, $addresses ) ) {
			wp_set_comment_status( $comment->comment_ID, 'approve' );
			return array();
		}

		$batch = new Email_Batches\Comment_Moderation( $comment );

		$batch->add_recipient_addresses( $addresses );

		Prompt_Factory::make_mailer( $batch )->send();

		return array();
	}

	/**
	 * When a comment is unapproved, notify moderators for API user.
	 *
	 * This is inspired by the Crowd Control plugin, to let moderators know when the crowd has unapproved a comment.
	 *
	 * @since 2.0.0
	 *
	 * @param object $comment
	 */
	public static function approved_to_unapproved( $comment ) {

		if ( current_user_can( 'moderate_comments' ) ) {
			return;
		}

		$enabled_message_types = Prompt_Core::$options->get( 'enabled_message_types' );

		if ( !in_array( Prompt_Enum_Message_Types::COMMENT_MODERATION, $enabled_message_types ) ) {
			return;
		}

		wp_notify_moderator( $comment->comment_ID );
	}

}