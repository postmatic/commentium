<?php
namespace Postmatic\Commentium\Unit_Tests\Filters;

use Postmatic\Commentium\Unit_Tests\Mock_Mailer_Test_Case;
use Postmatic\Commentium\Filters;
use Prompt_Core;
use Prompt_Enum_Message_Types;

class Comment_Moderation extends Mock_Mailer_Test_Case {

	function test_default() {
		$original_addresses = array( 'test@example.com' );

		$comment_id = $this->factory->comment->create();

		$recipients = Filters\Comment_Moderation::recipients( $original_addresses, $comment_id );

		$this->assertEquals(
			$original_addresses,
			$recipients,
			'Expected original addresses without moderation enabled.'
		);
	}

	function test_auto_approve_moderator_comment() {
		Prompt_Core::$options->set( 'enabled_message_types', array( Prompt_Enum_Message_Types::COMMENT_MODERATION ) );

		$admin = $this->factory->user->create_and_get( array( 'role' => 'administrator' ) );
		$post = $this->factory->post->create_and_get();
		$comment_id = $this->factory->comment->create( array(
			'comment_post_ID' => $post->ID,
			'comment_approved' => 0,
			'comment_author_email' => $admin->user_email,
		) );

		$this->mailer_expects = $this->never();

		$recipients = Filters\Comment_Moderation::recipients( array( $admin->user_email ), $comment_id );

		$this->assertEmpty( $recipients );

		$check_comment = get_comment( $comment_id );
		$this->assertEquals( 1, $check_comment->comment_approved, 'Expected auto-approved moderator comment.' );

		Prompt_Core::$options->reset();
	}

	function test_moderation_notifications() {
		Prompt_Core::$options->set( 'enabled_message_types', array( Prompt_Enum_Message_Types::COMMENT_MODERATION ) );

		$admin = $this->factory->user->create_and_get( array( 'role' => 'administrator' ) );
		$author = $this->factory->user->create_and_get( array( 'role' => 'author' ) );
		$post = $this->factory->post->create_and_get( array( 'post_author' => $author->ID ) );
		$comment = $this->factory->comment->create_and_get( array(
			'comment_post_ID' => $post->ID,
			'comment_approved' => 0,
		) );

		$this->mail_data->admin = $admin;
		$this->mail_data->author = $author;
		$this->mail_data->post = $post;
		$this->mail_data->comment = $comment;
		$this->mail_data->addresses = array( $admin->user_email, $author->user_email );

		$this->mailer_will = $this->returnCallback( array( $this, 'verify_moderation_notifications' ) );

		$recipients = Filters\Comment_Moderation::recipients( $this->mail_data->addresses, $comment );

		$this->assertEmpty( $recipients );

		Prompt_Core::$options->reset();
	}

	function verify_moderation_notifications() {

		$message_template = $this->mailer_payload->get_batch_message_template();
		$message_values = $this->mailer_payload->get_individual_message_values();

		$this->assertCount( 2, $message_values, 'Expected two notification emails.' );

		$this->assertContains( $message_values[0]['to_address'], $this->mail_data->addresses );
		$this->assertContains( $message_values[1]['to_address'], $this->mail_data->addresses );

		$this->assertContains(
			$this->mail_data->post->post_title,
			$message_template['subject'],
			'Expected post title in email subject.'
		);

		$this->assertContains(
			$this->mail_data->comment->comment_author,
			$message_template['html_content'],
			'Expected commenter name in email.'
		);

		$this->assertContains(
			$this->mail_data->comment->comment_content,
			$message_template['html_content'],
			'Expected comment content in email.'
		);

		$this->assertContains(
			$this->mail_data->comment->comment_ID,
			$message_values[0]['reply_to']['trackable-address']->ids,
			'Expected comment ID in metadata'
		);

		$this->assertEquals(
			Prompt_Enum_Message_Types::COMMENT_MODERATION,
			$message_template['message_type'],
			'Expected comment moderation message type.'
		);
	}

	function test_comment_unapproved_action() {
		add_filter( 'comment_moderation_recipients', array( $this, 'check_unapproved_notification' ), 9, 2 );
		Prompt_Core::$options->set( 'enabled_message_types', array( Prompt_Enum_Message_Types::COMMENT_MODERATION ) );


		$post_id = $this->factory->post->create();
		$comment = $this->factory->comment->create_and_get( array( 'comment_post_ID' => $post_id ) );

		$comment->comment_approved = 0;
		wp_update_comment( (array)$comment );

		$this->assertTrue( $this->unapproved_moderation_triggered, 'Expected to detect a moderation email.' );

		Prompt_Core::$options->reset();
		remove_filter( 'comment_moderation_recipients', array( $this, 'check_unapproved_notification' ), 9 );
	}

	function check_unapproved_notification( $addresses, $comment_id ) {
		$this->unapproved_moderation_triggered = true;
		// Prevent actual sending
		return array();
	}

}