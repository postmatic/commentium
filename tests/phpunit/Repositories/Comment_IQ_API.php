<?php
namespace Postmatic\Commentium\Unit_Tests\Repositories;

use Postmatic\Commentium\Repositories;

use PHPUnit_Framework_TestCase;

class Comment_IQ_API extends PHPUnit_Framework_TestCase {

	function test_add_article() {

		$article_mock = $this->getMock( 'Postmatic\Commentium\Models\Comment_IQ_Article' );

		$article_mock->expects( $this->once() )
			->method( 'get_comment_iq_id' )
			->willReturn( null );

		$id = 6;
		$content = '<p>TEST CONTENT</p>';

		$article_mock->expects( $this->once() )
			->method( 'get_comment_iq_content' )
			->willReturn( $content );

		$article_mock->expects( $this->once() )
			->method( 'set_comment_iq_id' )
			->with( $id );

		$client_mock = $this->getMockBuilder( 'Postmatic\CommentIQ\API\Client' )
			->setMethods( array( 'add_article', 'add_comment', 'update_article', 'update_comment' ) )
			->getMock();

		$client_mock->expects( $this->once() )
			->method( 'add_article' )
			->with( $content )
			->willReturn( $id );

		$repo = new Repositories\Comment_IQ_API( $client_mock );

		$repo->save_article( $article_mock );
	}

	function test_update_article() {

		$article_mock = $this->getMock( 'Postmatic\Commentium\Models\Comment_IQ_Article' );

		$id = 6;
		$content = '<p>TEST CONTENT</p>';

		$article_mock->expects( $this->once() )
			->method( 'get_comment_iq_id' )
			->willReturn( $id );

		$article_mock->expects( $this->once() )
			->method( 'get_comment_iq_content' )
			->willReturn( $content );

		$client_mock = $this->getMockBuilder( 'Postmatic\CommentIQ\API\Client' )
			->setMethods( array( 'add_article', 'add_comment', 'update_article', 'update_comment' ) )
			->getMock();

		$client_mock->expects( $this->once() )
			->method( 'update_article' )
			->with( $id, $content );

		$repo = new Repositories\Comment_IQ_API( $client_mock );

		$repo->save_article( $article_mock );
	}

	function test_add_comment() {

		$article_id = 13;
		$details = array(
			'commentID' => 100,
			'Foo' => 'Bar',
		);
		$body = 'TEST COMMENT';
		$date = 'now';
		$username = 'tester';

		$article_mock = $this->getMock( 'Postmatic\Commentium\Models\Comment_IQ_Article' );

		$article_mock->expects( $this->once() )
			->method( 'get_comment_iq_id' )
			->willReturn( $article_id );

		$comment_mock = $this->getMock( 'Postmatic\Commentium\Models\Comment_IQ_Comment' );

		$comment_mock->expects( $this->once() )
			->method( 'get_comment_iq_body' )
			->willReturn( $body );

		$comment_mock->expects( $this->once() )
			->method( 'get_comment_iq_date' )
			->willReturn( $date );

		$comment_mock->expects( $this->once() )
			->method( 'get_comment_iq_username' )
			->willReturn( $username );

		$comment_mock->expects( $this->once() )
			->method( 'set_comment_iq_id' )
			->with( $details['commentID'] );

		$comment_mock->expects( $this->once() )
			->method( 'set_comment_iq_details' )
			->with( array( 'Foo' => 'Bar' ) );

		$client_mock = $this->getMockBuilder( 'Postmatic\CommentIQ\API\Client' )
			->setMethods( array( 'add_article', 'add_comment', 'update_article', 'update_comment' ) )
			->getMock();

		$client_mock->expects( $this->once() )
			->method( 'add_comment' )
			->with( $article_id, $body, $date, $username )
			->willReturn( $details );

		$repo = new Repositories\Comment_IQ_API( $client_mock );

		$repo->save_comment( $article_mock, $comment_mock );
	}

	function test_update_comment() {

		$details = array(
			'commentID' => 100,
			'Foo' => 'Bar',
		);
		$body = 'TEST COMMENT';
		$date = 'now';
		$username = 'tester';

		$article_mock = $this->getMock( 'Postmatic\Commentium\Models\Comment_IQ_Article' );

		$comment_mock = $this->getMock( 'Postmatic\Commentium\Models\Comment_IQ_Comment' );

		$comment_mock->expects( $this->once() )
			->method( 'get_comment_iq_id' )
			->willReturn( $details['commentID'] );

		$comment_mock->expects( $this->once() )
			->method( 'get_comment_iq_body' )
			->willReturn( $body );

		$comment_mock->expects( $this->once() )
			->method( 'get_comment_iq_date' )
			->willReturn( $date );

		$comment_mock->expects( $this->once() )
			->method( 'get_comment_iq_username' )
			->willReturn( $username );

		$client_mock = $this->getMockBuilder( 'Postmatic\CommentIQ\API\Client' )
			->setMethods( array( 'add_article', 'add_comment', 'update_article', 'update_comment' ) )
			->getMock();

		$client_mock->expects( $this->once() )
			->method( 'update_comment' )
			->with( $details['commentID'], $body, $date, $username )
			->willReturn( $details );

		$repo = new Repositories\Comment_IQ_API( $client_mock );

		$repo->save_comment( $article_mock, $comment_mock );
	}
}
