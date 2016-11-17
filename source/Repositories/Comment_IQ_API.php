<?php
namespace Postmatic\Commentium\Repositories;

use Postmatic\Commentium\Models;

use Postmatic\CommentIQ\API;

use WP_Error;

/**
 * Comment IQ API-based repository.
 *
 * @since 0.1.0
 */
class Comment_IQ_API implements Comment_IQ {
	
	/** @var API\Client */
	protected $client;

	/**
	 * Instantiate a Comment IQ API-based repository.
	 *
	 * @since 0.1.0
	 * @param API\Client $client The API client to use.
	 */
	public function __construct( API\Client $client ) {
		$this->client = $client;
	}

	/**
	 * Save an article.
	 *
	 * Updates existing articles, adds new ones.
	 *
	 * @since 0.1.0
	 * @param Models\Comment_IQ_Article $article
	 * @return int|WP_Error Comment ID or error.
	 */
	public function save_article( Models\Comment_IQ_Article $article ) {

		$article_id = $article->get_comment_iq_id();

		if ( $article_id ) {
			$this->client->update_article( $article_id, $article->get_comment_iq_content() );
		} else {
			$article_id = $this->client->add_article( $article->get_comment_iq_content() );
		}

		if ( is_numeric( $article_id ) ) {
			$article->set_comment_iq_id( $article_id );
		}

		return $article_id;
	}

	/**
	 * Save a comment.
	 *
	 * Comment IQ details will be set on the comment. Saves the related article if it does not have an comment IQ ID.
	 *
	 * @since 0.1.0
	 * @param Models\Comment_IQ_Article $article The article the comment pertains to.
	 * @param Models\Comment_IQ_Comment $comment The comment to add.
	 * @return int|WP_Error Comment ID or error.
	 */
	public function save_comment( Models\Comment_IQ_Article $article, Models\Comment_IQ_Comment $comment ) {

		$comment_id = $comment->get_comment_iq_id();


		if ( $comment_id ) {

			$details = $this->client->update_comment(
				$comment_id,
				$comment->get_comment_iq_body(),
				$comment->get_comment_iq_date(),
				$comment->get_comment_iq_username()
			);

		} else {

			$article_id = $article->get_comment_iq_id();

			if ( ! $article_id ) {
				$article_id = $this->save_article( $article );
			}

			if ( is_wp_error( $article_id ) ) {
				return $article_id;
			}

			$details = $this->client->add_comment(
				$article_id,
				$comment->get_comment_iq_body(),
				$comment->get_comment_iq_date(),
				$comment->get_comment_iq_username()
			);

		}

		if ( is_wp_error( $details ) ) {
			return $details;
		}

		if ( ! isset( $details['commentID'] ) ) {
			return new WP_Error( 'comment_iq_error', 'IQ details were missing ID.', $details );
		}

		$comment_id = $details['commentID'];
		unset( $details['commentID'] );

		$comment->set_comment_iq_id( $comment_id );
		$comment->set_comment_iq_details( $details );

		return $comment_id;
	}
}
