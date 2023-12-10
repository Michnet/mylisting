<?php
/**
 * Shows the `file` form field on listing forms.
 *
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_script( 'mylisting-repeater-ajax-file-upload' );

$og_files = ! empty( $field['value'] ) ? (array) $field['value'] : [];
$uploaded_files = ! empty( $field['value'] ) ? (array) $field['value'] : [];

$files = [];

if ( $uploaded_files ) {
	foreach ( $uploaded_files as $index => $value ) {
		if ( ! isset( $value['mylisting_accordion_photo'] ) ) {
			continue;
		}

		if ( ! empty( $value['mylisting_accordion_photo'] ) ) {
			$files[ $index ] = $value['mylisting_accordion_photo'];
		}
		
		unset( $uploaded_files[ $index ]['mylisting_accordion_photo'] );
	}
}

?>

<div class="resturant-menu-repeater" data-uploaded-list="<?php echo htmlspecialchars(json_encode(! empty( $files ) ? $files : []), ENT_QUOTES, 'UTF-8') ?>" data-list="<?php echo htmlspecialchars(json_encode( isset( $field['value'] ) ? $uploaded_files : []), ENT_QUOTES, 'UTF-8') ?>">
	<div class="repeater-list margin-top-10" data-repeater-list="<?php echo esc_attr( (isset($field['name']) ? $field['name'] : $key) ) ?>">
			<div data-repeater-item class="repeater-field-wrapper">
				<div class="field-type-file form-group">
					<div class="field ">	
						<label>Photo</label>
						<?php if ( is_admin() ) : ?>
							<div class="file-upload-field single-upload form-group-review-gallery">
								<div class="uploaded-files-list review-gallery-images">
									<div class="upload-file review-gallery-add listing-file-upload-input" data-name="mylisting_accordion_photo" data-multiple="">
										<i class="mi file_upload"></i>
										<div class="content"></div>
									</div>
									<input type="hidden" class="input-text outer-photo" name="mylisting_accordion_photo">
									<div class="job-manager-uploaded-files">
									</div>
								</div>
								<small class="description">
									<?php printf( _x( 'Maximum file size: %s.', 'Add listing form', 'my-listing' ), size_format( wp_max_upload_size() ) ); ?>
								</small>
							</div>
						<?php else : ?>
							<div class="file-upload-field single-upload form-group-review-gallery ajax-upload">
								<input
								type="file"
								class="input-text review-gallery-input wp-job-manager-file-upload"
								data-file_types="jpg|jpeg|jpe|gif|png|bmp|tiff|tif|webp|ico|heic"
								name="mylisting_accordion_photo"
								id="<?php echo esc_attr( (isset($field['name']) ? $field['name'] : $key) ) ?>_mylisting_accordion_photo"
								style="display: none;"
								>
								<div class="uploaded-files-list review-gallery-images">
									<label class="upload-file review-gallery-add" for="mylisting_accordion_photo">
										<i class="mi file_upload"></i>
										<div class="content"></div>
									</label>

									<div class="job-manager-uploaded-files">
									</div>
								</div>

								<small class="description">
									<?php printf( _x( 'Maximum file size: %s.', 'Add listing form', 'my-listing' ), size_format( wp_max_upload_size() ) ); ?>
								</small>
							</div>
						<?php endif; ?>
					</div>
				</div>


				<div class="fields-box row mx-0">
				<div class="form-group col-12">
					<label>Event day number
						<span>Which day of the event is this program item scheduled on?</span>
					</label>
					<input type="number" name="event_day"
						min="1"
						max="100"
						step="1"
						>
					<!-- <input required 
						type="text" 
						name="event_day" 
						placeholder="Enter day number as a digit e.g: 1"> -->
				</div>
				<div class="form-group col-12">
					<label>Title </label>
					<input required 
					type="text" 
					name="item_title" 
					placeholder="<?php esc_attr_e( 'E.g: Keynote Speech', 'my-listing' ) ?>">
				</div>

				<div class="form-group col-12">
					<label class="margin-top-10">Brief Description</label>
					<textarea
						cols="20" rows="2"
						name="item_description"
						>
					</textarea>
				</div>

				<div class="form-group w50 col-md-6 col-12">
				    <label>Time </label>
					<input type="time" name="item_time" placeholder="<?php esc_attr_e( 'The scheduled time for this program item', 'my-listing' ) ?>">
				</div>
			</div>
			
			<button data-repeater-delete type="button" aria-label="<?php echo esc_attr( _ex( 'Delete repeater item', 'Repeater field -> Delete item', 'my-listing' ) ) ?>" class="delete-repeater-item buttons button-5 icon-only small"><i class="material-icons delete"></i>
			</button>
		</div>
	</div>
	<input data-repeater-create type="button" value="Add" id="add-menu-links-field">
</div>
