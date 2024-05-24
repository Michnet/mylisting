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
<h2><?php get_the_ID(); ?></h2>
<?php 
	$edit_post = array(
		'post_id'            => $_GET["job_id"], // Get the post ID
		'field_groups'       => array(24), // Create post field group ID(s)
		'form'               => false,
		//'return'             => '%post_url%',
		'submit_value'       => 'Save Changes',
	);
	acf_form( $edit_post );

?>
