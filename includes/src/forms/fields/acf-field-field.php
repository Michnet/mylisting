<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Acf_Field_Field extends Base_Field {

	public function init() {
		if ( $this->get_key() === 'job_phone' && $this->props['content_lock'] ) {
			$key = 'phone';
			add_filter( 'mylisting/compile-string/unescaped-fields', function( $fields ) use ( $key ) {
				$fields[] = $key;
				return $fields;
			} );

			add_filter( 'mylisting/compile-string-field/phone', function( $value, $field ) {
				return sprintf( '<div class="c27-display-button" data-listing-id="%1$s" data-field-id="%2$s">%3$s</div>', $field->listing->get_id(), $field->get_key(), esc_html__( 'Show Number', 'my-listing' ) );
			}, 50, 2 );
		}
	}

	public function get_posted_value() {
		return isset( $_POST[ $this->key ] )
			? sanitize_text_field( stripslashes( $_POST[ $this->key ] ) )
			: '';
	}

	public function validate() {
		$value = $this->get_posted_value();
		//$this->validateMinLength();
		//$this->validateMaxLength();
	}

	public function field_props() {
		$this->props['type'] = 'acf-field';
		$this->props['acf_field_keys'] = '';
		$this->props['acf_field_group_keys'] = '';
	}
	

	public function get_editor_options() {
		$this->getLabelField();
		$this->fieldKey();
		$this->fieldGroupKey();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();

		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
		$this->getShowInCompareField();
		$this->getContentLockField();
	}

	public function fieldKey() { ?>
		<div class="form-group w50">
			<label>Field Key</label>
			<input type="text" v-model="field.acf_field_keys"/>
		</div>
		<?php
	}
	public function fieldGroupKey() { ?>
		<div class="form-group w50">
			<label>Field Group Key</label>
			<input type="text" v-model="field.acf_field_group_keys"/>
		</div>
		<?php
	}

	protected function getContentLockField() { ?>
		<div class="form-group" v-show="field.slug === 'job_phone'">
			<label>Content Lock</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.content_lock">
				<span class="switch-slider"></span>
			</label>
		</div>
	<?php }
}