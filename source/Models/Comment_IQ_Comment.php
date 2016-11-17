<?php
namespace Postmatic\Commentium\Models;

/**
 * Interface for representation of a commentIQ comment.
 *
 * @since 0.1.0
 */
interface Comment_IQ_Comment {

	/**
	 * Get the associated CommentIQ comment ID.
	 *
	 * @since 0.1.0
	 * @return int|null CommentIQ ID if found, otherwise null.
	 */
	public function get_comment_iq_comment_id();

	/**
	 * Get the associated CommentIQ comment details.
	 *
	 * @since 0.1.0
	 * @return array CommentIQ details if found, otherwise empty.
	 */
	public function get_comment_iq_comment_details();

	/**
	 * Set the associated CommentIQ comment ID.
	 *
	 * @since 0.1.0
	 * @param int $id CommentIQ comment ID.
	 * @return $this
	 */
	public function set_comment_iq_comment_id( $id );

	/**
	 * Set the associated CommentIQ article details.
	 *
	 * @since 0.1.0
	 * @param array $details CommentIQ details array.
	 * @return $this
	 */
	public function set_comment_iq_comment_details( $details );

}