<?php
/*
Plugin Name: Formidable to Slack
Plugin URI: http://ninnypants.com/plugins/
Description: Invite formidable form submissions to slack
Version: 0.0.1
Author: ninnypants
Author URI: http://ninnypants.com
License: GPL2

Copyright 2015  Tyrel Kelsey  (email : tyrel@ninnypants.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

$formidable_to_slack = new FormidableToSlack;
class FormidableToSlack {
	protected $email_field_label;
	protected $first_name_field_label;
	protected $last_name_field_label;
	protected $slack_key;
	protected $slack_form;

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'frm_style_general_settings', array( $this, 'settings_fields' ) );
		add_action( 'frm_update_settings', array( $this, 'save_settings' ) );
		add_action( 'frm_after_create_entry', array( $this, 'send_invite' ), 10, 2 );
	}

	public function init () {
		$this->email_field_label = get_option( 'fts_email_field_label', 'Email Address' );
		$this->first_name_field_label = get_option( 'fts_first_name_field_label', 'First Name' );
		$this->last_name_field_label = get_option( 'fts_last_name_field_label', 'Last Name' );
		$this->slack_url = get_option( 'fts_slack_url', '' );
		$this->slack_token = get_option( 'fts_slack_token', '' );
		$this->slack_form = get_option( 'fts_slack_form', '' );
	}

	public function settings_fields( $form_settings ) {
		?>
		<div class="clear"></div>
		<div class="menu-settings">
			<h3 class="frm_no_bg"><?php _e( 'Slack to Formidable', 'formidable-to-slack' ); ?></h3>
		</div>

		<p>
			<label class="frm_left_label" for="fts_slack_url"><?php _e( 'Slack URL', 'formidable-to-slack' ); ?></label>
			<input type="text" name="fts_slack_url" id="fts_slack_url" value="<?php echo esc_attr( $this->slack_url ); ?>" />.slack.com
		</p>
		<p>
			<label class="frm_left_label" for="fts_slack_token"><?php _e( 'Slack Token', 'formidable-to-slack' ); ?></label>
			<input type="text" name="fts_slack_token" id="fts_slack_token" value="<?php echo esc_attr( $this->slack_token ); ?>" />
		</p>
		<p>
			<label class="frm_left_label" for="fts_slack_form"><?php _e( 'Slack Form', 'formidable-to-slack' ); ?></label>
			<?php FrmFormsHelper::forms_dropdown( 'fts_slack_form', $this->slack_form ); ?>
		</p>
		<p>
			<label class="frm_left_label" for="fts_email_field_label"><?php _e( 'Email Address Label', 'formidable-to-slack' ); ?></label>
			<input type="text" name="fts_email_field_label" id="fts_email_field_label" value="<?php echo esc_attr( $this->email_field_label ); ?>" />
		</p>
		<p>
			<label class="frm_left_label" for="fts_first_name_field_label"><?php _e( 'First Name Label', 'formidable-to-slack' ); ?></label>
			<input type="text" name="fts_first_name_field_label" id="fts_first_name_field_label" value="<?php echo esc_attr( $this->first_name_field_label ); ?>" />
		</p>
		<p>
			<label class="frm_left_label" for="fts_last_name_field_label"><?php _e( 'Last Name Label', 'formidable-to-slack' ); ?></label>
			<input type="text" name="fts_last_name_field_label" id="fts_last_name_field_label" value="<?php echo esc_attr( $this->last_name_field_label ); ?>" />
		</p>
		<?php
	}

	public function save_settings( $params ) {
		if ( isset( $params['fts_slack_url'] ) && ! empty( $params['fts_slack_url'] ) ) {
			update_option( 'fts_slack_url', $params['fts_slack_url'] );
		}
		if ( isset( $params['fts_slack_token'] ) && ! empty( $params['fts_slack_token'] ) ) {
			update_option( 'fts_slack_token', $params['fts_slack_token'] );
		}

		if ( isset( $params['fts_slack_form'] ) && ! empty( $params['fts_slack_form'] ) ) {
			update_option( 'fts_slack_form', $params['fts_slack_form'] );
		}

		if ( isset( $params['fts_email_field_label'] ) && ! empty( $params['fts_email_field_label'] ) ) {
			update_option( 'fts_email_field_label', $params['fts_email_field_label'] );
		}

		if ( isset( $params['fts_first_name_field_label'] ) && ! empty( $params['fts_first_name_field_label'] ) ) {
			update_option( 'fts_first_name_field_label', $params['fts_first_name_field_label'] );
		}

		if ( isset( $params['fts_last_name_field_label'] ) && ! empty( $params['fts_last_name_field_label'] ) ) {
			update_option( 'fts_last_name_field_label', $params['fts_last_name_field_label'] );
		}
	}

	/**
	 * Test the form to see if it should send the slack invite, and send it.
	 *
	 * @global FrmEntry $frm_entry
	 * @global FrmEntryMeta $frm_entry_meta
	 * @global FrmField $frm_field
	 * @param  int $entry_id Required. Form entry ID.
	 * @param  int $form_id  Required. Submitted form ID.
	 * @return void
	 */
	public function send_invite( $entry_id, $form_id ) {
		global $frm_entry, $frm_entry_meta, $frm_field;
		$fields = $frm_field->getAll( array( 'form_id' => $form_id ) );

		$request_args = array(
			'email' => '',
			'first_name' => '',
			'last_name' => '',
			'token' => $this->slack_token,
			'channels' => '',
			'set_active' => 'true',
			'_attempts' => 1,
		);
		// loop fields and pull out the First Name, Last Name, and Email Address fields
		foreach ( $fields as $field ) {
			// check email
			if ( $this->email_field_label === $field->name ) {
				$request_args['email'] = $frm_entry_meta->get_entry_meta_by_field( $entry_id, $field->id );
			} // first name
			else if ( $this->first_name_field_label === $field->name ) {
				$request_args['first_name'] = $frm_entry_meta->get_entry_meta_by_field( $entry_id, $field->id );
			} // last name
			else if ( $this->last_name_field_label === $field->name ) {
				$request_args['last_name'] = $frm_entry_meta->get_entry_meta_by_field( $entry_id, $field->id );
			}
		}

		// bail if the email doesn't validate or we don't have a slack url
		if ( ! is_email( $request_args['email'] ) || empty( $this->slack_url ) ) {
			return;
		}

		$url = sprintf( 'https://%s.slack.com/api/users.admin.invite?t=%d', $this->slack_url, ( new DateTime( 'NOW', new DateTimeZone( 'America/Los_Angeles' ) ) )->getTimestamp() );

		wp_remote_post( $url, array(
			'blocking' => false,
			'body' => $request_args,
		) );
	}
}