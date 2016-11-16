<?php
namespace Postmatic\Commentium\Actions;

use Postmatic\Commentium\Models\Comment_IQ_Article;
use Postmatic\Commentium\Lists\Posts\Post;
use Postmatic\CommentIQ\API;

use Prompt_Core;
use WP_Post;

class Comment_IQ {

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

		$base_url = 'http://iq.gopostmatic.com/commentIQ/v1';

		$client = isset( $deps['client'] ) ? $deps['client'] : new API\WordPressClient( _wp_http_get_object(), $base_url );

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
}