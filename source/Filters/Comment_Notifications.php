<?php
namespace Postmatic\Commentium\Filters;

use Prompt_Core;

/**
 * Cancel comment notifications if needed
 * @since 0.5.0
 */
class Comment_Notifications {

	/**
	 * Check snob notifications and allow/cancel comments
	 *
	 * @since 0.5.0
	 * @param boolean $allow
	 * @param string $comment_id
	 * @return boolean
	 */
	public static function allow( $allow, $comment_id ) {

		// Is snob enabled?
		if ( ! Prompt_Core::$options->get( 'comment_snob_notifications' ) ) {
			return $allow;
		}

		// Get comment meta
		$meta = get_comment_meta( $comment_id, 'commentiq_comment_details', true );
		if ( empty( $meta ) ) {
			return $allow;
		}

		// Criteria for snob
		if ( $meta['ArticleRelevance'] < 0.25 or $meta['Length'] < 30 ) {
			$allow = false;
		}

		return $allow;
	}
}
