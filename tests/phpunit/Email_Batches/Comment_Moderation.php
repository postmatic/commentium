<?php
namespace Postmatic\Commentium\Unit_Tests\Email_Batches;

use Postmatic\Commentium\Email_Batches;
use Prompt_Enum_Message_Types;
use WP_UnitTestCase;

class Comment_Moderation extends WP_UnitTestCase {

	public function test_defaults() {

		$post = $this->factory->post->create_and_get();

		$comment = $this->factory->comment->create( array( 'comment_post_ID' => $post->ID ) );

		$batch = new Email_Batches\Comment_Moderation( $comment );

		$batch_template = $batch->get_batch_message_template();

		$this->assertContains(
			$post->post_title,
			$batch_template['subject'],
			'Expected post title in subject.'
		);

		$this->assertEquals( '{{{reply_to}}}', $batch_template['reply_to'] );

		$this->assertEquals( Prompt_Enum_Message_Types::COMMENT_MODERATION, $batch_template['message_type'] );

		$this->assertTrue( $batch_template['is_comment_moderation'], 'Expected conditional to indicate message type.' );

	}

	public function test_skip_incapable_recipients() {

		$author = $this->factory->user->create_and_get( array( 'role' => 'author' ) );
		$subscriber = $this->factory->user->create_and_get( array( 'role' => 'subscriber' ) );

		$post = $this->factory->post->create_and_get( array( 'post_author' => $author->ID ) );

		$comment = $this->factory->comment->create( array( 'comment_post_ID' => $post->ID ) );

		$batch = new Email_Batches\Comment_Moderation( $comment );

		$batch->add_recipient_addresses( array( $author->user_email, $subscriber->user_email ) );

		$invidual_message_values = $batch->get_individual_message_values();

		$this->assertEquals( 1, count( $invidual_message_values ), 'Expected only one recipient.' );
		$this->assertEquals( $author->user_email, $invidual_message_values[0]['to_address'] );
	}
}