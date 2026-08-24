<?php

$data['controls']['_gb_variant_separator'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('Variants', 'gutenbricks'),
  'description' => 'Configure how variant fields behaves. <a style="display:none;" href="#" target="_blank">Learn More</a>',
  'type' => 'separator',
  'required' => [
    '_gb_disable_template_edit',
    '!=',
    true
  ],
];

$data['controls']['_gb_variant_field_label'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('Variant Label', 'gutenbricks'),
  'placeholder' => 'Variants',
  'type' => 'text',
  'hasDynamicData' => false,
  'required' => [
    '_gb_disable_template_edit',
    '!=',
    true
  ],
];


$data['controls']['_gb_variant_field_type'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('Displayed Field Type', 'gutenbricks'),
  'type' => 'select',
  'searchable' => true,
  'hasDynamicData' => false,
  'placeholder' => 'Default: Radio',
  'default' => 'radio',
  'options' => [
    'radio' => 'Radio',
    'select' => 'Select',
  ],
  'required' => [
    '_gb_disable_template_edit',
    '!=',
    true
  ],
];

$data['controls']['_gb_variant_field_group'] = [
  'group' => '_gutenbricks_block',
  'label' => esc_html__('Variant Group Name', 'gutenbricks'),
  'placeholder' => 'Variants',
  'hasDynamicData' => false,
  'type' => 'text',
  'description' => 'The group name where the varient field will be placed.',
  'required' => [
    '_gb_disable_template_edit',
    '!=',
    true
  ],
];


