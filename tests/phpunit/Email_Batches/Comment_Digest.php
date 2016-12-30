<?php
namespace Postmatic\Commentium\Unit_Tests\Email_Batches;

use Postmatic\Commentium\Lists;
use Postmatic\Commentium\Email_Batches;
use Prompt_Enum_Message_Types;
use Prompt_Enum_Email_Transports;
use Prompt_Core;
use WP_UnitTestCase;

class Comment_Digest extends WP_UnitTestCase {

	public function test_defaults() {

		$post_list = new Lists\Posts\Post( $this->factory->post->create_and_get() );

		$batch = new Email_Batches\Comment_Digest( $post_list );

		$this->assertEquals( $post_list, $batch->get_post_list() );

		$batch_message_template = $batch->get_batch_message_template();

		$this->assertEquals( Prompt_Enum_Message_Types::COMMENT, $batch_message_template['message_type'] );

		$this->assertContains( $post_list->get_wp_post()->post_title, $batch_message_template['subject'] );

		$this->assertContains( $post_list->get_wp_post()->post_title, $batch_message_template['subject'] );

		$this->assertContains(
			'mailto:',
			$batch_message_template['footnote_html'],
			'Expected mailto URL in footer HTML.'
		);

		$this->assertContains(
			\Prompt_Unsubscribe_Matcher::target(),
			$batch_message_template['footnote_html'],
			'Expected unsubscribe prompt in footer HTML.'
		);

		$this->assertContains(
			\Prompt_Unsubscribe_Matcher::target(),
			$batch_message_template['footnote_text'],
			'Expected unsubscribe prompt in footer text.'
		);

		$this->assertEmpty( $batch->get_comments(), 'Expected no comments.' );

		$this->assertEmpty( $batch->get_default_values(), 'Expected no default values.' );

		$this->assertEmpty( $batch->get_individual_message_values(), 'Expected no individual values.' );
	}

	public function test_get_comments() {

		$post_list = new Lists\Posts\Post( $this->factory->post->create_and_get() );

		$post_list->set_digested_comments_date_gmt( get_gmt_from_date( '1 hour ago' ) );

		$digested_comment = $this->factory->comment->create_and_get( array(
			'comment_post_ID' => $post_list->id(),
			'comment_date_gmt' => get_gmt_from_date( '2 hours ago' )
		) );

		$undigested_comment = $this->factory->comment->create_and_get( array(
			'comment_post_ID' => $post_list->id(),
			'comment_date_gmt' => get_gmt_from_date( 'now' )
		) );

		$batch = new Email_Batches\Comment_Digest( $post_list );

		$comments = $batch->get_comments();

		$this->assertCount( 1, $comments );

		$this->assertEquals( $undigested_comment, $comments[0] );
	}

	public function test_recipients() {

		$subscriber_one = $this->factory->user->create_and_get();
		$subscriber_two = $this->factory->user->create_and_get();

		$post_list = new Lists\Posts\Post( $this->factory->post->create_and_get() );

		$post_list->subscribe( $subscriber_one->ID );
		$post_list->subscribe( $subscriber_two->ID );
		
		$parent_comment = $this->factory->comment->create_and_get( array(
			'comment_date_gmt' => get_gmt_from_date( '2 hours ago' ),
			'comment_post_ID' => $post_list->id()
		) );
		
		$post_list->set_digested_comments_date_gmt( get_gmt_from_date( '1 hour ago ' ) );
		
		$child_comment = $this->factory->comment->create_and_get( array(
			'comment_post_ID' => $post_list->id(),
			'comment_parent' => $parent_comment->comment_ID,
		) );

		$batch = new Email_Batches\Comment_Digest( $post_list );

		$batch->compile_recipients();

		$individual_message_values = $batch->get_individual_message_values();

		$this->assertCount( 2, $individual_message_values, 'Expected two recipients.' );

		$this->assertEquals( $subscriber_one->user_email, $individual_message_values[0]['to_address'] );
		
		$this->assertArrayHasKey(
			"reply_to_comment_{$parent_comment->comment_ID}",
			$individual_message_values[0],
			'Expected a parent comment reply macro.'
		);
		
		$this->assertArrayHasKey(
			"reply_to_comment_{$child_comment->comment_ID}",
			$individual_message_values[0],
			'Expected a child comment reply macro.'
		);
	}

	public function test_lock_for_sending() {

		$older_post = $this->factory->post->create_and_get( array( 'post_date_gmt' => get_gmt_from_date( '1 day ago' ) ) );
		
		$post_list = new Lists\Posts\Post( $older_post );

		$batch = new Email_Batches\Comment_Digest( $post_list );

		$batch->lock_for_sending();

		$digested_date = $post_list->get_digested_comments_date_gmt();
		
		$this->assertGreaterThan( $post_list->get_wp_post()->post_date_gmt, $digested_date );
	}
	
	public function test_clear_for_retry() {
		
		$post_list = new Lists\Posts\Post( $this->factory->post->create_and_get() );

		$batch = new Email_Batches\Comment_Digest( $post_list );
		
		$original_digested_date = get_gmt_from_date( '3 days ago' );
		
		$post_list->set_digested_comments_date_gmt( $original_digested_date );
		
		$batch->lock_for_sending();
		$batch->clear_for_retry();
		
		$this->assertEquals( $original_digested_date, $post_list->get_digested_comments_date_gmt() );
	}
	
	public function test_parent_rendering() {

		Prompt_Core::$options->set( 'email_transport', Prompt_Enum_Email_Transports::API );

		$post_list = new Lists\Posts\Post( $this->factory->post->create_and_get() );
		
		$post_list->set_digested_comments_date_gmt( get_gmt_from_date( '1 hour ago' ) );
		$root_comment = $this->factory->comment->create_and_get( array(
			'comment_post_ID' => $post_list->id(),
			'comment_date' => get_gmt_from_date( '1 day ago' ),
		) );
		
		$parent_author = $this->factory->user->create_and_get();
		$parent_comment = $this->factory->comment->create_and_get( array(
			'comment_post_ID' => $post_list->id(),
			'comment_date' => get_gmt_from_date( '2 hours ago' ),
			'comment_parent' => $root_comment->comment_ID,
			'user_id' => $parent_author->ID,
		) );
		
		$child_comment = $this->factory->comment->create_and_get( array(
			'comment_post_ID' => $post_list->id(),
			'comment_parent' => $parent_comment->comment_ID,
			'comment_date' => get_gmt_from_date( '10 minutes ago' ),
		) );

		$next_comment = $this->factory->comment->create_and_get( array(
			'comment_post_ID' => $post_list->id(),
		) );

		$batch = new Email_Batches\Comment_Digest( $post_list );
		
		$batch_template = $batch->get_batch_message_template();
		
		$html = $batch_template['html_content'];
		
		$this->assertContains( 'depth-3', $html, 'Expected a third level comment.' );
		
		$this->assertGreaterThan(
			strpos( $html, "reply_to_comment_{$root_comment->comment_ID}" ),
			strpos( $html, "reply_to_comment_{$next_comment->comment_ID}" ),
			'Expected ascending root comment order.'
		);
		
		$this->assertContains( 
			md5( $parent_author->user_email ),
			$html,
			'Expected parent author email hash in gravatar link.'
		);
		
		$this->assertContains(
			'contextual',
			$html,
			'Expected parent comment to have the contextual class.'
		);
		
		$this->assertContains(
			'featured',
			$html,
			'Expected child comment to have the featured class.'
		);
	}
}
