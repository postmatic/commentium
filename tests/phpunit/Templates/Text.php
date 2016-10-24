<?php
namespace Postmatic\Commentium\Unit_Testing\Templates;

use Postmatic\Commentium\Templates;

use PHPUnit_Framework_TestCase;

class Text extends PHPUnit_Framework_TestCase {

	public function test_locate() {
		$template = new Templates\HTML( 'test-text.php' );
		$this->assertContains(
			'templates/test-text.php',
			$template->locate()
		);
	}
}
