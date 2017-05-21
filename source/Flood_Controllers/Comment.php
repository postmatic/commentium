<?php
namespace Postmatic\Commentium\Flood_Controllers;

use Postmatic\Commentium\Lists;
use Postmatic\Commentium\Repositories;
use Postmatic\Commentium\Models;
use Prompt_Comment_Flood_Controller;
use Prompt_Site_Comments;
use Prompt_Core;
use Prompt_Options;

/**
 * Comment digest flood control
 * @since 0.4.0
 */
class Comment extends Prompt_Comment_Flood_Controller {

	/**
	 * @since 0.4.0
	 * @var Repositories\Scheduled_Callback_HTTP
	 */
	protected $callback_repo;
    /**
     * @since 1.0.1
     * @var Prompt_Options
     */
	protected $options;

	/**
	 * @since 0.4.0
     * @since 1.0.1 Added options
	 * @param $comment
	 * @param Repositories\Scheduled_Callback_HTTP|null $callback_repo
     * @param Prompt_Options $options
	 */
	public function __construct(
	    $comment,
        Repositories\Scheduled_Callback_HTTP $callback_repo = null,
        Prompt_Options $options = null
    ) {

		parent::__construct( $comment );

		$this->prompt_post = new Lists\Posts\Post( $this->prompt_post->id() );
		$this->callback_repo = $callback_repo ?: new Repositories\Scheduled_Callback_HTTP();
		$this->options = $options ?: Prompt_Core::$options;
	}

	/**
	 * Recipient IDs using comment digest flood control.
	 *
	 * Once flood control is triggered, schedules a comment digest for most subscribers instead of a comment notice.
	 * The post author and site-wide comment subscribers are exceptions and may still get a single comment notice.
	 *
	 * @since 0.4.0
	 * @return array
	 */
	public function control_recipient_ids() {

		if ( $this->is_flood() ) {
			$this->prompt_post->set_flood_control_comment_id( $this->comment->comment_ID );
		}

		if ( !$this->prompt_post->get_flood_control_comment_id() ) {
			return parent::control_recipient_ids();
		}

		if ( !$this->options->get( 'enable_replies_only' ) ) {
            $this->ensure_comment_digest_schedule();
        }

		return $this->all_ids_except( $this->get_author_id( $this->comment ), $this->single_notice_recipient_ids() );
	}

	/**
	 * @since 0.4.0
	 */
	protected function ensure_comment_digest_schedule() {

		$callback_id = $this->prompt_post->get_comment_digest_callback_id();

		if ( $callback_id ) {
			return;
		}

		$callback = new Models\Scheduled_Callback( array(
			'start_timestamp' => strtotime( '+24 hours' ),
			'metadata' => array(
				'postmatic/premium/mailers/comment_digest/initiate',
				array( $this->prompt_post->id() )
			),
		) );

		$callback_id = $this->callback_repo->save( $callback );

		if ( !is_wp_error( $callback_id ) ) {
			$this->prompt_post->set_comment_digest_callback_id( $callback_id );
		}
	}

	/**
	 * @since 0.4.0
	 * @param object|\WP_Comment $comment
	 * @return null|int
	 */
	protected function get_author_id( $comment ) {

		$comment_author_id = $comment->user_id;
		if ( !$comment_author_id ) {
			$author = get_user_by( 'email', $comment->comment_author_email );
			$comment_author_id = $author ? $author->ID : null;
		}

		return $comment_author_id;
	}

	/**
	 * @since 0.4.0
	 * @return int|null
	 */
	protected function parent_comment_author_id() {

		if ( !$this->comment->comment_parent ) {
			return null;
		}

		$parent_comment = get_comment( $this->comment->comment_parent );

		if ( !$parent_comment ) {
			return null;
		}

		return $this->get_author_id( $parent_comment );
	}

	/**
	 * @since 0.4.0
	 * @return array
	 */
	protected function single_notice_recipient_ids() {

		$site_comments = new Prompt_Site_Comments();
		$recipient_ids = $site_comments->subscriber_ids();

		if ( $this->options->get( 'auto_subscribe_authors' ) ) {
			$recipient_ids[] = $this->prompt_post->get_wp_post()->post_author;
		}

		$parent_comment_author_id = $this->parent_comment_author_id();
		if ( $parent_comment_author_id and $this->prompt_post->is_subscribed( $parent_comment_author_id ) ) {
			$recipient_ids[] = $parent_comment_author_id;
		}

		return $recipient_ids;
	}
}