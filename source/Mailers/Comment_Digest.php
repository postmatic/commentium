<?php
namespace Postmatic\Commentium\Mailers;

use Postmatic\Commentium\Lists;
use Postmatic\Commentium\Email_Batches;
use Postmatic\Commentium\Repositories;
use Prompt_Mailer;
use Prompt_Logging;
use Prompt_Enum_Error_Codes;
use Prompt_Api_Client;

/**
 * Manage sending a digest.
 * @since 0.1.0
 */
class Comment_Digest extends Prompt_Mailer {

	/** @var  Email_Batches\Comment_Digest */
	protected $batch;

	/**
	 * Initiate mailing of a comment digest for a post.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id
	 * @param int $retry_wait_seconds Minimum time to wait if a retry is necessary, null for default
	 * @param null|Repositories\Scheduled_Callback_HTTP $callback_repo
	 * @param null|Prompt_Api_Client
	 */
	public static function initiate( $post_id, $retry_wait_seconds = null, $callback_repo = null, $api_client = null ) {

		$post_list = new Lists\Posts\Post( $post_id );

		$callback_repo = $callback_repo ?: new Repositories\Scheduled_Callback_HTTP();
		
		$callback_id = $post_list->get_comment_digest_callback_id();
		
		if ( $callback_id ) {
			
			$result = $callback_repo->delete( $callback_id );
			
			if ( is_wp_error( $result ) ) {
				Prompt_Logging::add_wp_error( $result );
			}
			
			$post_list->set_comment_digest_callback_id( null );
		}
		
		
		$batch = new Email_Batches\Comment_Digest( $post_list );

		if ( ! $batch->get_comments() ) {
			// Don't send digests with no comments
			return;
		}

		$mailer = new self( $batch, $api_client );

		$result = $mailer->set_retry_wait_seconds( $retry_wait_seconds )->send();

		if ( $mailer->reschedule( $result ) ) {
			return;
		}
		
		if ( is_wp_error( $result ) ) {
			Prompt_Logging::add_error(
				Prompt_Enum_Error_Codes::DIGEST,
				__( 'Encountered an error while mailing a comment digest.', 'postmatic-premium' ),
				compact( 'post_list', 'digest_post', 'batch', 'result' )
			);
		}
	}

	/**
	 * @since 0.1.0
	 *
	 * @param Email_Batches\Comment_Digest $batch
	 * @param Prompt_Api_Client $client
	 */
	public function __construct( Email_Batches\Comment_Digest $batch, Prompt_Api_Client $client = null ) {
		parent::__construct( $batch, $client );
	}

	/**
	 * Add idempotent checks and batch recording to the parent send method.
	 *
	 * @since 0.1.0
	 *
	 * @return null|array|\WP_Error
	 */
	public function send() {

		$this->batch->compile_recipients()->lock_for_sending();

		return parent::send();
	}

	/**
	 * Schedule a retry if a temporary failure has occurred.
	 *
	 * @since 0.1.0
	 *
	 * @param array $response
	 * @return bool Whether a retry has been rescheduled.
	 */
	public function reschedule( $response ) {

		$rescheduler = \Prompt_Factory::make_rescheduler( $response, $this->retry_wait_seconds );

		if ( $rescheduler->found_temporary_error() ) {
			
			$this->batch->clear_for_retry();
			
			$rescheduler->reschedule(
				'postmatic/premium/mailers/comment_digest/initiate',
				array( $this->batch->get_post_list()->id() )
			);
			return true;
		}

		return false;
	}

}