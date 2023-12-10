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
$uploaded_files = ! empty( $field['value'] ) ? (array) $field['value'][0]['list'] : [];

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

	<div class="section-form-group">
		<input type="text" 
			name="<?php echo esc_attr( $key.'_title' ); ?>" 
			placeholder="<?php esc_attr_e( 'Section Heading', 'my-listing' ) ?>"
			value="<?php echo isset( $field['value'][0]['title'] ) ? esc_attr( $field['value'][0]['title'] ) : ''; ?>"
			>
	
		<?php if ( isset( $field['allow_sub_title'] ) && $field['allow_sub_title'] === true ): ?>
			<input type="text" 
				name="<?php echo esc_attr( $key.'_sub_title' ); ?>" 
				placeholder="<?php esc_attr_e( 'Section Sub-heading', 'my-listing' ) ?>"
				value="<?php echo isset( $field['value'][0]['sub_title'] ) ? esc_attr( $field['value'][0]['sub_title'] ) : ''; ?>"
				>
		<?php endif ?>

		<?php if ( isset( $field['allow_description'] ) && $field['allow_description'] === true ): ?>
			<textarea
			cols="20" rows="2" class="input-text"
			name="<?php echo esc_attr( $key.'_descript' ); ?>"
			value="<?php echo isset( $field['value'][0]['descript'] ) ? esc_attr( $field['value'][0]['sub_title'] ) : ''; ?>"
			placeholder="<?php echo esc_attr_x( 'Section description', 'General Repeater Description', 'my-listing' ) ?>"></textarea>
		<?php endif ?>
	</div>
    
<div class="resturant-menu-repeater" data-uploaded-list="<?php echo htmlspecialchars(json_encode(! empty( $files ) ? $files : []), ENT_QUOTES, 'UTF-8') ?>" data-list="<?php echo htmlspecialchars(json_encode( isset( $field['value'] ) ? $uploaded_files : []), ENT_QUOTES, 'UTF-8') ?>">
	<div class="repeater-list" data-repeater-list="<?php echo esc_attr( (isset($field['name']) ? $field['name'] : $key) ) ?>">
		<div data-repeater-item class="repeater-field-wrapper">

			<?php if ( isset( $field['allow_item_images'] ) && $field['allow_item_images'] === true ): ?>
				<div class="field-type-file form-group">
					<div class="field ">
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
			<?php endif ?>


			<div class="fields-box row mx-0">

				
				<label>Title </label>
				<input required type="text" 
				name="item_title" placeholder="<?php esc_attr_e( 'Item name or title', 'my-listing' ) ?>">

				<?php if ( isset( $field['allow_item_sub_title'] ) && $field['allow_item_sub_title'] === true ): ?>
					<label>Sub-title </label>
					<input required type="text" 
					name="item_title" placeholder="<?php esc_attr_e( 'Item sub-title', 'my-listing' ) ?>">
				<?php endif ?>

				<?php if ( isset( $field['allow_description'] ) && $field['allow_description'] === true ): ?>
					<label>Item description </label>
					<textarea
					cols="20" rows="2" class="input-text"
					name="menu-description"
					placeholder="<?php echo esc_attr_x( 'A short description for this item', 'General Repeater Description', 'my-listing' ) ?>"></textarea>
				<?php endif ?>

				<?php if ( isset( $field['allow_item_link'] ) && $field['allow_item_link'] === true ): ?>
				<div class="form-group">
					<label><span>Add a link through this item to an external url</span></label>
					<input type="text" name="link_label" placeholder="<?php esc_attr_e( 'Button Label', 'my-listing' ) ?>">
					<input type="url" name="link_url" placeholder="<?php esc_attr_e( 'URL', 'my-listing' ) ?>">
				</div>
				<?php endif ?>

			</div>
			<button data-repeater-delete type="button" aria-label="<?php echo esc_attr( _ex( 'Delete repeater item', 'Repeater field -> Delete item', 'my-listing' ) ) ?>" class="delete-repeater-item buttons button-5 icon-only small"><i class="material-icons delete"></i></button>
		</div>
	</div>
	<input data-repeater-create type="button" value="<?php esc_attr_e( 'Add item', 'my-listing' ) ?>" id="add-menu-links-field">
	<p><?php var_dump($field['value'] ); ?></p>
</div>
