<?php

namespace MyListing\Src\Listing_Types\Content_Blocks;

if ( ! defined('ABSPATH') ) {
	exit;
}

class General_Repeater_Block extends Base_Block {

	public function props() {
		$this->props['type'] = 'general_repeater';
		$this->props['title'] = 'General Repeater';
		$this->props['icon'] = 'mi view_module';
		$this->props['style'] = 'outlined-icons';
		$this->props['show_field'] = '';
		$this->props['template'] = 'default';
		$this->props['cols'] = 2;
		$this->props['cols_sm'] = 2;
		$this->props['cols_xs'] = 1;
		$this->props['gap'] = 20;
		$this->allowed_fields = [ 'general-repeater' ];
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getSourceField();
		$this->setTemplate();
		$this->setCols();
		$this->setColGap();
	}

	protected function setTemplate() { ?>
		<div class="form-group">
			<label>Template</label>
			<div class="select-wrapper">
				<select v-model="block.template">
					<option value="default">Horizontal</option>
					<option value="list-view">Vertical</option>
				</select>
			</div>
		</div>
	<?php }

	protected function setCols() { ?>
		<template v-if="block.template === 'default'">
			<div class="form-group">
				<label>Number of Columns</label>
				<input type="number" v-model="block.cols" min="1" max="12"></input>
			</div>
			<div class="form-group">
				<label>Number of Columns (Tablet)</label>
				<input type="number" v-model="block.cols_sm" min="1" max="12"></input>
			</div>
			<div class="form-group">
				<label>Number of Columns (Mobile)</label>
				<input type="number" v-model="block.cols_xs" min="1" max="12"></input>
			</div>
		</template>
	<?php }

	protected function setColGap() { ?>
		<div class="form-group">
			<label>Gap Between Columns</label>
			<input type="number" v-model="block.gap"></input>
		</div>
	<?php }
}