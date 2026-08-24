<?php

// Show Hide: START
$options['_gb_sep_show_hide'] = [
  'tab' => 'content',
  'group' => 'gutenbricks_group',
  'label' => esc_html__('Visibility Toggle', 'gutenbricks'),
  'description' => '',
  'type' => 'separator',
];

$options['_gb_sep_show_hide_cannot'] = [
  'tab' => 'content',
  'group' => 'gutenbricks_group',
  'description' => 'This element is already configured as a variant. It cannot be controlled by visibility toggle.',
  'type' => 'info',
  'required' => [
    ['_gb_enable_variant', '=', true]
  ],
];

$options['_gb_enable_show_hide'] = [
  'type' => 'checkbox',
  'group' => 'gutenbricks_group',
  'tab' => 'content',
  'label' => 'Enable Show & Hide Toggle',
  'required' => [
    ['_gb_enable_variant', '!=', true],
  ],
];

$options['_gb_show_hide_default'] = [
  'type' => 'checkbox',
  'group' => 'gutenbricks_group',
  'tab' => 'content',
  'default' => true,
  'label' => 'Set <b>SHOW</b> as default',
  'description' => 'Check this if you want to show the element by default. <a style="display:none;" href="#" target="_blank">Learn More</a>',
  'hasDynamicData' => false,
  'required' => [
    ['_gb_enable_show_hide', '=', true],
    ['_gb_enable_variant', '!=', true],
  ],
];

$options['_gb_show_hide_label'] = [
  'type' => 'text',
  'group' => 'gutenbricks_group',
  'tab' => 'content',
  'label' => 'Label',
  'placeholder' => 'Show...',
  'hasDynamicData' => false,
  'required' => [
    ['_gb_enable_show_hide', '=', true],
    ['_gb_enable_variant', '!=', true],
  ],
];

$options['_gb_show_hide_group'] = [
  'type' => 'text',
  'group' => 'gutenbricks_group',
  'description' => '(Optional) Same Group Name will be grouped together in the right panel. <a style="display:none;" href="#" target="_blank">Learn More</a>',
  'tab' => 'content',
  'placeholder' => 'Visibility',
  'label' => 'Group Name',
  'hasDynamicData' => false,
  'required' => [
    ['_gb_enable_show_hide', '=', true],
    ['_gb_enable_variant', '!=', true],
  ],
];

$options['_gb_show_hide_instructions'] = [
  'type' => 'textarea',
  'group' => 'gutenbricks_group',
  'tab' => 'content',
  'label' => 'Field Instruction',
  'hasDynamicData' => false,
  'required' => [
    ['_gb_enable_show_hide', '=', true],
    ['_gb_enable_variant', '!=', true],
  ],
];
// Show Hide: END 


