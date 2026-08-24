<?php

$data['controls']['_gb_block_settings_enabled'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('Block Setting Fields', 'gutenbricks'),
  'type' => 'select',
  'placeholder' => esc_html__('No block settings selected', 'gutenbricks'),
  'description' => 'Select which block settings user can edit. <a style="display:none;" href="#" target="_blank">Learn More</a>',
  'multiple' => true,
  'searchable' => true,
  'clearable' => true,
  'options' => [
    'block_html_id' => 'Block HTML ID',
  ],
  'required' => [
    '_gb_disable_template_edit',
    '!=',
    true
  ],
];