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

}