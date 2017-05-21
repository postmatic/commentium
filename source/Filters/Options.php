<?php
namespace Postmatic\Commentium\Filters;

/**
 * Filter option values.
 */
class Options {

    /**
     * Add an option to enable replies only.
     *
     * @param array $options
     * @return array
     */
    public static function default_options( array $options = array() ) {
        $options['enable_replies_only'] = false;
        return $options;
    }
}
