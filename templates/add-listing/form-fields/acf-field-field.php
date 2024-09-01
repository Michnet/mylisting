<?php
/**
 * Shows `text` form field on listing forms.
 *
 * @since 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php acf_form_head(); ?>
<?php 
  
  $keysString = isset($field['acf_field_keys']) ? $field['acf_field_keys'] : null;
  $keysArray = explode(',', $keysString);

	$edit_post = array(
		'post_id'            => $_GET["job_id"], // Get the post ID
		'fields'        	 => is_array($keysArray) ? $keysArray : false,
        'field_groups'       => isset($field['acf_field_group_keys']) ? array($field['acf_field_group_keys']) :  false,
		'form'               => false,
		//'return'             => '%post_url%',
		'submit_value'       => 'Save Changes',
	);
	acf_form( $edit_post );

?>
