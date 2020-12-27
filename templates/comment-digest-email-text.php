<?php
/**
 * Template variables in scope:
 * @var array $comments Comments to be included in the digest
 * @var Postmatic\Commentium\Lists\Posts\Post $post_list
 */
?>

<p>
	<?php
	$permalink      = get_permalink( $post_list->id() );
	$comments_count = wp_count_comments( $post_list->id() )->approved - count( $comments );
	echo wp_kses_post(
		apply_filters(
			'replyable/template/comment_digest/text_header',
			sprintf(
				__( 'There were <a href="%1$s">%2$d comments</a> previous to these. Here\'s the latest discussion:', 'Postmatic' ),
				$permalink . '#comments',
				$comments_count
			),
			$permalink,
			$comments_count,
			$post_list->id(),
			$comments,
		)
	);
	?>
</p>

<div>
	<?php
	wp_list_comments( array(
		'callback' => array( 'Prompt_Email_Comment_Rendering', 'render_text' ),
		'end-callback' => '__return_empty_string',
		'style' => 'div',
	), $comments );
	?>
</div>

<p>
	<?php
	echo wp_kses_post(
		apply_filters( //phpcs:ignore
			'replyable/template/comment_digest/text_reply',
			__( '* Reply to this email to add a new comment. *', 'Postmatic' ),
			$post_list->id(),
			$comments
		)
	);
	?>
</p>
