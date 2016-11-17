<?php
namespace Postmatic\Commentium\Unit_Tests\Actions;

use Postmatic\Commentium\Actions;

use PHPUnit_Framework_TestCase;
use stdClass;

class Comment_IQ extends PHPUnit_Framework_TestCase {

	public function test_no_service_no_save() {

		$wp_post_stub = new stdClass();
		$wp_post_stub->post_status = 'publish';
		$wp_post_stub->post_type = 'post';

		$repo_mock = $this->getMock( 'Postmatic\Commentium\Repositories\Comment_IQ' );

		$repo_mock->expects( $this->never() )->method( 'save_article' );

		$dependencies = array(
			'enabled_message_types' => array( 'comment' ),
			'enabled_post_types' => array( 'post' ),
			'repo' => $repo_mock,
		);

		Actions\Comment_IQ::maybe_save_post_article( 0, $wp_post_stub, $dependencies );
	}

	public function test_save_post_article() {

		$wp_post_stub = new stdClass();
		$wp_post_stub->post_status = 'publish';
		$wp_post_stub->post_type = 'post';
		$wp_post_stub->post_content = 'TEST CONTENT';

		$repo_mock = $this->getMock( 'Postmatic\Commentium\Repositories\Comment_IQ' );

		$article_mock = $this->getMock( 'Postmatic\Commentium\Models\Comment_IQ_Article' );

		$repo_mock->expects( $this->once() )
			->method( 'save_article' )
			->with( $article_mock );

		$dependencies = array(
			'enabled_message_types' => array( 'comment-digest' ),
			'enabled_post_types' => array( 'post' ),
			'repo' => $repo_mock,
			'article' => $article_mock,
		);

		Actions\Comment_IQ::maybe_save_post_article( 1, $wp_post_stub, $dependencies );
	}

	public function test_save_comment_when_disabled() {

		$wp_comment_stub = new stdClass();
		$wp_comment_stub->approved = '1';

		$repo_mock = $this->getMock( 'Postmatic\Commentium\Repositories\Comment_IQ' );

		$repo_mock->expects( $this->never() )->method( 'save_comment' );

		$article_mock = $this->getMock( 'Postmatic\Commentium\Models\Comment_IQ_Article' );

		$iq_comment_mock = $this->getMock( 'Postmatic\Commentium\Models\Comment_IQ_Comment' );

		$dependencies = array(
			'enabled_message_types' => array( 'comment' ),
			'wp_comment' => $wp_comment_stub,
			'article' => $article_mock,
			'comment' => $iq_comment_mock,
		);

		Actions\Comment_IQ::maybe_save_comment( 0, $dependencies );
	}

	public function test_save_comment() {

		$wp_comment_stub = new stdClass();
		$wp_comment_stub->approved = '1';

		$article_mock = $this->getMock( 'Postmatic\Commentium\Models\Comment_IQ_Article' );

		$iq_comment_mock = $this->getMock( 'Postmatic\Commentium\Models\Comment_IQ_Comment' );

		$repo_mock = $this->getMock( 'Postmatic\Commentium\Repositories\Comment_IQ' );

		$repo_mock->expects( $this->once() )
			->method( 'save_comment' )
			->with( $article_mock, $iq_comment_mock );

		$dependencies = array(
			'enabled_message_types' => array( 'comment-digest' ),
			'wp_comment' => $wp_comment_stub,
			'article' => $article_mock,
			'comment' => $iq_comment_mock,
			'repo' => $repo_mock,
		);

		Actions\Comment_IQ::maybe_save_comment( 0, $dependencies );
	}

}