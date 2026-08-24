<?php
/**
 * GutenBricks Meta Fields
 *
 * @package Gutenbricks
 */

$data['controls']['_gb_meta_group_title'] = [
  'group' => '_gutenbricks_meta',
  'label' => esc_html__('Meta Group Title', 'gutenbricks'),
  'hasDynamicData' => false,
  'type' => 'text',
  'placeholder' => esc_html__('Meta Group Title', 'gutenbricks'),
  'description' => __('This title will be used to group the meta fields in the Gutenberg editor.', 'gutenbricks'),
  'required' => [
    '_gb_disable_template_edit',
    '!=',
    true
  ],
];


$data['controls']['_gb_meta_setting_separator'] = [
  'group' => '_gutenbricks_meta',
  'label' => esc_html__('GutenBricks Meta Fields', 'gutenbricks'),
  'type' => 'separator',
  'description' => 'Create custom fields for your block in the Gutenberg editor.',
  'required' => [
    '_gb_disable_template_edit',
    '!=',
    true
  ],
];

$data['controls']['_gb_meta_fields'] = [
  'group' => '_gutenbricks_meta',
  'label' => esc_html__('Fields', 'gutenbricks'),
  'type' => 'repeater',
  'titleProperty' => 'label',
  'titleEditable' => true,
  'placeholder' => esc_html__('Block Meta Field', 'gutenbricks'),
  'fields' => [
    'label' => [
      'type' => 'text',
      'hasDynamicData' => false,
      'placeholder' => 'Show Button',
      'label' => esc_html__('Label', 'gutenbricks'),
    ],
    'name' => [
      'type' => 'text',
      'hasDynamicData' => false,
      'placeholder' => 'show_button',
      'label' => esc_html__('Name', 'gutenbricks'),
      'description' => esc_html__('Field name must be unique and in snake_case (For eg. snake_case.) To make it visible in Dynamic value selector please refresh the browser.', 'gutenbricks'),
    ],
    'type' => [
      'type' => 'select',
      'inline' => true,
      'default' => 'text',
      'placeholder' => 'Text',
      'label' => esc_html__('Type', 'gutenbricks'),
      'options' => [
        'text' => 'Text',
        'number' => 'Number',
        'range' => 'Range',
        'true_false' => 'True / False',
        'select' => 'Select',
        'radio' => 'Radio',
        // 'image_radio' => 'Image Radio',
        'image' => 'Image',
        'color' => 'Color',
        'color_swatch' => 'Color Swatch',
      ],
    ],
    'choices' => [
      'type' => 'textarea',
      'description' => esc_html__('', 'gutenbricks'),
      'label' => esc_html__('Choices', 'gutenbricks'),
      'placeholder' => esc_html__('value:Label', 'gutenbricks'),
      'required' => [
        'type',
        '=',
        ['select', 'radio', 'color_swatch', 'image_radio']
      ],
    ],
    '_color_swatch_palette_info' => [
      'type' => 'info',
      'description' => 'Enter the color value on the lefthand side and the label on the righthand side. For example: #000000:Black, var(--white):White',
      'required' => [
        'type',
        '=',
        ['color_swatch']
      ],
    ],
    '_image_radio_info' => [
      'type' => 'info',
      'description' => 'Enter the value on the lefthand side and the image URL on the righthand side. For example: value:https://example.com/image.jpg',
      'required' => [
        'type',
        '=',
        ['image_radio']
      ],
    ],
    'default_value' => [
      'type' => 'text',
      'label' => esc_html__('Default Value', 'gutenbricks'),
      'required' => [
        'type',
        '=',
        ['text', 'color', 'color_swatch']
      ],
    ],
    'default_value_number' => [
      'type' => 'number',
      'label' => esc_html__('Default Value', 'gutenbricks'),
      'required' => [
        'type',
        '=',
        ['number', 'range']
      ],
    ],
    'min' => [
      'type' => 'number',
      'label' => esc_html__('Min', 'gutenbricks'),
      'required' => [
        'type',
        '=',
        ['number', 'range']
      ],
    ],
    'max' => [
      'type' => 'number',
      'label' => esc_html__('Max', 'gutenbricks'),
      'required' => [
        'type',
        '=',
        ['number', 'range']
      ],
    ],
    'step' => [
      'type' => 'number',
      'label' => esc_html__('Step', 'gutenbricks'),
      'required' => [
        'type',
        '=',
        ['number', 'range']
      ],
    ],
    'default_value_choice' => [
      'type' => 'text',
      'label' => esc_html__('Default Value', 'gutenbricks'),
      'description' => 'Enter the value of the choice.',
      'required' => [
        'type',
        '=',
        ['select', 'radio']
      ],
    ],
    'default_value_boolean' => [
      'type' => 'checkbox',
      'label' => esc_html__('True by Default', 'gutenbricks'),
      'required' => [
        'type',
        '=',
        ['true_false']
      ],
    ],
    'true_value' => [
      'type' => 'text',
      'label' => esc_html__('Value for True', 'gutenbricks'),
      'placeholder' => 'true',
      'hasDynamicData' => false,
      'required' => [
        'type',
        '=',
        ['true_false']
      ],
    ],
    'false_value' => [
      'type' => 'text',
      'label' => esc_html__('Value for False', 'gutenbricks'),
      'placeholder' => 'false',
      'hasDynamicData' => false,
      'required' => [
        'type',
        '=',
        ['true_false']
      ],
    ],
    'default_value_image' => [
      'type' => 'image',
      'label' => esc_html__('Default Image', 'gutenbricks'),
      'required' => [
        'type',
        '=',
        ['image']
      ],
    ],
    'enable_opacity' => [
      'type' => 'checkbox',
      'label' => esc_html__('Enable Opacity', 'gutenbricks'),
      'inline' => true,
      'required' => [
        'type',
        '=',
        ['color']
      ], 
    ],
    'more' => [
      'type' => 'checkbox',
      'label' => esc_html__('More Options', 'gutenbricks'),
      'inline' => true,
    ],
    'instructions' => [
      'type' => 'textarea',
      'rows' => 5,
      'label' => esc_html__('(Optional) Instruction', 'gutenbricks'),
      'hasDynamicData' => false,
      'required' => [
        'more',
        '=',
        true,
      ],
    ]
  ]
];

/*
$data['controls']['_gb_meta_repeater_info'] = [
  'group' => '_gutenbricks_meta',
  'type' => 'separator',
  'label' => esc_html__('GutenBricks Repeater', 'gutenbricks'),
  'description' => 'Create Repeater Fields first and then create a repeater by adding Repeater Fields. <a style="display:none;" href="#" target="_blank">Learn More</a>',
  'required' => [
    '_gb_disable_template_edit',
    '!=',
    true
  ],
];


$data['controls']['_gb_repeater_fields'] = [
  'group' => '_gutenbricks_meta',
  'label' => esc_html__('Repeater Fields', 'gutenbricks'),
  'type' => 'repeater',
  'titleProperty' => 'label',
  'titleEditable' => true,
  'placeholder' => esc_html__('Block Meta Field', 'gutenbricks'),
  'fields' => [
    'label' => [
      'type' => 'text',
      'hasDynamicData' => false,
      'placeholder' => 'Show Button',
      'label' => esc_html__('Label', 'gutenbricks'),
    ],
    'name' => [
      'type' => 'text',
      'hasDynamicData' => false,
      'placeholder' => 'show_button',
      'label' => esc_html__('Name', 'gutenbricks'),
      'description' => esc_html__('Field name must be unique.', 'gutenbricks'),
    ],
    'type' => [
      'type' => 'select',
      'inline' => true,
      'default' => 'text',
      'label' => esc_html__('Type', 'gutenbricks'),
      'options' => [
        'text' => 'Text',
        'true_false' => 'True / False',
        'select' => 'Select',
        'radio' => 'Radio',
        'image' => 'Image',
        'repeater' => 'Repeater',
      ],
    ],
    'options' => [
      'type' => 'textarea',
      'description' => esc_html__('', 'gutenbricks'),
      'label' => esc_html__('Options', 'gutenbricks'),
      'placeholder' => esc_html__('value:Label', 'gutenbricks'),
      'required' => [
        'type',
        '=',
        ['select', 'radio']
      ],
    ],
    '_gb_field_more' => [
      'type' => 'checkbox',
      'label' => esc_html__('More Options', 'gutenbricks'),
      'inline' => true,
    ],
    'instructions' => [
      'type' => 'textarea',
      'rows' => 5,
      'label' => esc_html__('(Optional) Instruction', 'gutenbricks'),
      'required' => [
        '_gb_field_more',
        '=',
        true,
      ],
    ]
  ]
];

if (function_exists('bricks_is_builder') && bricks_is_builder()) {
  $settings = $this->render_context::get_template_settings(get_the_ID());
} else {
  $settings = $this->render_context::get_template_settings();
}

$repeater_field_options = [];
if (!empty($settings['_gb_repeater_fields'])) {
  foreach ($settings['_gb_repeater_fields'] as $field) {
    $repeater_field_options[$field['name']] = $field['label'];
  }
}

$data['controls']['_gb_meta_repeater'] = [
  'group' => '_gutenbricks_meta',
  'label' => esc_html__('Repeater', 'gutenbricks'),
  'type' => 'repeater',
  'titleProperty' => 'label',
  'titleEditable' => true,
  'placeholder' => esc_html__('Block Meta Field', 'gutenbricks'),
  'fields' => [
    'label' => [
      'type' => 'text',
      'hasDynamicData' => false,
      'placeholder' => 'Show Button',
      'label' => esc_html__('Label', 'gutenbricks'),
    ],
    'name' => [
      'type' => 'text',
      'hasDynamicData' => false,
      'placeholder' => 'show_button',
      'label' => esc_html__('Name', 'gutenbricks'),
      'description' => esc_html__('Field name must be unique.', 'gutenbricks'),
    ],
    '_gb_children_repeater_fields' => [
      'type' => 'select',
      'clearable' => true,
      'multiple' => true,
      'searchable' => true,
      'description' => esc_html__('If you do not see the labels, please save and refresh the page.', 'gutenbricks'),
      'label' => esc_html__('Type', 'gutenbricks'),
      'options' => $repeater_field_options,
    ],
    
    '_gb_field_more' => [
      'type' => 'checkbox',
      'label' => esc_html__('More Options', 'gutenbricks'),
          'inline' => true,
        ],
        'instructions' => [
          'type' => 'textarea',
          'rows' => 5,
          'label' => esc_html__('(Optional) Instruction', 'gutenbricks'),
          'required' => [
            '_gb_field_more',
            '=',
            true,
          ],
        ]
      ]
    ];
*/
