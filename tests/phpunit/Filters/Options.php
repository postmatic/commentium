<?php

namespace Postmatic\Commentium\Unit_Tests\Filters;

use Postmatic\Commentium\Filters;

use \WP_UnitTestCase;

class Options extends WP_UnitTestCase {

	public function test_default_options() {
		$original_options = array( 'foo' => 'bar' );

		$filtered_options = Filters\Options::default_options( $original_options );

		$this->assertEquals(
			array_merge( $original_options, array( 'enable_replies_only' => '' ) ),
			$filtered_options,
			'Expected custom replies only option to be added.'
		);
	}

}
