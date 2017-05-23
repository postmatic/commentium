<?php
namespace Postmatic\Commentium\Admin\Options_Tabs;

use Prompt_Admin_Comment_Options_Tab;
use Prompt_Enum_Message_Types;

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
        $checkbox_data = $this->validate_checkbox_fields( $new_data, $old_data, array(
            'enable_replies_only',
            'auto_subscribe_authors'
        ) );

        $valid_data['enable_replies_only'] = $checkbox_data['enable_replies_only'];
        $valid_data['auto_subscribe_authors'] = $checkbox_data['auto_subscribe_authors'];

        if ( $checkbox_data['enable_replies_only'] && !$old_data['enable_replies_only'] ) {
            $valid_data['comment_flood_control_trigger_count'] = 0;
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
			'How many comments during a 14 hour period will trigger comment digests to be sent instead of individual comments? If you would like to send only direct replies and a daily digest of new comment activity set this to 1.',
			'commentium'
		) . html( 'p',
			__(
				'Postmatic automatically combines comment notifications on posts that go viral so your users do not get too many emails. Setting the trigger to 3 comments is good for most sites.',
				'commentium'
			)
		);

		$replies_only_entry = array(
		    array(
                'title' => __( 'Send Replies Only', 'postmatic-premium' ),
                'type' => 'checkbox',
                'name' => 'enable_replies_only',
                'desc' => __(
                    'Only send notifications to comment authors when someone replies to their comment. If you enable this you may want to check the language in the comment form opt-in text (above).',
                    'commentium'
                )
            )
        );

        array_splice( $entries, 2, 0, $replies_only_entry );

		if ( $this->is_moderation_enabled_but_not_post_delivery() ) {
		    $author_subscribe_entry = array(
		        array(
                    'title' => __( 'Author Subscriptions', 'commentium' ),
                    'type' => 'checkbox',
                    'name' => 'auto_subscribe_authors',
                    'desc' => __(
                            'Subscribe authors to comments on their own posts.<small>(Recommended)</small>',
                            'commentium'
                        ) . html( 'p',
                            __(
                                'This will automatically subscribe post authors to new comment notifications on their posts. This works well to keep the author up to date with the latest comments and discussion.',
                                'commentium'
                            )
                        ),
                )
			);

		    array_splice( $entries, 4, 0, $author_subscribe_entry );
        }

		return $entries;
	}

    /**
     * @since 1.0.2
     * @return bool
     */
	protected function is_moderation_enabled_but_not_post_delivery() {
        $enabled_message_types = $this->options->get( 'enabled_message_types' );

        return in_array( Prompt_Enum_Message_Types::COMMENT_MODERATION, $enabled_message_types, true ) &&
               !in_array( Prompt_Enum_Message_Types::POST, $enabled_message_types, true );
    }

}
