<?php
namespace Postmatic\Commentium\Unit_Tests\Repositories;

use Postmatic\Commentium\Repositories;
use Postmatic\Commentium\Models;
use PHPUnit_Framework_TestCase;

class Scheduled_Callback_HTTP extends PHPUnit_Framework_TestCase {

	protected $values;

	function test_get_by_id() {

		$values = array( 'id' => 1 );

		$client_mock = $this->getMock( 'Prompt_Api_Client' );
		$client_mock->expects( $this->once() )
			->method( 'get_scheduled_callback' )
			->with( $values['id'] )
			->will( $this->returnValue( array(
				'response' => array( 'code' => 200 ),
				'body' => json_encode( array( 'scheduled_callback' => $values ) ),
			) ) );

		$repo = new Repositories\Scheduled_Callback_HTTP( $client_mock );

		$callback = $repo->get_by_id( $values['id'] );

		$this->assertEquals( $values['id'], $callback->get_id(), 'Expected callback with looked up ID' );
	}

	function test_delete() {

		$id = 1;

		$client_mock = $this->getMock( 'Prompt_Api_Client' );
		$client_mock->expects( $this->once() )
			->method( 'delete_scheduled_callback' )
			->with( $id )
			->will( $this->returnValue( array(
				'response' => array( 'code' => 200 ),
				'body' => '',
			) ) );

		$repo = new Repositories\Scheduled_Callback_HTTP( $client_mock );

		$this->assertTrue( $repo->delete( $id ), 'Expected delete to return true.' );
	}

	function test_save() {

		$callback = new Models\Scheduled_Callback( array( 'id' => 1 ) );

		$client_mock = $this->getMock( 'Prompt_Api_Client' );
		$client_mock->expects( $this->once() )
			->method( 'post_scheduled_callbacks' )
			->with( $callback->to_array() )
			->will( $this->returnValue( array(
				'response' => array( 'code' => 200 ),
				'body' => json_encode( $callback->to_array() ),
			) ) );

		$repo = new Repositories\Scheduled_Callback_HTTP( $client_mock );
		$id = $repo->save( $callback );

		$this->assertEquals( 1, $id, 'Expected save to return ID.' );
	}
}