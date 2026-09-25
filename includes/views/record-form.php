<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( GIMAT_DASH_CAP ) ) {
	wp_die( esc_html__( 'You do not have permission to view this page.', 'gimat-dashboard' ) );
}

$events    = Gimat_Events::get_all();
$is_edit   = isset( $_GET['id'] ) && (int) $_GET['id'] > 0;
$record    = null;
$custom_fields = array();

if ( $is_edit ) {
	$record = Gimat_Records::get( (int) $_GET['id'] );
	if ( ! $record ) {
		wp_die( esc_html__( 'Record not found.', 'gimat-dashboard' ), '', array( 'response' => 404 ) );
	}
	if ( ! empty( $record->custom_fields ) ) {
		$decoded = json_decode( $record->custom_fields, true );
		if ( is_array( $decoded ) ) {
			$custom_fields = $decoded;
		}
	}
}

$sub_event_suggestions = Gimat_Records::distinct_values( 'sub_event_name' );
$org_suggestions        = Gimat_Records::distinct_values( 'organization' );

$val = function ( $field, $default = '' ) use ( $record ) {
	return $record ? esc_attr( $record->$field ) : esc_attr( $default );
};

$gimat_page_title  = $is_edit ? __( 'Edit Training Record', 'gimat-dashboard' ) : __( 'Add Training Record', 'gimat-dashboard' );
$gimat_page_sub    = $is_edit ? __( 'Update the details of this training session.', 'gimat-dashboard' ) : __( 'Log a new training session with its participant breakdown.', 'gimat-dashboard' );
$gimat_page_icon   = $is_edit ? 'dashicons-edit' : 'dashicons-plus-alt2';
$gimat_notice_html = $this->notice_html();
?>
<div class="wrap gimat-wrap">
	<?php require __DIR__ . '/partials/brand-header.php'; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gimat-record-form">
		<input type="hidden" name="action" value="<?php echo esc_attr( $is_edit ? 'gimat_update_record' : 'gimat_add_record' ); ?>" />
		<?php if ( $is_edit ) : ?>
			<input type="hidden" name="record_id" value="<?php echo esc_attr( $record->id ); ?>" />
			<?php wp_nonce_field( 'gimat_update_record' ); ?>
		<?php else : ?>
			<?php wp_nonce_field( 'gimat_add_record' ); ?>
		<?php endif; ?>

		<div class="gimat-card">
			<div class="gimat-form-grid">
				<div class="gimat-field">
					<label for="event_id"><?php esc_html_e( 'Event', 'gimat-dashboard' ); ?> <span class="req">*</span></label>
					<select id="event_id" name="event_id" required>
						<option value=""><?php esc_html_e( 'Select an event…', 'gimat-dashboard' ); ?></option>
						<?php foreach ( $events as $evt ) : ?>
							<option value="<?php echo esc_attr( $evt->id ); ?>" <?php selected( $record ? $record->event_id : '', $evt->id ); ?>><?php echo esc_html( $evt->event_name ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( empty( $events ) ) : ?>
						<p class="gimat-hint"><?php esc_html_e( 'No events yet — create one first.', 'gimat-dashboard' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=gimat-events' ) ); ?>"><?php esc_html_e( 'Add an event →', 'gimat-dashboard' ); ?></a></p>
					<?php endif; ?>
				</div>

				<div class="gimat-field gimat-span-2">
					<label for="sub_event_name"><?php esc_html_e( 'Sub Event Name', 'gimat-dashboard' ); ?> <span class="req">*</span></label>
					<input type="text" id="sub_event_name" name="sub_event_name" list="sub-events" maxlength="200" required value="<?php echo $val( 'sub_event_name' ); ?>" />
					<datalist id="sub-events">
						<?php foreach ( $sub_event_suggestions as $s ) : ?><option value="<?php echo esc_attr( $s ); ?>"></option><?php endforeach; ?>
					</datalist>
				</div>

				<div class="gimat-field gimat-span-2">
					<label for="organization"><?php esc_html_e( 'Organization', 'gimat-dashboard' ); ?> <span class="req">*</span></label>
					<input type="text" id="organization" name="organization" list="orgs" maxlength="200" required value="<?php echo $val( 'organization' ); ?>" />
					<datalist id="orgs">
						<?php foreach ( $org_suggestions as $s ) : ?><option value="<?php echo esc_attr( $s ); ?>"></option><?php endforeach; ?>
					</datalist>
				</div>

				<div class="gimat-field">
					<label for="duration"><?php esc_html_e( 'Duration', 'gimat-dashboard' ); ?> <span class="req">*</span></label>
					<input type="text" id="duration" name="duration" required placeholder="<?php esc_attr_e( 'e.g. 3 days', 'gimat-dashboard' ); ?>" value="<?php echo $val( 'duration' ); ?>" />
				</div>

				<div class="gimat-field">
					<label for="start_date"><?php esc_html_e( 'Start Date', 'gimat-dashboard' ); ?> <span class="req">*</span></label>
					<input type="date" id="start_date" name="start_date" required value="<?php echo $val( 'start_date' ); ?>" />
				</div>

				<div class="gimat-field">
					<label for="location"><?php esc_html_e( 'Location', 'gimat-dashboard' ); ?> <span class="req">*</span></label>
					<input type="text" id="location" name="location" required value="<?php echo $val( 'location' ); ?>" />
				</div>

				<div class="gimat-field gimat-span-2">
					<label for="facilitator"><?php esc_html_e( 'Facilitator(s)', 'gimat-dashboard' ); ?> <span class="req">*</span></label>
					<input type="text" id="facilitator" name="facilitator" required value="<?php echo $val( 'facilitator' ); ?>" />
				</div>
			</div>

			<h3 class="gimat-section-title"><?php esc_html_e( 'Gender', 'gimat-dashboard' ); ?></h3>
			<div class="gimat-form-grid gimat-grid-2">
				<div class="gimat-field">
					<label for="participant_male"><?php esc_html_e( 'Male', 'gimat-dashboard' ); ?></label>
					<input type="number" id="participant_male" name="participant_male" min="0" value="<?php echo $val( 'participant_male', 0 ); ?>" />
				</div>
				<div class="gimat-field">
					<label for="participant_female"><?php esc_html_e( 'Female', 'gimat-dashboard' ); ?></label>
					<input type="number" id="participant_female" name="participant_female" min="0" value="<?php echo $val( 'participant_female', 0 ); ?>" />
				</div>
			</div>

			<h3 class="gimat-section-title"><?php esc_html_e( 'Ethnicity', 'gimat-dashboard' ); ?></h3>
			<div class="gimat-form-grid gimat-grid-3">
				<div class="gimat-field">
					<label for="demographic_dalit"><?php esc_html_e( 'Dalit', 'gimat-dashboard' ); ?></label>
					<input type="number" id="demographic_dalit" name="demographic_dalit" min="0" value="<?php echo $val( 'demographic_dalit', 0 ); ?>" />
				</div>
				<div class="gimat-field">
					<label for="demographic_janajati"><?php esc_html_e( 'Janajati', 'gimat-dashboard' ); ?></label>
					<input type="number" id="demographic_janajati" name="demographic_janajati" min="0" value="<?php echo $val( 'demographic_janajati', 0 ); ?>" />
				</div>
				<div class="gimat-field">
					<label for="demographic_other"><?php esc_html_e( 'Other', 'gimat-dashboard' ); ?></label>
					<input type="number" id="demographic_other" name="demographic_other" min="0" value="<?php echo $val( 'demographic_other', 0 ); ?>" />
				</div>
			</div>

			<h3 class="gimat-section-title">
				<?php esc_html_e( 'Custom Fields', 'gimat-dashboard' ); ?>
				<button type="button" class="button button-small" id="gimat-add-custom-field"><span class="dashicons dashicons-plus-alt2"></span><?php esc_html_e( 'Add Field', 'gimat-dashboard' ); ?></button>
			</h3>
			<div id="gimat-custom-fields-wrap">
				<?php if ( empty( $custom_fields ) ) : ?>
					<p class="gimat-no-custom-fields" id="gimat-no-custom-fields-msg"><?php esc_html_e( 'No custom fields. Click "Add Field" to add one.', 'gimat-dashboard' ); ?></p>
				<?php else : foreach ( $custom_fields as $label => $value ) : ?>
					<div class="gimat-custom-field-row">
						<input type="text" name="custom_field_label[]" placeholder="<?php esc_attr_e( 'Label (e.g. Venue Type)', 'gimat-dashboard' ); ?>" value="<?php echo esc_attr( $label ); ?>" />
						<input type="text" name="custom_field_value[]" placeholder="<?php esc_attr_e( 'Value (e.g. Indoor)', 'gimat-dashboard' ); ?>" value="<?php echo esc_attr( $value ); ?>" />
						<button type="button" class="button-link-delete gimat-remove-custom-field">&times;</button>
					</div>
				<?php endforeach; endif; ?>
			</div>
			<template id="gimat-custom-field-template">
				<div class="gimat-custom-field-row">
					<input type="text" name="custom_field_label[]" placeholder="<?php esc_attr_e( 'Label (e.g. Venue Type)', 'gimat-dashboard' ); ?>" />
					<input type="text" name="custom_field_value[]" placeholder="<?php esc_attr_e( 'Value (e.g. Indoor)', 'gimat-dashboard' ); ?>" />
					<button type="button" class="button-link-delete gimat-remove-custom-field">&times;</button>
				</div>
			</template>

			<div class="gimat-form-actions">
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=gimat-records' ) ); ?>"><?php esc_html_e( 'Cancel', 'gimat-dashboard' ); ?></a>
				<button type="submit" class="button button-primary"><span class="dashicons dashicons-yes"></span><?php echo $is_edit ? esc_html__( 'Save Changes', 'gimat-dashboard' ) : esc_html__( 'Add Record', 'gimat-dashboard' ); ?></button>
			</div>
		</div>
	</form>
</div>
