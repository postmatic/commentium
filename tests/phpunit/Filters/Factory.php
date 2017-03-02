<?php
namespace Postmatic\Commentium\Unit_Tests\Filters;

use Postmatic\Commentium\Unit_Tests\No_Outbound_Test_Case;
use Postmatic\Commentium\Filters;
use Prompt_Comment_Flood_Controller;
use Prompt_Core;
use Prompt_Enum_Message_Types;

class Factory extends No_Outbound_Test_Case {

	public function test_make_default_comment_flood_controller() {
		$comment = $this->factory->comment->create_and_get();
		$basic_controller = new Prompt_Comment_Flood_Controller( $comment );
		$controller = Filters\Factory::make_comment_flood_controller( $basic_controller, $comment );
		$this->assertNotInstanceOf( 'Postmatic\Commentium\Flood_Controllers\Comment', $controller );
	}

	public function test_make_api_comment_flood_controller() {
		Prompt_Core::$options->set( 'enabled_message_types', array( Prompt_Enum_Message_Types::COMMENT_DIGEST ) );
		
		$comment = $this->factory->comment->create_and_get();
		$basic_controller = new Prompt_Comment_Flood_Controller( $comment );
		$controller = Filters\Factory::make_comment_flood_controller( $basic_controller, $comment );
		$this->assertInstanceOf( 'Postmatic\Commentium\Flood_Controllers\Comment', $controller );
	}
}