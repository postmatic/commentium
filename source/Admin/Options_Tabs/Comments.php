<?php
namespace Postmatic\Commentium\Admin\Options_Tabs;

use Prompt_Admin_Comment_Options_Tab;

class Comments extends Prompt_Admin_Comment_Options_Tab {

	protected function table_entries() {
		$entries = parent::table_entries();

		if ( ! $this->options->is_api_transport() ) {
			return $entries;
		}

		$entries[2]['title'] = __( 'Comment digests', 'postmatic-premium' );
		$entries[2]['desc'] = __(
			'How many comments during a 14 hour period will trigger comment digests to be sent instead of individual comments? There is a minimum of 3.',
			'postmatic-premium'
		) . html( 'p',
			__(
				'Postmatic automatically combines comment notifications on posts that go viral so your users do not get too many emails. Setting the trigger to 3 comments is good for most sites.',
				'postmatic-premium'
			)
		);
		
		return $entries;
	}
}
