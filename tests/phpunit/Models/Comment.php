<?php
namespace Postmatic\Commentium\Unit_Tests\Models;

use Postmatic\Commentium\Models;
use Postmatic\Commentium\Unit_Tests\No_Outbound_Test_Case;

class Comment extends No_Outbound_Test_Case {

	function test_get_missing_comment_iq_id() {
		$comment = new Models\Comment( static::factory()->comment->create() );
		$this->assertNull( $comment->get_comment_iq_id(), 'Expected no comment IQ ID.' );
	}

	function test_set_comment_iq_id() {
		$comment = new Models\Comment( static::factory()->comment->create() );
		$comment->set_comment_iq_id( 13 );
		$this->assertEquals( 13, $comment->get_comment_iq_id(), 'Expected the comment IQ ID to be set.' );
	}

	function test_get_comment_iq_body() {
		$wp_comment = static::factory()->comment->create_and_get();
		$comment = new Models\Comment( $wp_comment );
		$this->assertEquals(
			$wp_comment->comment_content,
			$comment->get_comment_iq_body(),
			'Expected comment content as body.'
		);
	}

	function test_get_comment_iq_date() {
		$wp_comment = static::factory()->comment->create_and_get();
		$comment = new Models\Comment( $wp_comment );
		$this->assertEquals(
			$wp_comment->comment_date_gmt,
			$comment->get_comment_iq_date(),
			'Expected comment gmt date as date.'
		);
	}

	function test_get_comment_iq_username() {
		$wp_comment = static::factory()->comment->create_and_get();
		$comment = new Models\Comment( $wp_comment );
		$this->assertEquals(
			$wp_comment->comment_author,
			$comment->get_comment_iq_username(),
			'Expected comment author as username.'
		);
	}

	function test_get_missing_comment_iq_details() {
		$comment = new Models\Comment( static::factory()->comment->create() );
		$this->assertEmpty( $comment->get_comment_iq_details(), 'Expected no comment IQ details.' );
	}

	function test_set_comment_iq_details() {
		$comment = new Models\Comment( static::factory()->comment->create() );
		$data = array( 'Foo' => 'Bar' );
		$comment->set_comment_iq_details( $data );
		$this->assertEqualSets( $data, $comment->get_comment_iq_details(), 'Expected comment IQ data to be set.' );
	}
}
