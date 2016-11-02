<?php
namespace Postmatic\Commentium\Filters;

use Postmatic\Commentium\Flood_Controllers;
use Prompt_Comment_Flood_Controller;
use Prompt_Core;

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
		if ( ! Prompt_Core::$options->is_api_transport() ) {
			return $controller;
		}
		return new Flood_Controllers\Comment( $comment );
	}
}