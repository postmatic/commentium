<?php namespace Postmatic\Commentium\Unit_Tests;

use Postmatic\Commentium\Filters;
use Prompt_Core;

class Comment_Notifications extends No_Outbound_Test_Case {

	function test_default() {
		$comment_id = $this->factory->comment->create();

		$this->assertTrue(
			Filters\Comment_Notifications::allow( true, $comment_id ),
			'Expected filter not to change allow value.'
		);
		$this->assertFalse(
			Filters\Comment_Notifications::allow( false, $comment_id ),
			'Expected filter not to change allow value.'
		);
	}

	function test_allow_good_comment() {
		Prompt_Core::$options->set( 'comment_snob_notifications', true );

		$comment_id = $this->factory->comment->create();
		$meta = array(
			'ArticleRelevance' => 0.26,
			'Length' => 31
		);
		update_comment_meta( $comment_id, 'commentiq_comment_details', $meta );

		$this->assertTrue(
			Filters\Comment_Notifications::allow( true, $comment_id ),
			'Expected filter not to change allow value.'
		);
	}

	function test_reject_irrelevant_comment() {
		Prompt_Core::$options->set( 'comment_snob_notifications', true );

		$comment_id = $this->factory->comment->create();
		$meta = array(
			'ArticleRelevance' => 0.24,
			'Length' => 31
		);
		update_comment_meta( $comment_id, 'commentiq_comment_details', $meta );

		$this->assertFalse(
			Filters\Comment_Notifications::allow( true, $comment_id ),
			'Expected filter not to change allow value.'
		);
	}
}