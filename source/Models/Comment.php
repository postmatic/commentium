<?php
namespace Postmatic\Commentium\Models;

use Prompt_Comment;

/**
 * Comment with Comment IQ enhancements.
 *
 * @since 0.1.0
 */
class Comment extends Prompt_Comment implements Comment_IQ_Comment {

	/** @var string Comment ID meta key matching that used in Elevated Comments */
	protected static $comment_iq_id_meta_key = 'comment_iq_comment_id';
	/** @var string Comment details meta key matching that used in Elevated Comments */
	protected static $comment_iq_details_meta_key = 'comment_iq_comment_details';

	/**
	 * Get the Comment IQ ID from WordPress metadata.
	 *
	 * @since 0.1.0
	 * @return int|null
	 */
	public function get_comment_iq_id() {
		$id = get_comment_meta( $this->id(), static::$comment_iq_id_meta_key, true );
		return $id ? intval( $id ) : null;
	}

	/**
	 * Get the CommentIQ comment body.
	 *
	 * @since 0.1.0
	 * @return string CommentIQ body.
	 */
	public function get_comment_iq_body() {
		return $this->get_wp_comment()->comment_content;
	}

	/**
	 * Get the CommentIQ comment date .
	 *
	 * @since 0.1.0
	 * @return string CommentIQ body.
	 */
	public function get_comment_iq_date() {
		return $this->get_wp_comment()->comment_date_gmt;
	}

	/**
	 * Get the CommentIQ comment date .
	 *
	 * @since 0.1.0
	 * @return string CommentIQ body.
	 */
	public function get_comment_iq_username() {
		return $this->get_wp_comment()->comment_author;
	}

	/**
	 * Get the Comment IQ details from WordPress metadata.
	 *
	 * @since 0.1.0
	 * @return array
	 */
	public function get_comment_iq_details() {
		$details = get_comment_meta( $this->id(), static::$comment_iq_details_meta_key, true );
		return $details ? $details : array();
	}

	/**
	 * Set the Comment IQ ID.
	 *
	 * @since 0.1.0
	 * @param int $id The Comment IQ ID.
	 * @return $this
	 */
	public function set_comment_iq_id( $id ) {
		update_comment_meta( $this->id(), static::$comment_iq_id_meta_key, intval( $id ) );
		return $this;
	}

	/**
	 * Set the Comment IQ details.
	 *
	 * @since 0.1.0
	 * @param array $details The Comment IQ details.
	 * @return $this
	 */
	public function set_comment_iq_details( $details ) {
		$details = is_array( $details ) ? $details : array();
		update_comment_meta( $this->id(), static::$comment_iq_details_meta_key, $details);
		return $this;
	}
}
