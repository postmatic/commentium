<?php
/**
 * comment moderation text template
 *
 * @var object $comment
 * @var string $commenter_name
 * @var string $type
 * @var string $comment_author_domain
 * @var WP_Post $post
 */

?>
<h1>
<?php
printf(
	__( 'There is a new comment to moderate from %s on %s.', 'postmatic-premium' ),
	$commenter_name,
	get_the_title( $comment->comment_post_ID )
);
?>
</h1>

<div>
"<?php echo $comment->comment_content; ?>"
</div>

<h1><?php _e( 'Details about the comment', 'postmatic-premium' ); ?></h1>

- <?php  echo __( 'Author', 'postmatic-premium' ) . ': ' . $comment->comment_author; ?>

- <?php echo __( 'Email', 'postmatic-premium' ) . ': ' . $comment->comment_author_email; ?>

- <?php echo __( 'IP Address', 'postmatic-premium' ) . ': http://whois.arin.net/rest/ip/' . $comment->comment_author_IP; ?>

- <?php echo __( 'Domain', 'postmatic-premium' ) . ':  http://' . $comment_author_domain; ?>


<h2><?php _e( 'Approve?', 'postmatic-premium' ); ?></h2>

<p>
<?php _e( 'Reply to this email with a blank message or the word \'approve.\'. You can also approve and reply at the same time by just writing your reply.', 'postmatic-premium' ); ?>
</p>

<p>
<?php echo admin_url( 'comment.php?action=approve&c=' . $comment->comment_ID ); ?>
</p>

<h2><?php _e( 'Trash?', 'postmatic-premium' ); ?></h2>

<p>
<?php _e( 'Reply to this email with the word \'trash.\'', 'postmatic-premium' ); ?>
</p>

<p>
<?php echo admin_url( 'comment.php?action=trash&c=' . $comment->comment_ID ); ?>
</p>

<h2><?php _e( 'Spam?', 'postmatic-premium' ); ?></h2>

<p>
<?php _e( 'Reply to this email with a the word \'spam.\'', 'postmatic-premium' ); ?>
</p>

<p>
<?php echo admin_url( 'comment.php?action=spam&c=' . $comment->comment_ID ); ?>
</p>
