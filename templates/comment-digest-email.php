<?php
/**
 * Template variables in scope: * @var array $comments Comments to be included in the digest
 * @var int $new_comment_count
 * @var array $new_comments
 * @var array $parent_comments
 * @var Postmatic\Commentium\Lists\Posts\Post $post_list
 * @var string $subscribed_post_title
 * @var string $subscribed_post_title_link
 * @var string $subscribed_post_author_name
 */
?>

<div class="padded comment-digest-intro">
	<?php echo get_the_post_thumbnail( $post_list->id(), 'thumb' ); ?>
	<h4>
		<?php
		printf(
			_n(
				'There is %d new comment on <em>%s</em>.',
				'There are %d new comments on <em>%s</em>.', 
				$new_comment_count,
				'Postmatic' 
			),
			$new_comment_count,
			$subscribed_post_title
		);
		?>
	</h4>
</div>

	<div id="comments" class="comment-digest padded">
		<?php
		wp_list_comments( array(
			'callback' => array( 'Prompt_Email_Comment_Rendering', 'render' ),
			'style' => 'div',
		), array_merge( $parent_comments, $comments ) );
		?>
	</div>

	<div class="reply-prompt padded">
		<img src="<?php echo Prompt_Core::$url_path . '/media/reply-comment-2x.png'; ?>" width="30" height="30"
		     align="left" style="float: left; margin-right: 10px;"/>
		<p class="reply">
			<?php
			_e( 'Reply to this email to add a comment. Your email address will not be shown.', 'Postmatic' );
			?><br/>
			<small>
				<?php
				printf(
					__(
						'<strong>Please note</strong>: Your reply will be published publicly and immediately on %s.',
						'Postmatic'
					),
					get_bloginfo( 'name' )
				);
				?>
			</small>
		</p>
	</div>

	<div class="context padded">
		<h3><?php _e( 'Here\'s a recap of this post and conversation:', 'Postmatic' ); ?></h3>

		<p>
			<?php
			/* translators: %1$s is post title, %2$s date, %3$s time, %4$s author */
			printf(
				__( '%1$s was published on %2$s by %4$s.', 'Postmatic' ),
				$subscribed_post_title_link,
				get_the_date( '', $post_list->get_wp_post() ),
				get_the_time( '', $post_list->get_wp_post() ),
				$subscribed_post_author_name
			);

			/* translators: %s is post modified date */
			if ( get_the_modified_time( 'U', $post_list->get_wp_post() ) > get_the_time( 'U', $post_list->get_wp_post() ) ) {
				printf( ' ' );
				printf(
					__( '(Last updated %s)', 'Postmatic' ),
					get_the_modified_date( '', $post_list->get_wp_post() )
				);
			}
			?>
		</p>
		<?php echo get_the_post_thumbnail( $post_list->id(), 'medium' ); ?>
		<p class="excerpt"><?php echo $post_list->get_excerpt(); ?></p>
	</div>