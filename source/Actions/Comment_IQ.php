<?php
namespace Postmatic\Commentium\Actions;

use Postmatic\Commentium\Repositories;
use Postmatic\Commentium\Models\Comment_IQ_Article;
use Postmatic\Commentium\Models\Comment;
use Postmatic\Commentium\Lists\Posts\Post;
use Postmatic\CommentIQ\API;
use Postmatic\Commentium\Models\Comment_IQ_Comment;

use Prompt_Core;
use WP_Post;
use WP_Comment;

class Comment_IQ {

	protected static $base_url = 'http://iq.gopostmatic.com/commentIQ/v1';

	/**
	 * Synchronize a post with a Comment IQ article if appropriate.
	 *
	 * Done when the service API is enabled for enabled post types that are published.
	 *
	 * @since 0.1.0
	 * @param int $post_id ID of a post just saved.
	 * @param WP_Post $post The post object.
	 * @param array $deps {
	 *      Optional array of dependencies.
	 *
	 * @type array $enabled_message_types Currently enabled message types.
	 * @type array $site_subscription_post_types Post types with subscription enabled.
	 * @type Repositories\Comment_IQ $repo A Comment IQ repository.
	 * @type Comment_IQ_Article $article The local article.
	 * }
	 */
	public static function maybe_save_post_article( $post_id, $post, $deps = array() ) {

		if ( 'publish' != $post->post_status ) {
			return;
		}

		$enabled_message_types = isset( $deps['enabled_message_types'] ) ?
			$deps['enabled_message_types'] :
			Prompt_Core::$options->get( 'enabled_message_types' );

		if ( ! in_array( 'comment-digest', $enabled_message_types ) ) {
			return;
		}

		$enabled_post_types = isset( $deps['site_subscription_post_types'] ) ?
			$deps['site_subscription_post_types'] :
			Prompt_Core::$options->get( 'site_subscription_post_types' );

		if ( ! in_array( $post->post_type, $enabled_post_types ) ) {
			return;
		}

		$repo = isset( $deps['repo'] ) ?
			$deps['repo'] :
			new Repositories\Comment_IQ_API( new API\WordPressClient( _wp_http_get_object(), static::$base_url ) );

		$article = isset( $deps['article'] ) ? $deps['article'] : new Post( $post );

		$repo->save_article( $article );
	}

	/**
	 * Synchronize a comment with Comment IQ if appropriate.
	 *
	 * Done when comment digests are enabled for approved comments.
	 *
	 * @since 0.1.0
	 * @param int $comment_id ID of a post just saved.
	 * @param array $deps {
	 *      Optional array of dependencies.
	 *
	 * @type array $enabled_message_types Currently enabled message types.
	 * @type WP_Comment $wp_comment WordPress comment object.
	 * @type Repositories\Comment_IQ $repo A Comment IQ repository.
	 * @type Comment_IQ_Article $article The local article IQ model.
	 * @type Comment_IQ_Comment $comment The local comment IQ model.
	 * }
	 */
	public static function maybe_save_comment( $comment_id, $deps = array() ) {

		$wp_comment = isset( $deps['wp_comment'] ) ? $deps['wp_comment'] : get_comment( $comment_id );

		if ( ! static::is_valid_wp_comment( $wp_comment ) ) {
			return;
		}

		$enabled_message_types = isset( $deps['enabled_message_types'] ) ?
			$deps['enabled_message_types'] :
			Prompt_Core::$options->get( 'enabled_message_types' );

		if ( ! in_array( 'comment-digest', $enabled_message_types ) ) {
			return;
		}

		$repo = isset( $deps['repo'] ) ?
			$deps['repo'] :
			new Repositories\Comment_IQ_API( new API\WordPressClient( _wp_http_get_object(), static::$base_url ) );

		$article = isset( $deps['article'] ) ? $deps['article'] : new Post( $wp_comment->comment_post_ID );

		$iq_comment = isset( $deps['comment'] ) ? $deps['comment'] : new Comment( $wp_comment );

		$repo->save_comment( $article, $iq_comment );
	}

	/**
	 * Whether a WordPress is worth trying to get IQ data for.
	 *
	 * @since 0.1.0
	 * @param WP_Comment $wp_comment The WordPress comment object.
	 * @return bool
	 */
	protected static function is_valid_wp_comment( $wp_comment ) {
		return isset( $wp_comment->approved ) and '1' == $wp_comment->approved and empty( $wp_comment->comment_type );
	}

}