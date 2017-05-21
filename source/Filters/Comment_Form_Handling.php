<?php
namespace Postmatic\Commentium\Filters;

use Prompt_Options;
use Prompt_Core;

/**
 * Comment form handling filters
 *
 * @since 1.0.1
 */
class Comment_Form_Handling {

    /**
     * Filter tooltip text.
     *
     * @since 1.0.1
     *
     * @param string $text
     * @param int $post_id
     * @param Prompt_Options|null $options
     *
     * @return string
     */
    public static function tooltip( $text, $post_id = 0, Prompt_Options $options = null ) {
        $options = $options ?: Prompt_Core::$options;

        if ( ! $options->get( 'enable_replies_only' ) ) {
            return $text;
        }

        return __( 'Receive replies to your comment via email', 'postmatic-premium' );
    }
}
