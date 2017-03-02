<?php
namespace Postmatic\Commentium\Unit_Tests;

use WP_UnitTestCase;

class No_Outbound_Test_Case extends WP_UnitTestCase {

	protected $remove_outbound_hooks = true;
	protected $remove_comment_iq_hooks = true;

	public function setUp() {
		parent::setUp();

		if ( $this->remove_outbound_hooks ) {
			$this->remove_outbound_hooks();
		}

		if ( $this->remove_comment_iq_hooks ) {
			$this->remove_comment_iq_hooks();
		}
	}

	protected function remove_outbound_hooks() {
		remove_action( 'transition_post_status',        array( 'Prompt_Outbound_Handling', 'action_transition_post_status' ) );
		remove_action( 'wp_insert_comment',             array( 'Prompt_Outbound_Handling', 'action_wp_insert_comment' ) );
		remove_action( 'transition_comment_status',     array( 'Prompt_Outbound_Handling', 'action_transition_comment_status' ) );
		remove_filter( 'comment_moderation_recipients', array( 'Prompt_Outbound_Handling', 'filter_comment_moderation_recipients' ) );
	}

	protected function remove_comment_iq_hooks() {
		remove_action( 'wp_insert_comment', array( 'Postmatic\Commentium\Actions\Comment_IQ', 'maybe_save_comment' ) );
		remove_action( 'edit_comment', array( 'Postmatic\Commentium\Actions\Comment_IQ', 'maybe_save_comment' ) );
		remove_action( 'save_post', array( 'Postmatic\Commentium\Actions\Comment_IQ', 'maybe_save_post_article' ), 10, 2 );
	}
}