<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class General_Repeater_Field extends Base_Field {

	public function get_posted_value() {

		$value = ! empty( $_POST[ $this->key ] ) ? (array) $_POST[ $this->key ] : [];

		$form_key = 'current_'.$this->key;
		$files = isset( $_POST[ $form_key ] ) ? (array) $_POST[ $form_key ] : [];
		$prepared_files = [];

		if ( ! empty( $files ) ) {
			foreach ( $files as $key => $url ) {
				if ( ! isset( $url['mylisting_accordion_photo'] ) ) {
					continue;
				}

				if ( is_array( $url['mylisting_accordion_photo'] ) ) {
					$url['mylisting_accordion_photo'] = reset($url['mylisting_accordion_photo']);
				}

				$prepared_files[ $key ] = $url['mylisting_accordion_photo'];
			}
		}
		
		$links = [];
			foreach ( $value as $index => $file_value ) {
				if ( empty( $file_value ) || ! is_array( $file_value ) ) {
					continue;
				}

				if ( isset( $prepared_files[ $index ] ) ) {
				$file = $prepared_files[ $index ];
				if ( is_array( $file ) ) {
					$file = reset( $file );
				}

				$file_value['mylisting_accordion_photo'] = $file;
				}

				$links[] = $file_value;
			}
		
		return array_filter( $links );
	}

	public function validate() {
		$value = $this->get_posted_value();
	}

	public function field_props() {
		// for backwards compatibility
		$this->props['type'] = 'general-repeater';
		$this->props['allow_price'] = true;
		$this->props['currency'] = '';
		$this->props['allow_link'] = true;
		$this->props['allow_description'] = true;
		$this->props['allow_images'] = true;
	}

	public function update() {
		$value = $this->get_posted_value();
		update_post_meta( $this->listing->get_id(), '_'.$this->key, $value );
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		$this->allowPrice();
		$this->allowLink();
		$this->allowDescription();
		$this->allowImages();
		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
		$this->getShowInCompareField();
	}

	public function allowPrice() { ?>
		<div class="form-group w50">
			<label>Enable price?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_price">
				<span class="switch-slider"></span>
			</label>
		</div>

		<div class="form-group w50">
			<label>Currency</label>
			<input type="text" v-model="field.currency" placeholder="E.g. $">
		</div>
		<?php
	}

	public function allowLink() { ?>
		<div class="form-group w50">
			<label>Enable link?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_link">
				<span class="switch-slider"></span>
			</label>
		</div>
		<?php
	}

	public function allowDescription() { ?>
		<div class="form-group w50">
			<label>Enable description?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_description">
				<span class="switch-slider"></span>
			</label>
		</div>
		<?php
	}

	public function allowImages() { ?>
		<div class="form-group">
			<label>Enable images?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_images">
				<span class="switch-slider"></span>
			</label>
		</div>
		<?php
	}

	public function get_value() {
		$value = get_post_meta( $this->listing->get_id(), '_'.$this->key, true );
		return $value;
	}
}
