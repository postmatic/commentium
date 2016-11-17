<?php
namespace Postmatic\Commentium\Unit_Tests\Lists\Posts;

use Postmatic\Commentium\Lists;

use WP_UnitTestCase;

class Post extends WP_UnitTestCase {

	function test_digest_exclusion() {
		$sent_post = new Lists\Posts\Post( $this->factory->post->create() );
		$exclude_post = new Lists\Posts\Post( $this->factory->post->create() );
		$unsent_post_id = $this->factory->post->create();

		$check_posts = get_posts();

		$this->assertCount( 3, $check_posts );

		$sent_post->add_sent_digest( 1 );
		$exclude_post->set_exclude_from_digests( true );

		$check_posts = get_posts( array( 'meta_query' => array( Lists\Posts\Post::include_in_digest_meta_clauses() ) ) );

		$this->assertCount( 1, $check_posts );
		$this->assertEquals( $unsent_post_id, $check_posts[0]->ID, 'Expected the unsent post.' );
	}

	function test_digest_discussion_mode_default() {
		$post_list = new Lists\Posts\Post( $this->factory->post->create() );
		$this->assertFalse( $post_list->in_digest_discussion_mode(), 'Expected post NOT to be in digest discussion mode.' );
	}

	function test_digest_discussion_mode_flood() {
		$post_list = new Lists\Posts\Post( $this->factory->post->create() );

		$post_list->set_flood_control_comment_id( 1 );

		$this->assertTrue( $post_list->in_digest_discussion_mode(), 'Expected post to be in digest discussion mode.' );
	}

	function test_comment_digest_callback_id_default() {
		$post_list = new Lists\Posts\Post( $this->factory->post->create() );

		$this->assertNull(
			$post_list->get_comment_digest_callback_id(),
			'Expected no callback ID by default.'
		);
	}

	function test_set_comment_digest_callback_id() {
		$post_list = new Lists\Posts\Post( $this->factory->post->create() );

		$post_list->set_comment_digest_callback_id( 3 );

		$this->assertEquals(
			3,
			$post_list->get_comment_digest_callback_id(),
			'Expected the comment digest callback id that was set.'
		);
	}

	function test_digested_comments_date_gmt_default() {
		$post_list = new Lists\Posts\Post( $this->factory->post->create() );

		$this->assertEquals( $post_list->get_wp_post()->post_date_gmt, $post_list->get_digested_comments_date_gmt() );
	}

	function test_set_digested_comments_date_gmt() {
		$post_list = new Lists\Posts\Post( $this->factory->post->create() );

		$test_date = get_gmt_from_date( 'yesterday' );

		$post_list->set_digested_comments_date_gmt( $test_date );

		$this->assertEquals( $test_date, $post_list->get_digested_comments_date_gmt() );
	}

	function test_undigested_comments_date_clauses() {
		$post_list = new Lists\Posts\Post( $this->factory->post->create() );

		$digested_date = get_gmt_from_date( '3 hours ago' );

		$post_list->set_digested_comments_date_gmt( $digested_date );

		$clauses = $post_list->undigested_comments_date_clauses();

		$this->assertEquals( 'comment_date_gmt', $clauses[0]['column'], 'Expected digested comment GMT date column.' );
		$this->assertEquals( $digested_date, $clauses[0]['after'], 'Expected digested date in after clause.' );
	}

	function test_article_id_default() {
		$post_list = new Lists\Posts\Post( $this->factory->post->create() );

		$this->assertNull(
			$post_list->get_comment_iq_id(),
			'Expected no article ID by default.'
		);
	}

	function test_set_article_id() {
		$post_list = new Lists\Posts\Post( $this->factory->post->create() );

		$post_list->set_comment_iq_id( 13 );

		$this->assertEquals( 13, $post_list->get_comment_iq_id(), 'Expected to get the ID that was set.' );
	}

	function test_article_content() {
		$content = 'XXCONTENTXX';
		$post_list = new Lists\Posts\Post( $this->factory->post->create( array( 'post_content' => $content ) ) );

		$this->assertEquals(
			$content,
			$post_list->get_comment_iq_content(),
			'Expected article content to be post content.'
		);
	}

}