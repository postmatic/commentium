<?php
namespace Postmatic\Commentium\Unit_Tests\Models;

use Postmatic\Commentium\Models;
use WP_UnitTestCase;

class Scheduled_Callback extends WP_UnitTestCase {

	protected $values;

	function setUp() {
		parent::setUp();
		$this->values = array(
			'id' => null,
			'start_timestamp' => strtotime( 'next tuesday' ),
			'started_on' => 'last tuesday',
			'recurrence_seconds' => 9999,
			'next_invocation_on' => 'next wednesday',
			'last_invocation_on' => 'last wednesday',
			'metadata' => array( 'foo' => 'bar' ),
		);
	}

	function test_defaults() {

		$callback = new Models\Scheduled_Callback();

		$this->assertNull( $callback->get_id() );
		$this->assertGreaterThan(
			time(),
			$callback->get_start_timestamp(),
			'Expected default start in the future.'
		);
		$this->assertGreaterThan(
			0,
			$callback->get_recurrence_seconds(),
			'Expected a nonzero recurrence time.'
		);
		$this->assertEmpty( $callback->get_metadata(), 'Expected no metadata by default.' );
	}

	function test_array_representation() {

		$callback = new Models\Scheduled_Callback( $this->values );

		$this->assertEqualSets( $this->values, $callback->to_array() );
	}

}