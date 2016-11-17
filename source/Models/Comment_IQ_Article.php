<?php
namespace Postmatic\Commentium\Models;

/**
 * Interface for representation of a commentIQ article.
 *
 * @since 0.1.0
 */
interface Comment_IQ_Article {

	/**
	 * Get the associated CommentIQ article ID.
	 *
	 * @since 0.1.0
	 * @return int
	 */
	public function get_comment_iq_id();

	/**
	 * Get the associated CommentIQ article content.
	 *
	 * @since 0.1.0
	 * @return int
	 */
	public function get_comment_iq_content();

	/**
	 * Set the associated CommentIQ article ID.
	 *
	 * @since 0.1.0
	 * @param int $id
	 * @return $this
	 */
	public function set_comment_iq_id( $id );
}