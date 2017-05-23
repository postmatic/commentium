<?php
namespace Postmatic\Commentium\Unit_Tests\Admin\Options_Tabs;

use Postmatic\Commentium\Admin\Options_Tabs;

use Prompt_Options;
use Prompt_Enum_Message_Types;

use WP_UnitTestCase;

class Comments extends WP_UnitTestCase {

    /** @var  Prompt_Options */
    protected $options_mock;

    public function setUp() {
        parent::setUp();
        $this->options_mock = $this->getMockBuilder( 'Prompt_Options' )->disableOriginalConstructor()->getMock();
        $this->options_mock->expects($this->any())
                           ->method('get')
                           ->willReturn(array());
    }

	public function test_render_local() {

		$tab = new Options_Tabs\Comments( $this->options_mock );

		$content = $tab->render();

		$this->assertContains( 'Comment flood control', $content, 'Expected the old flood control title.' );
		$this->assertContains( 'comment_flood_control_trigger_count', $content, 'Expected the same flood control field name.' );
		$this->assertNotContains( 'enable_replies_only', $content, 'Expected no replies only field.' );
		$this->assertNotContains( '<script', $content, 'Expected no inline script in content.' );
	}
	
	public function test_render_api_with_author_subscribe() {

        $options_mock = $this->getMockBuilder( 'Prompt_Options' )->disableOriginalConstructor()->getMock();

        $options_mock->expects( $this->any() )
            ->method( 'is_api_transport' )
            ->willReturn( true );

        $options_mock->expects( $this->any() )
            ->method( 'get' )
            ->willReturnCallback( function( $name = null, $default = null ) {
                return ( $name === 'enabled_message_types' ) ? array( Prompt_Enum_Message_Types::COMMENT_MODERATION ) : array();
            });

		$tab = new Options_Tabs\Comments( $options_mock );

		$content = $tab->render();

		$this->assertContains( 'Comment digests', $content, 'Expected a new flood control title.' );
		$this->assertContains( 'comment_flood_control_trigger_count', $content, 'Expected the same flood control field name.' );
        $this->assertContains( 'enable_replies_only', $content, 'Expected the replies only field.' );
        $this->assertContains( '<script', $content, 'Expected inline script in content.' );
        $this->assertContains( 'auto_subscribe_authors', $content, 'Expected the author auto-subscribe field.' );
	}

	public function test_render_api_without_author_subscribe() {

        $options_mock = $this->getMockBuilder( 'Prompt_Options' )->disableOriginalConstructor()->getMock();

        $options_mock->expects( $this->any() )
            ->method( 'is_api_transport' )
            ->willReturn( true );

        $options_mock->expects( $this->any() )
            ->method( 'get' )
            ->willReturnCallback( function( $name = null, $default = null ) {
                $moderation_and_post_types = array(
                    Prompt_Enum_Message_Types::COMMENT_MODERATION,
                    Prompt_Enum_Message_Types::POST
                );
                return ( $name === 'enabled_message_types' ) ? $moderation_and_post_types : array();
            });

		$tab = new Options_Tabs\Comments( $options_mock );

		$content = $tab->render();

		$this->assertContains( 'Comment digests', $content, 'Expected a new flood control title.' );
		$this->assertContains( 'comment_flood_control_trigger_count', $content, 'Expected the same flood control field name.' );
        $this->assertContains( 'enable_replies_only', $content, 'Expected the replies only field.' );
        $this->assertContains( '<script', $content, 'Expected inline script in content.' );
        $this->assertNotContains( 'auto_subscribe_authors', $content, 'Expected the author auto-subscribe field.' );
	}

	public function test_disable_replies_only() {
        $tab = new Options_Tabs\Comments( $this->options_mock );

        $new_data = array( 'comment_flood_control_trigger_count' => 3 );
        $old_data = array( 'enable_replies_only' => true );

        $valid_data = $tab->validate( $new_data, $old_data );

        $this->assertFalse( $valid_data['enable_replies_only'], 'Expected replies only to be disabled.' );
        $this->assertEquals( 3, $valid_data['comment_flood_control_trigger_count'], 'Expected default trigger count.' );
    }

	public function test_enable_replies_only() {
        $tab = new Options_Tabs\Comments( $this->options_mock );

        $new_data = array( 'comment_flood_control_trigger_count' => 3, 'enable_replies_only' => true );
        $old_data =  array( 'enable_replies_only' => false );

        $valid_data = $tab->validate( $new_data, $old_data );

        $this->assertTrue( $valid_data['enable_replies_only'], 'Expected replies only to be enabled.' );
        $this->assertEquals( 0, $valid_data['comment_flood_control_trigger_count'], 'Expected trigger count to be 0.' );
    }

	public function test_disable_author_subscribe() {
        $tab = new Options_Tabs\Comments( $this->options_mock );

        $new_data = array( 'comment_flood_control_trigger_count' => 3 );
        $old_data = array( 'auto_subscribe_authors' => true );

        $valid_data = $tab->validate( $new_data, $old_data );

        $this->assertFalse( $valid_data['auto_subscribe_authors'], 'Expected author subscribe to be disabled.' );
    }

	public function test_enable_author_subscribe() {
        $tab = new Options_Tabs\Comments( $this->options_mock );

        $new_data = array(  'comment_flood_control_trigger_count' => 3, 'auto_subscribe_authors' => true  );
        $old_data = array(  'auto_subscribe_authors' => false  );

        $valid_data = $tab->validate( $new_data, $old_data );

        $this->assertTrue( $valid_data['auto_subscribe_authors'], 'Expected author subscribe to be enabled.' );
    }
}
