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
			<div class="form-group">
				<input type="text" 
					name="<?php echo esc_attr( $key.'_title' ); ?>" 
					placeholder="<?php esc_attr_e( 'Section Title', 'my-listing' ) ?>"
					value="<?php echo isset( $field['value'] ) ? esc_attr( $field['value'] ) : ''; ?>"
					>
			
				<?php if ( isset( $field['allow_sub_title'] ) && $field['allow_sub_title'] === true ): ?>
					<div class="item-head">
						<input type="text" name="<?php echo esc_attr( $key.'_sub_title' ); ?>" placeholder="<?php esc_attr_e( 'Section Sub-title', 'my-listing' ) ?>">
					</div>
				<?php endif ?>
				<?php if ( isset( $field['allow_description'] ) && $field['allow_description'] === true ): ?>
					<textarea
					cols="20" rows="2" class="input-text"
					name="{section}[descript]"
					placeholder="<?php echo esc_attr_x( 'Section Description', 'General Repeater Description', 'my-listing' ) ?>"></textarea>
				<?php endif ?>
			</div>
	<div data-repeater-list="<?php echo esc_attr( (isset($field['name']) ? $field['name'] : $key) ) ?>">
			
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

				<div class="form-group w50 col-md-6 col-12">
					<label>Test name </label>
					<input required type="text" name="test_name" placeholder="<?php esc_attr_e( 'First Name', 'my-listing' ) ?>">
				</div>
			</div>
			<button data-repeater-delete type="button" aria-label="<?php echo esc_attr( _ex( 'Delete repeater item', 'Repeater field -> Delete item', 'my-listing' ) ) ?>" class="delete-repeater-item buttons button-5 icon-only small"><i class="material-icons delete"></i></button>
		</div>
	</div>
	<input data-repeater-create type="button" value="<?php esc_attr_e( 'Add item', 'my-listing' ) ?>" id="add-menu-links-field">
	<p><?php var_dump($field['value'] ); ?></p>
</div>
