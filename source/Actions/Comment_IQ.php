<?php
namespace Postmatic\Commentium\Actions;

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
	 *      @type bool $is_api_transport                 True if the email API is in use.
	 *      @type array $site_subscription_post_types    Post types with subscription enabled.
	 *      @type API\Client $client                     A commentIQ API client.
	 *      @type Comment_IQ_Article $article            The local article.
	 * }
	 */
	public static function maybe_sync_post_article( $post_id, $post, $deps = array() ) {

		if ( 'publish' != $post->post_status ) {
			return;
		}

		$is_api_transport = isset( $deps['is_api_transport'] ) ?
			$deps['is_api_transport'] :
			Prompt_Core::$options->is_api_transport();

		if ( ! $is_api_transport ) {
			return;
		}

		$enabled_post_types = isset( $deps['site_subscription_post_types'] ) ?
			$deps['site_subscription_post_types'] :
			Prompt_Core::$options->get( 'site_subscription_post_types' );

		if ( ! in_array( $post->post_type, $enabled_post_types ) ) {
			return;
		}

		$article = isset( $deps['article'] ) ? $deps['article'] : new Post( $post );

		$client = isset( $deps['client'] ) ? $deps['client'] : new API\WordPressClient( _wp_http_get_object(), static::$base_url );

		$article_id = $article->get_comment_iq_article_id();

		if ( empty( $article_id ) ) {
			$article_id = $client->add_article( $post->post_content );
		} elseif ( is_numeric( $article_id ) ) {
			$client->update_article( $article_id, $post->post_content );
		}

		if ( is_numeric( $article_id ) ) {
			$article->set_comment_iq_article_id( $article_id );
		}
	}
	
	/**
	 * Add a Comment IQ comment if appropriate.
	 *
	 * Done when comment digests are enabled for approved comments.
	 *
	 * @since 0.1.0
	 * @param int $comment_id ID of a post just saved.
	 * @param array $deps {
	 *      Optional array of dependencies.
	 *
	 *      @type array $enabled_message_types           Currently enabled message types.
	 *      @type WP_Comment $wp_comment            WordPress comment object.
	 *      @type WP_Post $wp_post            WordPress post object.
	 *      @type API\Client $client                     A commentIQ API client.
	 *      @type Comment_IQ_Article $article            The local article IQ model.
	 *      @type Comment_IQ_Comment $comment            The local comment IQ model.
	 * }
	 */
	public static function maybe_add_comment( $comment_id, $deps = array() ) {

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

		$article = isset( $deps['article'] ) ? $deps['article'] : new Post( $wp_comment->comment_post_ID );

		$article_id = $article->get_comment_iq_article_id();

		if ( is_null( $article_id ) ) {
			$wp_post = isset( $deps['wp_post'] ) ? $deps['wp_post'] : get_post( $wp_comment->comment_post_ID );
			static::maybe_sync_post_article( $wp_post->ID, $wp_post, $deps );
			$article_id = $article->get_comment_iq_article_id();
		}

		if ( is_null( $article_id ) ) {
			return;
		}

		$client = isset( $deps['client'] ) ? $deps['client'] : new API\WordPressClient( _wp_http_get_object(), static::$base_url );

		$comment_details = $client->add_comment(
			$article_id,
			$wp_comment->comment_content,
			$wp_comment->comment_date_gmt,
			$wp_comment->comment_author
		);

		if ( is_wp_error( $comment_details ) ) {
			return;
		}

		$comment = isset( $deps['comment'] ) ? $deps['comment'] : new Comment( $wp_comment );

		if ( isset( $comment_details['commentID'] ) ) {
			$comment->set_comment_iq_comment_id( $comment_details['commentID'] );
			unset( $comment_details['commentID'] );
		}

		$comment->set_comment_iq_comment_details( $comment_details );
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