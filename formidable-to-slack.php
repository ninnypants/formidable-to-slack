<?php
/*
Plugin Name: Formidable to Slack
Plugin URI: http://ninnypants.com/plugins/
Description: Invite formidable form submissions to slack
Version: 1.0.2
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
	/**
	 * Label text for the email field.
	 *
	 * Used to determine which field should be used as the email address.
	 * Defaults to 'Email Address', and must be set as the field label manualy.
	 *
	 * @var string
	 */
	protected $email_field_label;

	/**
	 * Label text for the first name field.
	 *
	 * Used to determine which field should be used for first_name. Defaults
	 * to 'First Name', and must be set as the field label manualy.
	 *
	 * @var string
	 */
	protected $first_name_field_label;

	/**
	 * Label text for the last name field.
	 *
	 * Used to determine which field should be used for last_name. Defaults
	 *  to 'Last Name', and must be set as the field label manualy.
	 *
	 * @var string
	 */
	protected $last_name_field_label;

	/**
	 * API key for Slack integration.
	 *
	 * @var string
	 */
	protected $slack_key;

	/**
	 * ID of the form to send Slack invites from.
	 *
	 * @var int
	 */
	protected $slack_form;

	/**
	 * Attach actions
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'frm_style_general_settings', array( $this, 'settings_fields' ) );
		add_action( 'frm_update_settings', array( $this, 'save_settings' ) );
		add_action( 'frm_after_create_entry', array( $this, 'send_invite' ), 10, 2 );
	}

	/**
	 * Initialize settings
	 *
	 * @return void
	 */
	public function init () {
		$this->email_field_label      = get_option( 'fts_email_field_label', 'Email Address' );
		$this->first_name_field_label = get_option( 'fts_first_name_field_label', 'First Name' );
		$this->last_name_field_label  = get_option( 'fts_last_name_field_label', 'Last Name' );
		$this->slack_url              = get_option( 'fts_slack_url', '' );
		$this->slack_token            = get_option( 'fts_slack_token', '' );
		$this->slack_form             = get_option( 'fts_slack_form', '' );
	}

	/**
	 * Output settings fields
	 *
	 * @return void
	 */
	public function settings_fields() {
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

	/**
	 * Save Slack settings passed from Formidable
	 *
	 * @param  array $params Required. $_POST values passed through Formidable.
	 * @return void
	 */
	public function save_settings( $params ) {
		if ( isset( $params['fts_slack_url'] ) && ! empty( $params['fts_slack_url'] ) ) {
			$this->slack_url = trim( sanitize_text_field( $params['fts_slack_url'] ) );
			update_option( 'fts_slack_url', $this->slack_url );
		}
		if ( isset( $params['fts_slack_token'] ) && ! empty( $params['fts_slack_token'] ) ) {
			$this->slack_token = trim( sanitize_text_field( $params['fts_slack_token'] ) );
			update_option( 'fts_slack_token', $this->slack_token );
		}

		if ( isset( $params['fts_slack_form'] ) && ! empty( $params['fts_slack_form'] ) ) {
			$this->slack_form = (int) $params['fts_slack_form'];
			update_option( 'fts_slack_form', $this->slack_form );
		}

		if ( isset( $params['fts_email_field_label'] ) && ! empty( $params['fts_email_field_label'] ) ) {
			$this->email_field_label = trim( sanitize_text_field( $params['fts_email_field_label'] ) );
			update_option( 'fts_email_field_label', $this->email_field_label );
		}

		if ( isset( $params['fts_first_name_field_label'] ) && ! empty( $params['fts_first_name_field_label'] ) ) {
			$this->first_name_field_label = trim( sanitize_text_field( $params['fts_first_name_field_label'] ) );
			update_option( 'fts_first_name_field_label', $this->first_name_field_label );
		}

		if ( isset( $params['fts_last_name_field_label'] ) && ! empty( $params['fts_last_name_field_label'] ) ) {
			$this->last_name_field_label = trim( sanitize_text_field( $params['fts_last_name_field_label'] ) );
			update_option( 'fts_last_name_field_label', $this->last_name_field_label );
		}
	}

	/**
	 * Test the form to see if it should send the slack invite, and send it.
	 *
	 * @param  int $entry_id Required. Form entry ID.
	 * @param  int $form_id  Required. Submitted form ID.
	 * @return void
	 */
	public function send_invite( $entry_id, $form_id ) {
		if ( $form_id != $this->slack_form ) {
			return;
		}

		// Pull form fields.
		$fields = FrmField::get_all_for_form( $form_id );

		$request_args = array(
			'email'      => '',
			'first_name' => '',
			'last_name'  => '',
			'token'      => $this->slack_token,
			'channels'   => '',
			'set_active' => 'true',
			'_attempts'  => 1,
		);
		// loop fields and pull out the First Name, Last Name, and Email Address fields.
		foreach ( $fields as $field ) {
			// Check email.
			if ( $this->email_field_label === $field->name ) {
				$request_args['email'] = FrmEntryMeta::get_entry_meta_by_field( $entry_id, $field->id );
			} // First name.
			else if ( $this->first_name_field_label === $field->name ) {
				$request_args['first_name'] = FrmEntryMeta::get_entry_meta_by_field( $entry_id, $field->id );
			} // Last name.
			else if ( $this->last_name_field_label === $field->name ) {
				$request_args['last_name'] = FrmEntryMeta::get_entry_meta_by_field( $entry_id, $field->id );
			}
		}

		// bail if the email doesn't validate or we don't have a slack url
		if ( ! is_email( $request_args['email'] ) || empty( $this->slack_url ) ) {
			return;
		}

		$url = sprintf( 'https://%s.slack.com/api/users.admin.invite?t=%d', $this->slack_url, $this->get_timestamp() );

		// send off async request and move along
		wp_remote_post( $url, array(
			'blocking' => false,
			'body'     => $request_args,
		) );
	}

	/**
	 * Return current timestamp in America/los_angeles timezone
	 *
	 * @return int Current timestamp.
	 */
	protected function get_timestamp() {
		$date = new DateTime( 'NOW', new DateTimeZone( 'America/Los_Angeles' ) );
		return $date->getTimestamp();
	}
}
