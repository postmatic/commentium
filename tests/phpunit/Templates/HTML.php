<?php
namespace Postmatic\Commentium\Unit_Testing\Templates;

use Postmatic\Commentium\Templates;

use PHPUnit_Framework_TestCase;

class HTML extends PHPUnit_Framework_TestCase {

	public function test_locate() {
		$template = new Templates\HTML( 'test.php' );
		$this->assertEquals(
			Core::get_instance()->path( 'templates/test.php' ),
			$template->locate()
		);
	}
}
