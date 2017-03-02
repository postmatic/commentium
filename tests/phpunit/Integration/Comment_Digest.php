<?php
namespace Postmatic\Commentium\Unit_Tests\Integration;

use Postmatic\Commentium\Lists;
use Postmatic\Commentium\Unit_Tests\Mock_Mailer_Test_Case;
use Postmatic\Commentium\Flood_Controllers;
use Postmatic\Commentium\Repositories;
use Postmatic\Commentium\Models;
use Postmatic\Commentium\Mailers;
use Prompt_Core;
use Prompt_Enum_Message_Types;

class Comment_Digest extends Mock_Mailer_Test_Case {
	
	/** @var Repositories\Scheduled_Callback_HTTP */
	private $callback_repo;
	/** @var Lists\Posts\Post */
	private $post_list;
	/** @var \WP_User */
	private $subscriber;
	/** @var Models\Scheduled_Callback */
	private $callback;
	

	function setUp() {
		parent::setUp(); 
		Prompt_Core::$options->set( 'enabled_message_types', array( Prompt_Enum_Message_Types::COMMENT_DIGEST ) );
		Prompt_Core::$options->set( 'comment_flood_control_trigger_count', 2 );
		
		add_filter( 'prompt/make_comment_flood_controller', array( $this, 'make_flood_controller' ), 10, 2 );
		
		$this->callback_repo = $this->getMock( 'Postmatic\Commentium\Repositories\Scheduled_Callback_HTTP' );
		$this->callback_repo->expects( $this->any() )->method( 'save' )->willReturnCallback( array( $this, 'set_callback' ) );
		$this->callback_repo->expects( $this->any() )->method( 'delete' )->willReturnCallback( array( $this, 'delete_callback' ) );
		$this->callback_repo->expects( $this->any() )->method( 'get_by_id' )->willReturnCallback( array( $this, 'get_callback' ) );

		$post = $this->factory->post->create_and_get( array( 'post_date_gmt' => get_gmt_from_date( '1 day ago' ) ) );
		$this->post_list = new Lists\Posts\Post( $post );

		$this->subscriber = $this->factory->user->create_and_get();
		
		$this->post_list->subscribe( $this->subscriber->ID );
	}
	
	function tearDown() {
		Prompt_Core::$options->reset();
		remove_filter( 'prompt/make_comment_flood_controller', array( $this, 'make_flood_controller' ), 10 );
		parent::tearDown(); 
	}

	function make_flood_controller( $controller, $comment ) {
		$this->assertInstanceOf( 'Postmatic\Commentium\Flood_Controllers\Comment', $controller );
		return new Flood_Controllers\Comment( $comment, $this->callback_repo );
	}
	
	function set_callback( $callback ) {
		$this->callback = $callback;
		$this->callback->set_id( 1 );
		return 1;
	}
	
	function delete_callback( $id ) {
		if ( 1 == $id ) {
			$this->callback = null;
		}
		return true;
	}
	
	function get_callback( $id ) {
		if ( 1 != $id ) {
			return null;
		}
		return $this->callback;
	}
	
	function test_comment_digest_cycle() {
	
		$this->mailer_expects = $this->exactly( 3 );
		
		// Expect comment 2 to trigger flood control but be delivered singly
		$this->mailer_will = $this->returnCallback( array( $this, 'verify_last_comment_email' ) );
		
		$this->mail_data->new_comments = $this->factory->comment->create_many( 2, array( 
			'comment_post_ID' => $this->post_list->id(),
			'comment_date_gmt' => get_gmt_from_date( '3 hours ago' ),
		) );
		
		\Prompt_Comment_Mailing::send_notifications( $this->mail_data->new_comments[1] );

		// Expect comment 3 not to be delivered singly
		$this->mailer_will = $this->returnCallback( array( $this, 'verify_post_flood_comment_email' ) );

		$this->mail_data->new_comments = $this->factory->comment->create_many( 1, array(
			'comment_post_ID' => $this->post_list->id(),
			'comment_date_gmt' => get_gmt_from_date( '2 hours ago' ),
		) );

		\Prompt_Comment_Mailing::send_notifications( $this->mail_data->new_comments[0] );
		
		// Expect the next comment digest to contain comment 3
		$api_client = $this->getMock( 'Prompt_Api_Client' );
		$api_client->expects( $this->exactly( 2 ) )
			->method( 'post_outbound_message_batches' )
			->willReturnCallback( array( $this, 'verify_comment_digest_email' ) );

		Mailers\Comment_Digest::initiate( $this->post_list->id(), null, $this->callback_repo, $api_client );
	
		$this->assertGreaterThan( 
			get_gmt_from_date( '10 seconds ago' ), 
			$this->post_list->get_digested_comments_date_gmt(),
			'Expected a current digested comments date.'
		);
		
		// Now pretend the comment digest went out 2 hours ago when the comment was published
		$this->post_list->set_digested_comments_date_gmt( '2 hours ago' );
		
		// Expect comment 4, made one hour ago, not to be delivered singly
		$this->mail_data->new_comments = $this->factory->comment->create_many( 1, array(
			'comment_post_ID' => $this->post_list->id(),
			'comment_date_gmt' => get_gmt_from_date( '1 hour ago' ),
		) );

		\Prompt_Comment_Mailing::send_notifications( $this->mail_data->new_comments[0] );
				
		// Expect the next comment digest to contain comment 4
		Mailers\Comment_Digest::initiate( $this->post_list->id(), null, $this->callback_repo, $api_client );
	
		// Without new comments initiate does not send
		Mailers\Comment_Digest::initiate( $this->post_list->id(), null, $this->callback_repo, $api_client );
	}

	function verify_last_comment_email() {
		$this->assertInstanceOf( 'Prompt_Comment_Email_Batch', $this->mailer_payload );
		
		$batch_template = $this->mailer_payload->get_batch_message_template();
		$this->assertEquals( \Prompt_Enum_Message_Types::COMMENT, $batch_template['message_type'] );
		
		$values = $this->mailer_payload->get_individual_message_values();
		$this->assertCount( 1, $values, 'Expected one recipient.' );
		$this->assertEquals( $this->subscriber->user_email, $values[0]['to_address'], 'Expected email to subscriber.' );
	}
	
	function verify_post_flood_comment_email() {
		$this->assertEmpty( $this->mailer_payload->get_individual_message_values(), 'Expected no single comment recipients.' );
	}
	
	function verify_comment_digest_email( $data ) {
		$batch_template = $data['batch_message_template'];
		$this->assertEquals( \Prompt_Enum_Message_Types::COMMENT, $batch_template['message_type'] );
		
		$comment = get_comment( $this->mail_data->new_comments[0] );
		$this->assertContains( 
			$comment->comment_author,
			$batch_template['html_content'],
			'Expected the comment author name in email content.'
		);
		
		$values = $data['individual_message_values'];
		$this->assertCount( 1, $values, 'Expected one recipient.' );
		$this->assertEquals( $this->subscriber->user_email, $values[0]['to_address'], 'Expected email to subscriber.' );
		
		return array( 'response' => array( 'code' => 200 ), 'body' => '{ "id": 1 }' );
	}
}
