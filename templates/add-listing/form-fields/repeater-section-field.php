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
	
	<div class="item-head form-group">
				<input type="text" name="title" placeholder="<?php esc_attr_e( 'Section Title', 'my-listing' ) ?>">
			
				<?php if ( isset( $field['allow_sub_title'] ) && $field['allow_sub_title'] === true ): ?>
					<div class="item-head">
						<input type="text" name="{section}[sub_title]" placeholder="<?php esc_attr_e( 'Section Sub-title', 'my-listing' ) ?>">
					</div>
				<?php endif ?>
				<?php if ( isset( $field['allow_description'] ) && $field['allow_description'] === true ): ?>
					<textarea
					cols="20" rows="2" class="input-text"
					name="{section}[descript]"
					placeholder="<?php echo esc_attr_x( 'Section Description', 'General Repeater Description', 'my-listing' ) ?>"></textarea>
				<?php endif ?>
			</div>
	<input data-repeater-create type="button" value="<?php esc_attr_e( 'Add item', 'my-listing' ) ?>" id="add-menu-links-field">
	<p><?php var_dump($field['value'] ); ?></p>
</div>
