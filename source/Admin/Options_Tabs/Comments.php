<?php
namespace Postmatic\Commentium\Admin\Options_Tabs;

use Prompt_Admin_Comment_Options_Tab;

/**
 * Commentium comment option customizations.
 */
class Comments extends Prompt_Admin_Comment_Options_Tab {

    /**
     * Render Commentium comment options.
     *
     * @since 1.0.1
     * @return string
     */
    public function render()
    {
        $script = $this->options->is_api_transport() ? $this->render_script() : '';
        return parent::render() . $script;
    }

    /**
     * Validate Commentium comment options.
     *
     * @since 1.0.1
     * @param array $new_data
     * @param array $old_data
     *
     * @return array
     */
    public function validate($new_data, $old_data)
    {
        $checkbox_data = $this->validate_checkbox_fields( $new_data, $old_data, [ 'enable_replies_only'] );

        $valid_data['enable_replies_only'] = $checkbox_data['enable_replies_only'];

        if ( $checkbox_data['enable_replies_only'] && !$old_data['enable_replies_only'] ) {
            $valid_data['comment_flood_control_trigger_count'] = 1;
        }

        $valid_data = array_merge( parent::validate($new_data, $old_data), $valid_data );
        return $valid_data;
    }

    /**
     * Render Commentium comment options javascript.
     *
     * @since 1.0.1
     * @return string
     */
    protected function render_script() {
        return '
            <script>
            jQuery(function( $ ) {
                var $digest_row = $("#prompt-settings-comment-delivery table tbody tr").eq(3);
                var $replies_only_box = $("input[name=enable_replies_only]").on("change",show_digest_row);
                show_digest_row();
                
                function show_digest_row() {
                    if ( $replies_only_box.is(":checked") ) {
                        $digest_row.hide();
                    } else {
                        $digest_row.show();
                    }
                }
            });
            </script>
        ';
    }

    /**
     * Render commentium comment options.
     *
     * @since 1.0.1
     * @return array
     */
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
