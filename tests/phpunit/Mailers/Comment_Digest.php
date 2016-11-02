<?php
namespace Postmatic\Commentium\Unit_Tests\Mailers;

use Postmatic\Commentium\Lists;
use Postmatic\Commentium\Mailers;
use WP_UnitTestCase;
use stdClass;

class Comment_Digest extends WP_UnitTestCase {

	protected $data;

	function setUp() {
		parent::setUp();
		$this->data = new stdClass();
	}

	function test_send() {

		$api_mock = $this->getMock( 'Prompt_Api_Client' );
		$api_mock->expects( $this->once() )
			->method( 'post_outbound_message_batches' )
			->willReturn( array( 'response' => array( 'code' => 200 ), 'body' => '{ "id": 1 }' ) );

		$batch_mock = $this->getMockBuilder( 'Postmatic\Commentium\Email_Batches\Comment_Digest' )
			->disableOriginalConstructor()
			->getMock();

		$batch_mock->expects( $this->once() )
			->method( 'compile_recipients' )
			->will( $this->returnValue( $batch_mock ) );

		$batch_mock->expects( $this->once() )
			->method( 'lock_for_sending' )
			->will( $this->returnValue( $batch_mock ) );

		$batch_mock->expects( $this->once() )
			->method( 'get_individual_message_values' )
			->will( $this->returnValue( true ) );

		$mailer = new Mailers\Comment_Digest( $batch_mock, $api_mock );

		$mailer->send();
	}
	
	function test_retry() {
		
		$post_id = 1;
		$post_list = new Lists\Posts\Post( $post_id );
		
		$api_mock = $this->getMock( 'Prompt_Api_Client' );
		$api_mock->expects( $this->never() )->method( 'post_outbound_message_batches' );
	
		$batch_mock = $this->getMockBuilder( 'Postmatic\Commentium\Email_Batches\Comment_Digest' )
			->disableOriginalConstructor()
			->getMock();
		
		$batch_mock->expects( $this->once() )
			->method( 'get_post_list' )
			->willReturn( $post_list );
		
		$batch_mock->expects( $this->once() )->method( 'clear_for_retry' );

		$rescheduler_mock = $this->getMockBuilder( 'Prompt_Rescheduler' )
			->disableOriginalConstructor()
			->getMock();
		
		$rescheduler_mock->expects( $this->once() ) 
			->method( 'found_temporary_error' )
			->willReturn( true );
		
		$rescheduler_mock->expects( $this->once() )
			->method( 'reschedule' )
			->with( 'postmatic/premium/mailers/comment_digest/initiate', array( $post_id ) );

		$factory_mock = $this->getMock( 'Foo', array( 'get_rescheduler_mock' ) );
		$factory_mock->expects( $this->once() )
			->method( 'get_rescheduler_mock' )
			->willReturn( $rescheduler_mock );

		add_filter( 'prompt/make_rescheduler', array( $factory_mock, 'get_rescheduler_mock' ) );

		$mailer = new Mailers\Comment_Digest( $batch_mock, $api_mock );

		$result = $mailer->reschedule( array( 'response' => array( 'code' => 503 ) ) );
		
		$this->assertTrue( $result, 'Expected reschedule to return true.' );

		remove_filter( 'prompt/make_rescheduler', array( $factory_mock, 'get_rescheduler_mock' ) );
	}

	function test_initiate() {
		$callback_id = 1;
		
		$post_id = $this->factory->post->create();
		$post_list = new Lists\Posts\Post( $post_id );
		$post_list->set_comment_digest_callback_id( $callback_id );
		
		$repo_mock = $this->getMock( 'Postmatic\Commentium\Repositories\Scheduled_Callback_HTTP' );
		$repo_mock->expects( $this->once() )
			->method( 'delete' )
			->with( $callback_id )
			->willReturn( true );
		
		// Will not send since there are no comments on the post
		Mailers\Comment_Digest::initiate( $post_id, null, $repo_mock );
		
		$this->assertEmpty( $post_list->get_comment_digest_callback_id(), 'Expected saved callback ID to be removed' );
	}
}

