<?php
namespace Postmatic\Commentium\Repositories;

use Postmatic\Commentium\Models;
use Prompt_Api_Client;
use Prompt_Logging;
use Prompt_Enum_Error_Codes;

/**
 * Manage persistence of scheduled callbacks via the Postmatic HTTP API
 *
 * @since 0.1.0
 *
 */
class Scheduled_Callback_HTTP {

	/** @var Prompt_Api_Client */
	protected $api_client;

	/**
	 *
	 * @since 0.1.0
	 *
	 * @param null|Prompt_Api_Client $api_client Optionally override the default API client
	 */
	public function __construct( $api_client = null ) {
		$this->api_client = $api_client ? $api_client : new Prompt_Api_Client();
	}

	/**
	 * Get a remote scheduled callback by ID.
	 *
	 * @since 0.1.0
	 *
	 * @param int $id
	 * @return Models\Scheduled_Callback|\WP_Error
	 */
	public function get_by_id( $id ) {

		$response = $this->api_client->get_scheduled_callback( $id );

		if ( is_wp_error( $response ) or 200 != $response['response']['code'] ) {
			return Prompt_Logging::add_error(
				Prompt_Enum_Error_Codes::API,
				__( 'Failed to get digest callback information.', 'Postmatic' ),
				compact( 'plan', 'response' )
			);
		}

		$data = json_decode( $response['body'], $assoc = true );
		return new Models\Scheduled_Callback( $data['scheduled_callback'] );
	}

	/**
	 *
	 * @since 0.1.0
	 *
	 * @param int $id
	 * @return \WP_Error|true
	 */
	public function delete( $id ) {
		$response = $this->api_client->delete_scheduled_callback( $id );

		if ( is_wp_error( $response ) or 200 != $response['response']['code'] ) {
			return Prompt_Logging::add_error(
				Prompt_Enum_Error_Codes::API,
				__( 'Failed to delete a digest schedule.', 'Postmatic' ),
				compact( 'callback', 'response' )
			);
		}

		return true;
	}

	/**
	 *
	 * @since 0.1.0
	 *
	 * @param Models\Scheduled_Callback $callback
	 * @return \WP_Error|int Callback ID
	 */
	public function save( Models\Scheduled_Callback $callback ) {

		$response = $this->api_client->post_scheduled_callbacks( $callback->to_array() );

		if ( is_wp_error( $response ) or 200 != $response['response']['code'] ) {
			return Prompt_Logging::add_error(
				Prompt_Enum_Error_Codes::API,
				__( 'Couldn\'t set the digest schedule - could be temporary', 'Postmatic' ),
				compact( 'start_timestamp', 'recurrence_seconds', 'response' )
			);
		}

		$data = json_decode( $response['body'] );

		return $data->id;
	}
}