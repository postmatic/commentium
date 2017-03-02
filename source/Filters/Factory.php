<?php
namespace Postmatic\Commentium\Filters;

use Postmatic\Commentium\Flood_Controllers;
use Prompt_Comment_Flood_Controller;
use Prompt_Core;
use Prompt_Enum_Message_Types;

/**
 * Filter factory created objects
 * @since 2.0.0
 */
class Factory {

	/**
	 * @since 2.0.0
	 * @param Prompt_Comment_Flood_Controller $controller
	 * @param null|object|\WP_Comment $comment
	 * @return Prompt_Comment_Flood_Controller
	 */
	public static function make_comment_flood_controller( Prompt_Comment_Flood_Controller $controller, $comment = null ) {
		if ( ! in_array( Prompt_Enum_Message_Types::COMMENT_DIGEST, Prompt_Core::$options->get( 'enabled_message_types' ), false ) ) {
			return $controller;
		}
		return new Flood_Controllers\Comment( $comment );
	}
}