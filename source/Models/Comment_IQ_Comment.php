<?php
namespace Postmatic\Commentium\Models;

/**
 * Interface for representation of a commentIQ comment.
 *
 * @since 0.1.0
 */
interface Comment_IQ_Comment {

	/**
	 * Get the CommentIQ comment ID.
	 *
	 * @since 0.1.0
	 * @return int|null CommentIQ ID if found, otherwise null.
	 */
	public function get_comment_iq_id();

	/**
	 * Get the CommentIQ comment body.
	 *
	 * @since 0.1.0
	 * @return string CommentIQ body.
	 */
	public function get_comment_iq_body();

	/**
	 * Get the CommentIQ comment date .
	 *
	 * @since 0.1.0
	 * @return string CommentIQ body.
	 */
	public function get_comment_iq_date();

	/**
	 * Get the CommentIQ comment date .
	 *
	 * @since 0.1.0
	 * @return string CommentIQ body.
	 */
	public function get_comment_iq_username();

	/**
	 * Get the CommentIQ comment details.
	 *
	 * @since 0.1.0
	 * @return array CommentIQ details if found, otherwise empty.
	 */
	public function get_comment_iq_details();

	/**
	 * Set the CommentIQ comment ID.
	 *
	 * @since 0.1.0
	 * @param int $id CommentIQ comment ID.
	 * @return $this
	 */
	public function set_comment_iq_id( $id );

	/**
	 * Set the CommentIQ article details.
	 *
	 * @since 0.1.0
	 * @param array $details CommentIQ details array.
	 * @return $this
	 */
	public function set_comment_iq_details( $details );

}