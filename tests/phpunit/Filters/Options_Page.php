<?php
namespace Postmatic\Commentium\Unit_Tests\Filters;

use Postmatic\Commentium\Filters;

use \WP_UnitTestCase;

class Options_Page extends WP_UnitTestCase {

    public function test_tabs() {
        $tabs = array(
            new \Prompt_Admin_Comment_Options_Tab( \Prompt_Core::$options ),
        );

        $tabs = Filters\Options_Page::tabs( $tabs );

        $this->assertCount( 1, $tabs, 'Expected no tabs to be added.' );
        $this->assertInstanceOf(
			'Prompt_Admin_Comment_Options_Tab',
			$tabs[0],
			'Expected the original comments tab.'
		);
    }

	public function test_premium_replace_tabs() {
		$tabs = array(
			new \Prompt_Admin_Comment_Options_Tab( \Prompt_Core::$options ),
		);

		\Prompt_Core::$options->set( 'enabled_message_types', array( 'post', 'digest' ) );
		\Prompt_Core::$options->set( 'email_transport', 'api' );

		$tabs = Filters\Options_Page::tabs( $tabs );

        $this->assertCount( 1, $tabs, 'Expected no tabs to be added.' );

		$this->assertInstanceOf(
			'Postmatic\Commentium\Admin\Options_Tabs\Comments',
			$tabs[0],
			'Expected the original comments tab to be replaced.'
		);
	}
}