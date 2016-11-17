<?php
namespace Postmatic\Commentium\Unit_Tests\Actions;

use Postmatic\Commentium\Actions;

use PHPUnit_Framework_TestCase;
use stdClass;

class Comment_IQ extends PHPUnit_Framework_TestCase {

	public function test_no_service_no_sync() {

		$wp_post_stub = new stdClass();
		$wp_post_stub->post_status = 'publish';
		$wp_post_stub->post_type = 'post';

		$article_mock = $this->getMockBuilder( 'Postmatic\Commentium\Lists\Posts\Post' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_comment_iq_article_id' ) )
			->getMock();

		$article_mock->expects( $this->never() )
			->method( 'get_comment_iq_article_id' );

		$client_mock = $this->getMockBuilder( 'Postmatic\CommentIQ\API\Client' )
			->setMethods( array( 'add_article' ) )
			->getMock()
			->expects( $this->never() )
			->method( 'add_article' );

		$dependencies = array(
			'is_api_transport' => false,
			'enabled_post_types' => array( 'post' ),
			'article' => $article_mock,
			'client' => $client_mock,
		);

		Actions\Comment_IQ::maybe_sync_post_article( 0, $wp_post_stub, $dependencies );
	}

	public function test_sync_post_article() {

		$wp_post_stub = new stdClass();
		$wp_post_stub->post_status = 'publish';
		$wp_post_stub->post_type = 'post';
		$wp_post_stub->post_content = 'TEST CONTENT';

		$article_mock = $this->getMockBuilder( 'Postmatic\Commentium\Lists\Posts\Post' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_comment_iq_article_id', 'set_comment_iq_article_id' ) )
			->getMock();

		$article_mock->expects( $this->once() )
			->method( 'get_comment_iq_article_id' )
			->willReturn( null );

		$client_mock = $this->getMockBuilder( 'Postmatic\CommentIQ\API\Client' )
			->setMethods( array( 'add_article' ) )
			->getMock();

		$client_mock->expects( $this->once() )
			->method( 'add_article' )
			->with( $wp_post_stub->post_content )
			->willReturn( 13 );

		$article_mock->expects( $this->once() )
			->method( 'set_comment_iq_article_id' )
			->with( 13 );

		$dependencies = array(
			'is_api_transport' => true,
			'enabled_post_types' => array( 'post' ),
			'article' => $article_mock,
			'client' => $client_mock,
		);

		Actions\Comment_IQ::maybe_sync_post_article( 1, $wp_post_stub, $dependencies );
	}

	public function test_new_comment_when_disabled() {

		$wp_comment_stub = new stdClass();
		$wp_comment_stub->approved = '1';

		$iq_comment_mock = $this->getMockBuilder( 'Postmatic\Commentium\Models\Comment' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_comment_iq_comment_id' ) )
			->getMock()
			->expects( $this->never() )
			->method( 'get_comment_iq_comment_id' );

		$article_mock = $this->getMockBuilder( 'Postmatic\Commentium\Lists\Posts\Post' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_comment_iq_article_id' ) )
			->getMock()
			->expects( $this->never() )
			->method( 'set_comment_iq_article_id' );

		$client_mock = $this->getMockBuilder( 'Postmatic\CommentIQ\API\Client' )
			->setMethods( array( 'add_comment' ) )
			->getMock()
			->expects( $this->never() )
			->method( 'add_comment' );

		$dependencies = array(
			'enabled_message_types' => array( \Prompt_Enum_Message_Types::COMMENT ),
			'enabled_post_types' => array( 'post' ),
			'wp_comment' => $wp_comment_stub,
			'article' => $article_mock,
			'comment' => $iq_comment_mock,
			'client' => $client_mock,
		);

		Actions\Comment_IQ::maybe_add_comment( 0, $dependencies );
	}

	public function test_new_comment() {

		$wp_comment_stub = new stdClass();
		$wp_comment_stub->approved = '1';
		$wp_comment_stub->comment_content = 'TEST COMMENT';
		$wp_comment_stub->comment_date_gmt = 'now';
		$wp_comment_stub->comment_author = 'Tony';

		$wp_post_stub = new stdClass();
		$wp_post_stub->ID = 3;

		$article_mock = $this->getMockBuilder( 'Postmatic\Commentium\Lists\Posts\Post' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_comment_iq_article_id' ) )
			->getMock();

		$article_mock->expects( $this->once() )
			->method( 'get_comment_iq_article_id' )
			->willReturn( 13 );

		$iq_data = array(
			'commentID' => 50,
			'foo' => 'bar',
		);

		$client_mock = $this->getMockBuilder( 'Postmatic\CommentIQ\API\Client' )
			->setMethods( array( 'add_comment' ) )
			->getMock();

		$client_mock->expects( $this->once() )
			->method( 'add_comment' )
			->with( 13, $wp_comment_stub->comment_content, $wp_comment_stub->comment_date_gmt, $wp_comment_stub->comment_author )
			->willReturn( $iq_data );

		$iq_comment_mock = $this->getMockBuilder( 'Postmatic\Commentium\Models\Comment' )
			->disableOriginalConstructor()
			->setMethods( array( 'set_comment_iq_comment_id', 'set_comment_iq_comment_details' ) )
			->getMock();

		$iq_comment_mock->expects( $this->once() )
			->method( 'set_comment_iq_comment_id' )
			->with( $iq_data['commentID'] );

		unset( $iq_data['commentID'] );

		$iq_comment_mock->expects( $this->once() )
			->method( 'set_comment_iq_comment_details' )
			->with( $iq_data );

		$dependencies = array(
			'enabled_message_types' => array( 'comment-digest' ),
			'enabled_post_types' => array( 'post' ),
			'wp_comment' => $wp_comment_stub,
			'wp_post' => $wp_post_stub,
			'article' => $article_mock,
			'comment' => $iq_comment_mock,
			'client' => $client_mock,
		);

		Actions\Comment_IQ::maybe_add_comment( 0, $dependencies );
	}
}