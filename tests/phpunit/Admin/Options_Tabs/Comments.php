<?php
namespace Postmatic\Commentium\Unit_Tests\Admin\Options_Tabs;

use Postmatic\Commentium\Admin\Options_Tabs;

use Prompt_Core;
use Prompt_Enum_Email_Transports;

use WP_UnitTestCase;

class Comments extends WP_UnitTestCase {

	public function test_render_local() {
		$tab = new Options_Tabs\Comments( Prompt_Core::$options );

		$content = $tab->render();

		$this->assertContains( 'Comment flood control', $content, 'Expected the old flood control title.' );
		$this->assertContains( 'comment_flood_control_trigger_count', $content, 'Expected the same flood control field name.' );
		$this->assertNotContains( 'enable_replies_only', $content, 'Expected no replies only field.' );
		$this->assertNotContains( '<script', $content, 'Expected no inline script in content.' );
	}
	
	public function test_render_api() {
		Prompt_Core::$options->set( 'email_transport', Prompt_Enum_Email_Transports::API );
		
		$tab = new Options_Tabs\Comments( Prompt_Core::$options );

		$content = $tab->render();

		$this->assertContains( 'Comment digests', $content, 'Expected a new flood control title.' );
		$this->assertContains( 'comment_flood_control_trigger_count', $content, 'Expected the same flood control field name.' );
        $this->assertContains( 'enable_replies_only', $content, 'Expected the replies only field.' );
        $this->assertContains( '<script', $content, 'Expected inline script in content.' );
	}

	public function test_disable_replies_only() {
        $tab = new Options_Tabs\Comments( Prompt_Core::$options );

        $new_data = [ 'comment_flood_control_trigger_count' => 3 ];
        $old_data = [ 'enable_replies_only' => true ];

        $valid_data = $tab->validate( $new_data, $old_data );

        $this->assertFalse( $valid_data['enable_replies_only'], 'Expected replies only to be disabled.' );
    }

	public function test_enable_replies_only() {
        $tab = new Options_Tabs\Comments( Prompt_Core::$options );

        $new_data = [ 'comment_flood_control_trigger_count' => 3, 'enable_replies_only' => true ];
        $old_data = [ 'enable_replies_only' => false ];

        $valid_data = $tab->validate( $new_data, $old_data );

        $this->assertTrue( $valid_data['enable_replies_only'], 'Expected replies only to be enabled.' );
    }
}
