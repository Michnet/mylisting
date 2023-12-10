<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Repeater_Section_Field extends Base_Field {

	public function get_posted_value() {

		$value = ! empty( $_POST[ $this->key ] ) ? $_POST[ $this->key ] : [];

		$form_key = 'current_'.$this->key;
		$files = isset( $_POST[ $form_key ] ) ?  $_POST[ $form_key ] : [];
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

				foreach($file_value as $field_name => $field_value){
					if(isset($field_value) && $field_name != 'mylisting_accordion_photo'){
						$file_value[$field_name] = sanitize_text_field( stripslashes($field_value) );
					}
				}

				$file_value['mylisting_accordion_photo'] = $file;
				}


				$links[] = $file_value;
			}

		$data = [];

		$data[] = [
			'title' =>  $_POST[ $this->key.'_title'],
			'sub_title' =>  $_POST[ $this->key.'_sub_title'],
			'descript' =>  $_POST[ $this->key.'_descript'],
			'list' => array_filter( $links )
		];

		return $data;
	}

	public function validate() {
		$value = $this->get_posted_value();
	}

	public function field_props() {
		// for backwards compatibility
		$this->props['type'] = 'repeater-section';
		$this->props['allow_sub_title'] = true;
		$this->props['allow_description'] = true;
		$this->props['singular_label'] = '';
		$this->props['plural_label'] = '';

		$this->props['allow_item_sub_title'] = true;
		$this->props['allow_item_link'] = true;
		$this->props['allow_item_description'] = true;
		$this->props['allow_item_images'] = true;
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
		$this->allowLabels();
		$this->allowSubTitle();
		$this->allowDescription();
		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
		$this->getShowInCompareField();

		$this->allowItemSubTitle();
		$this->allowItemImages();
		$this->allowItemDescription();
		$this->allowItemLink();
	}

	public function allowLabels() { ?>
		<div class="form-group w50">
			<label>Plural label</label>
			<input type="text" v-model="field.plural_label" placeholder="E.g. Reasons">
		</div>

		<div class="form-group w50">
			<label>Singular label</label>
			<input type="text" v-model="field.singular_label" placeholder="E.g. Reason">
		</div>
		<?php
	}

	public function allowSubTitle() { ?>
		<div class="form-group w50">
			<label>Enable subtitle?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_sub_title">
				<span class="switch-slider"></span>
			</label>
		</div>
		<?php
	}

	public function allowItemSubTitle() { ?>
		<div class="form-group w50">
			<label>Enable item subtitle?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_item_sub_title">
				<span class="switch-slider"></span>
			</label>
		</div>
		<?php
	}

	public function allowItemLink() { ?>
		<div class="form-group w50">
			<label>Enable Item Link?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_item_link">
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

	public function allowItemDescription() { ?>
		<div class="form-group w50">
			<label>Enable Item description?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_item_description">
				<span class="switch-slider"></span>
			</label>
		</div>
		<?php
	}

	public function allowItemImages() { ?>
		<div class="form-group">
			<label>Enable item images?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.allow_item_images">
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
