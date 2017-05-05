<?php
namespace Postmatic\Commentium\Admin\Options_Tabs;

use Prompt_Admin_Comment_Options_Tab;

class Comments extends Prompt_Admin_Comment_Options_Tab {

    public function validate($new_data, $old_data)
    {
        $valid_data = $this->validate_checkbox_fields( $new_data, $old_data, [ 'enable_replies_only'] );

        return array_merge( parent::validate($new_data, $old_data), $valid_data );
    }

    protected function table_entries() {
		$entries = parent::table_entries();

		if ( ! $this->options->is_api_transport() ) {
			return $entries;
		}

		$entries[2]['title'] = __( 'Comment digests', 'commentium' );
		$entries[2]['desc'] = __(
			'How many comments during a 14 hour period will trigger comment digests to be sent instead of individual comments? There is a minimum of 3.',
			'commentium'
		) . html( 'p',
			__(
				'Postmatic automatically combines comment notifications on posts that go viral so your users do not get too many emails. Setting the trigger to 3 comments is good for most sites.',
				'commentium'
			)
		);

		$replies_only_entry = [
		    [
                'title' => __( 'Replies Only', 'postmatic-premium' ),
                'type' => 'checkbox',
                'name' => 'enable_replies_only',
                'desc' => __(
                    'Only send notifications to comment authors when someone replies to their comment.',
                    'commentium'
                )
            ]
        ];

		array_splice( $entries, 2, 0, $replies_only_entry );

		return $entries;
	}
}
