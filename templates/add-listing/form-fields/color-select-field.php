<?php
/**
 * Shows the `select` form field on add listing forms.
 *
 * @since 1.0
 * @var   array $field
 */

$attrs = [];
$attrs[] = sprintf( 'name="%s"', esc_attr( isset( $field['name'] ) ? $field['name'] : $key ) );
$attrs[] = sprintf( 'id="%s"', esc_attr( $key ) );
$attrs[] = ! empty( $field['required'] ) ? 'required' : '';
$attrs[] = sprintf( 'placeholder="%s"', esc_attr( ! empty( $field['placeholder'] ) ? $field['placeholder'] : '' ) );
?>

<select <?php echo join( ' ', $attrs ) ?>>
	<option></option>
	<?php foreach ( $field['options'] as $key => $value ): ?>
		<option value="<?php echo esc_attr( $key ) ?>" <?php echo ! empty( $field['value'] ) ? selected( $field['value'], $key ) : '' ?>>
			<span class="color_preview" style="background:<?php echo esc_attr( $key );?>"><?php echo esc_html( $value );?></span>
		</option>
	<?php endforeach ?>
</select>
