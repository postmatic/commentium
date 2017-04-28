<?php
namespace Postmatic\Commentium\Filters;

use Postmatic\Commentium\Admin\Options_Tabs;

use Prompt_Core;

/**
 * Filter basic options page items.
 * @since 1.0.1
 */
class Options_Page {

	/**
	 * Add premium tabs to the basic ones in hard-coded order.
	 *
	 * @since 1.0.1
	 * @param \Prompt_Admin_Options_Tab[] $tabs
	 * @return \Prompt_Admin_Options_Tab[]
	 */
    public static function tabs( $tabs ) {
        $new_tabs = array();

		foreach( $tabs as $tab ) {
			$new_tabs[] = $tab;
			static::maybe_append_tab( $new_tabs );
		}

		return $new_tabs;
    }

    /**
	 * Look at the last tab in a list and decide whether to replace it or add our tabs after it. May change the passed array.
	 * @since 1.0.1
	 * @param \Prompt_Admin_Options_Tab[] $tabs
	 */
	protected static function maybe_append_tab( &$tabs ) {

		$last_tab = $tabs[count($tabs)-1];

		if ( 'Prompt_Admin_Comment_Options_Tab' === get_class( $last_tab ) and Prompt_Core::$options->is_api_transport() ) {
			array_pop( $tabs );
			$tabs[] = new Options_Tabs\Comments( Prompt_Core::$options );
		}

	}

}