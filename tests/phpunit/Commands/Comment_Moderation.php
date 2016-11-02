<?php
namespace Postmatic\Commentium\Unit_Tests\Commands;

use Postmatic\Commentium\Unit_Tests\No_Outbound_Test_Case;
use Postmatic\Commentium\Commands;

use stdClass;

class Comment_Moderation extends No_Outbound_Test_Case {

	function make_command_with_message( $comment_text = null ) {
		$command = new Commands\Comment_Moderation();
		$command->set_message( $comment_text );
		return $command;
	}

	function test_keys() {
		$test_keys = array( 3, 5 );

		$command = $this->make_command_with_message();
		$command->set_keys( $test_keys );

		$this->assertEquals( $test_keys, $command->get_keys(), 'Expected to get the set keys back.');
	}

	function test_id_setters() {
		$test_keys = array( 3, 5 );

		$command = $this->make_command_with_message();
		$command->set_comment_id( $test_keys[0] );
		$command->set_moderator_id( $test_keys[1] );

		$this->assertEquals( $test_keys, $command->get_keys(), 'Expected to get the set keys back.');
	}

	function test_add_comment() {
		$author_id = $this->factory->user->create( array( 'role' => 'author' ) );

		$post_id = $this->factory->post->create( array( 'post_author' => $author_id ) );

		$comment_id = $this->factory->comment->create( array( 'comment_post_ID' => $post_id, 'comment_approved' => 0 ) );

		$message = new stdClass();
		$message->message = 'Trash talk gets you everywhere.';

		$_SERVER['SERVER_NAME'] = 'test.tld';

		$command = $this->make_command_with_message( $message->message );
		$command->set_keys( array( $comment_id, $author_id ) );
		$command->set_message( $message );
		$command->execute();

		$comment = get_comment( $comment_id );

		$this->assertEquals( 1, $comment->comment_approved, 'Expected the comment to be approved.' );

		$comments = get_comments( array(
			'post_id' => $post_id,
			'user_id' => $author_id,
		) );

		$this->assertCount( 1, $comments, 'Expected to find new comment from the author.' );
		$this->assertEquals(
			$message->message,
			$comments[0]->comment_content,
			'Expected the comment text to be the same as the message body.'
		);
	}

	/**
	 * @dataProvider moderation_command_provider
	 */
	function test_execute( $command, $status ) {
		$author_id = $this->factory->user->create( array( 'role' => 'author' ) );

		$post_id = $this->factory->post->create( array( 'post_author' => $author_id ) );

		$comment_id = $this->factory->comment->create( array( 'comment_post_ID' => $post_id, 'comment_approved' => 0 ) );

		$message = new stdClass();
		$message->message = $command;

		$_SERVER['SERVER_NAME'] = 'test.tld';

		$command = $this->make_command_with_message( $command );
		$command->set_keys( array( $comment_id, $author_id ) );
		$command->set_message( $message );
		$command->execute();

		$comment = get_comment( $comment_id );

		$this->assertEquals( $status, $comment->comment_approved, 'Expected a different comment status.' );
	}

	function moderation_command_provider() {
		return array(
			array( 'approve', 1 ),
			array( "approve\n\n", 1 ),
			array( 'aprove', 1 ),
			array( 'appove', 1 ),
			array( 'apporve', 1 ),
			array( 'approvel', 1 ),
			array( 'aproove', 1 ),
			array( 'publish', 1 ),
			array( 'publsih', 1 ),
			array( 'puplish', 1 ),
			array( 'pubish', 1 ),
			array( 'publis', 1 ),
			array( 'publishe', 1 ),
			array( 'publich', 1 ),
			array( "\t", 1 ),
			array( '', 1 ),
			array( 'trash', 'trash' ),
			array( 'trach', 'trash' ),
			array( 'trush', 'trash' ),
			array( 'tash', 'trash' ),
			array( 'trah', 'trash' ),
			array( 'trsh', 'trash' ),
			array( 'tras', 'trash' ),
			array( 'trahs', 'trash' ),
			array( 'spam', 'spam' ),
			array( 'spamm', 'spam' ),
			array( 'sam', 'spam' ),
		);
	}

	function test_insufficient_capability() {
		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$comment_id = $this->factory->comment->create( array( 'comment_approved' => 0 ) );

		$message = new stdClass();
		$message->message = 'approve';

		$_SERVER['SERVER_NAME'] = 'example.com';

		$command = $this->make_command_with_message( $message );
		$command->set_keys( array( $comment_id, $subscriber_id ) );
		$command->set_message( $message );

		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$command->execute();

		$comment = get_comment( $comment_id );

		$this->assertEquals( 0, $comment->comment_approved, 'Expected the comment to be approved.' );
	}

	function test_moderate_comments_capability() {
		$subscriber_role = get_role( 'subscriber' );
		$subscriber_role->add_cap( 'moderate_comments' );

		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$comment_id = $this->factory->comment->create( array( 'comment_approved' => 0 ) );

		$message = new stdClass();
		$message->message = 'approve';

		$_SERVER['SERVER_NAME'] = 'example.com';

		$command = $this->make_command_with_message( $message );
		$command->set_keys( array( $comment_id, $subscriber_id ) );
		$command->set_message( $message );
		$command->execute();

		$comment = get_comment( $comment_id );

		$this->assertEquals( 1, $comment->comment_approved, 'Expected the comment to be approved.' );
		$subscriber_role->remove_cap( 'moderate_comments' );
	}

	function test_wrong_number_of_keys_exception() {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );

		$command = new Commands\Comment_Moderation();
		$command->set_keys( array( 3 ) );
		$command->execute();
	}

}