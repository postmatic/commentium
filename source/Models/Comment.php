<?php
namespace Postmatic\Commentium\Models;

use Prompt_Comment;

class Comment extends Prompt_Comment implements Comment_IQ_Comment {

	/** @var string Comment ID meta key matching that used in Elevated Comments */
	protected static $comment_iq_id_meta_key = 'comment_iq_comment_id';
	/** @var string Comment details meta key matching that used in Elevated Comments */
	protected static $comment_iq_details_meta_key = 'comment_iq_comment_details';

	public function get_comment_iq_comment_id() {
		$id = get_comment_meta( $this->id(), static::$comment_iq_id_meta_key, true );
		return $id ? intval( $id ) : null;
	}

	public function get_comment_iq_comment_details() {
		$details = get_comment_meta( $this->id(), static::$comment_iq_details_meta_key, true );
		return $details ? $details : array();
	}

	public function set_comment_iq_comment_id( $id ) {
		update_comment_meta( $this->id(), static::$comment_iq_id_meta_key, intval( $id ) );
		return $this;
	}

	public function set_comment_iq_comment_details( $details ) {
		$details = is_array( $details ) ? $details : array();
		update_comment_meta( $this->id(), static::$comment_iq_details_meta_key, $details);
		return $this;
	}
}
