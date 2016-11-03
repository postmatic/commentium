<?php
/**
 * Template variables in scope:
 * @var array $comments Comments to be included in the digest
 * @var Postmatic\Commentium\Lists\Posts\Post $post_list
 */
?>

<p>
	<?php
	printf(
		__( 'There were <a href="%1$s">%2$d comments</a> previous to these. Here\'s the latest discussion:', 'Postmatic' ),
		get_permalink( $post_list->id() ) . '#comments',
		wp_count_comments( $post_list->id() )->approved - count( $comments )
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
	<?php _e( '* Reply to this email to add a new comment. *', 'Postmatic' ); ?>
</p>

