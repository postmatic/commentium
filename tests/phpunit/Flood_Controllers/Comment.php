<?php
namespace Postmatic\Commentium\Unit_Tests\Flood_Controllers;

use Postmatic\Commentium\Lists;

use Postmatic\Commentium\Unit_Tests\Mock_Mailer_Test_Case;
use Postmatic\Commentium\Flood_Controllers;
use Postmatic\Commentium\Models;
use Prompt_Site_Comments;
use Prompt_Core;

class Comment extends Mock_Mailer_Test_Case {

	/** @var  array */
	protected $subscriber_ids;
	/** @var Lists\Posts\Post */
	protected $post_list;
	/** @var  int */
	protected $site_subscriber_id;
	/** @var  \PHPUnit_Framework_MockObject_MockObject */
	protected $callback_repo_mock;

	public function setUp() {
		parent::setUp();

		Prompt_Core::$options->set( 'auto_subscribe_authors', true );

		$site_comments = new Prompt_Site_Comments();
		$this->site_subscriber_id = $this->factory->user->create();
		$site_comments->subscribe( $this->site_subscriber_id );

		$author_id = $this->factory->user->create();
		$this->post_list = new Lists\Posts\Post( $this->factory->post->create( array( 'post_author' => $author_id ) ) );

		$this->subscriber_ids = $this->factory->user->create_many( 2 );

		$this->post_list->subscribe( $this->subscriber_ids[0] );
		$this->post_list->subscribe( $this->subscriber_ids[1] );

		$this->mailer_expects = $this->never();

		$this->callback_repo_mock = $this->getMock( 'Postmatic\Commentium\Repositories\Scheduled_Callback_HTTP' );
	}

	public function test_pre_flood() {

		$first_comment = $this->factory->comment->create_and_get( array( 'comment_post_ID' => $this->post_list->id() ) );

		$this->callback_repo_mock->expects( $this->never() )->method( 'save' );

		$controller = new Flood_Controllers\Comment( $first_comment, $this->callback_repo_mock );

		$recipient_ids = $controller->control_recipient_ids();

		$this->assertCount( 4, $recipient_ids, 'Expected all subscribers pre-flood.' );
	}

	public function test_flood() {

		$flood_comment_ids = $this->factory->comment->create_many( 7, array( 'comment_post_ID' => $this->post_list->id() ) );

		$last_comment = get_comment( $flood_comment_ids[6] );

		$this->callback_repo_mock->expects( $this->once() )
			->method( 'save' )
			->with( $this->isInstanceOf( 'Postmatic\Commentium\Models\Scheduled_Callback' ) )
			->willReturnCallback( array( $this, 'verify_scheduled_callback' ) );

		$controller = new Flood_Controllers\Comment( $last_comment, $this->callback_repo_mock );

		$recipient_ids = $controller->control_recipient_ids();

		$this->assertEquals(
			$recipient_ids,
			array( $this->site_subscriber_id, $this->post_list->get_wp_post()->post_author ),
			'Expected only the site subscriber and post author to receive the flood comment.'
		);

		$this->assertCount( 2, $this->post_list->subscriber_ids(), 'Expected post subscribers to remain subscribed.' );

		$this->assertEquals( 
			$last_comment->comment_ID, 
			$this->post_list->get_flood_control_comment_id(),
			'Expected the flood comment ID to be set.' 
		);

		$next_comment = $this->factory->comment->create_and_get( array(
			'comment_post_ID' => $this->post_list->id(),
			'user_id' => $this->post_list->get_wp_post()->post_author,
		) );

		$controller = new Flood_Controllers\Comment( $next_comment, $this->callback_repo_mock );

		$recipient_ids = $controller->control_recipient_ids();

		$this->assertEquals(
			array( $this->site_subscriber_id ),
			$recipient_ids,
			'Expected only the site subscriber and post author to receive post-flood comments.'
		);
	}

	public function verify_scheduled_callback( Models\Scheduled_Callback $callback ) {

		$this->assertGreaterThan(
			strtotime( '+23 hours' ),
			$callback->get_start_timestamp(),
			'Expected a 24-hour callback.'
		);
		
		$this->assertLessThan(
			strtotime( '+25 hours' ),
			$callback->get_start_timestamp(),
			'Expected a 24-hour callback.'
		);

		$this->assertEquals(
			array( 'postmatic/premium/mailers/comment_digest/initiate', array( $this->post_list->id() ) ),
			$callback->get_metadata(),
			'Expected comment digest mailing callback metadata.'
		);

		return 3;
	}

	public function test_flood_replies_only() {

        Prompt_Core::$options->set( 'enable_replies_only', true );
        Prompt_Core::$options->set( 'comment_flood_control_trigger_count', 0 );

		$flood_comment = $this->factory->comment->create_and_get( array( 'comment_post_ID' => $this->post_list->id() ) );

		$this->callback_repo_mock->expects( $this->never() )->method( 'save' );

		$controller = new Flood_Controllers\Comment( $flood_comment, $this->callback_repo_mock );

		$recipient_ids = $controller->control_recipient_ids();

		$this->assertEquals(
			$recipient_ids,
			array( $this->site_subscriber_id, $this->post_list->get_wp_post()->post_author ),
			'Expected only the site subscriber and post author to receive the flood comment.'
		);

		$this->assertCount( 2, $this->post_list->subscriber_ids(), 'Expected post subscribers to remain subscribed.' );

		$this->assertEquals(
			$flood_comment->comment_ID,
			$this->post_list->get_flood_control_comment_id(),
			'Expected the flood comment ID to be set.'
		);

		$next_comment = $this->factory->comment->create_and_get( array(
			'comment_post_ID' => $this->post_list->id(),
			'user_id' => $this->post_list->get_wp_post()->post_author,
		) );

		$controller = new Flood_Controllers\Comment( $next_comment, $this->callback_repo_mock );

		$recipient_ids = $controller->control_recipient_ids();

		$this->assertEquals(
			array( $this->site_subscriber_id ),
			$recipient_ids,
			'Expected only the site subscriber to receive post-flood comments.'
		);
	}

	public function test_direct_reply() {

		$parent_author_id = $this->factory->user->create();
		
		$this->post_list->subscribe( $parent_author_id );
		
		$parent_comment_id = $this->factory->comment->create( array(
			'comment_post_ID' => $this->post_list->id(),
			'user_id' => $parent_author_id,
		) );
		
		$this->post_list->set_flood_control_comment_id( $parent_comment_id );
		
		$reply_author_id = $this->factory->user->create();
		
		$this->post_list->subscribe( $reply_author_id );

		$reply_comment = $this->factory->comment->create_and_get( array(
			'comment_post_ID' => $this->post_list->id(),
			'comment_parent' => $parent_comment_id,
			'user_id' => $reply_author_id,
		) );

		$this->callback_repo_mock->expects( $this->once() )->method( 'save' )->willReturn( 3 );
		
		$controller = new Flood_Controllers\Comment( $reply_comment, $this->callback_repo_mock );
		
		$recipient_ids = $controller->control_recipient_ids();
		
		$this->assertEquals(
			array( $this->site_subscriber_id, $this->post_list->get_wp_post()->post_author, $parent_author_id ),
			$recipient_ids,
			'Expected only the post and parent comment authors to be recipients.'
		);
	}

	public function test_direct_reply_only() {

        Prompt_Core::$options->set( 'auto_subscribe_authors', false );
        Prompt_Core::$options->set( 'enable_replies_only', true );
        Prompt_Core::$options->set( 'comment_flood_control_trigger_count', 0 );

		$parent_author_id = $this->factory->user->create();

		$this->post_list->subscribe( $parent_author_id );

		$parent_comment_id = $this->factory->comment->create( array(
			'comment_post_ID' => $this->post_list->id(),
			'user_id' => $parent_author_id,
		) );

		$this->post_list->set_flood_control_comment_id( $parent_comment_id );

		$reply_author_id = $this->factory->user->create();

		$this->post_list->subscribe( $reply_author_id );

		$reply_comment = $this->factory->comment->create_and_get( array(
			'comment_post_ID' => $this->post_list->id(),
			'comment_parent' => $parent_comment_id,
			'user_id' => $reply_author_id,
		) );

		$this->callback_repo_mock->expects( $this->never() )->method( 'save' );

		$controller = new Flood_Controllers\Comment( $reply_comment, $this->callback_repo_mock );

		$recipient_ids = $controller->control_recipient_ids();

		$this->assertEquals(
			array( $this->site_subscriber_id, $parent_author_id ),
			$recipient_ids,
			'Expected only the site subscriber and parent comment author to be recipients.'
		);
	}

}