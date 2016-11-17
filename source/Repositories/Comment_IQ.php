<?php
namespace Postmatic\Commentium\Repositories;

use Postmatic\Commentium\Models;

/**
 * Comment IQ Repository.
 *
 * @since 0.1.0
 */
interface Comment_IQ {

	/**
	 * Save an article.
	 *
	 * Adds new articles, and updates existing ones.
	 *
	 * @since 0.1.0
	 * @param Models\Comment_IQ_Article $article The article to save.
	 * @return int|WP_Error Article ID or error.
	 */
	public function save_article( Models\Comment_IQ_Article $article );

	/**
	 * Save a comment.
	 *
	 * Also save the related article if it does not have an comment IQ ID. Adds new comments and updates existing ones.
	 *
	 * @since 0.1.0
	 * @param Models\Comment_IQ_Article $article The article the comment pertains to.
	 * @param Models\Comment_IQ_Comment $comment The comment to add.
	 * @return int|WP_Error Comment ID or error.
	 */
	public function save_comment( Models\Comment_IQ_Article $article, Models\Comment_IQ_Comment $comment );
}

