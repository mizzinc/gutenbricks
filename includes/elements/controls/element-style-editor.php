<?php

if (!$template_edit_disabled) {
  $options['_gb_style_editor_separator'] = [
    'group' => 'gutenbricks_group',
    'label' => esc_html__('Style Editor', 'gutenbricks'),
    'type' => 'separator',
    'description' => 'Add fields to edit the style of this element.',
    'required' => [
      '_gb_disable_template_edit',
      '!=',
      true
    ],
  ];

  $options['_gb_elem_fields'] = [
    'group' => 'gutenbricks_group',
    'label' => esc_html__('Fields', 'gutenbricks'),
    'type' => 'repeater',
    'titleProperty' => 'target',
    'titleEditable' => true,
    'placeholder' => esc_html__('Style Editing Field', 'gutenbricks'),
    'fields' => [
      // Targets: START 
      'target' => [
        'type' => 'select',
        'inline' => true,
        'label' => esc_html__('CSS Target', 'gutenbricks'),
        'searchable' => true,
        'options' => array_merge(
          \GutenBricks\Style_Editor::$image_targets, 
          \GutenBricks\Style_Editor::$numeral_targets, 
          \GutenBricks\Style_Editor::$color_targets),
      ],

      // other_type: START
      'other_type' => [
        'type' => 'select',
        'inline' => true,
        'default' => 'text',
        'label' => esc_html__('Input Type', 'gutenbricks'),
        'options' => [
          'text' => 'Text',
          'select' => 'Select',
          'radio' => 'Radio',
        ],
        'required' => [
          [
            'target',
            '=',
            array_keys(\GutenBricks\Style_Editor::$other_targets)
          ],
        ],
      ],
      'other_type_options' => [
        'type' => 'textarea',
        'description' => esc_html__('', 'gutenbricks'),
        'label' => esc_html__('Options', 'gutenbricks'),
        'placeholder' => esc_html__('value:Label', 'gutenbricks'),
        'required' => [
          'other_type',
          '=',
          ['select', 'radio']
        ],
      ],
      // other_type: END 



      // image_type: START
      'image_type' => [
        'type' => 'select',
        'inline' => true,
        'default' => 'text',
        'label' => esc_html__('Input Type', 'gutenbricks'),
        'options' => [
          'image' => 'Image',
        ],
        'required' => [
          [
            'target',
            '=',
            array_keys(\GutenBricks\Style_Editor::$image_targets)
          ],
        ],
      ],

      'image_type_options' => [
        'type' => 'textarea',
        'description' => esc_html__('', 'gutenbricks'),
        'label' => esc_html__('Options', 'gutenbricks'),
        'placeholder' => esc_html__('value:Label', 'gutenbricks'),
        'required' => [
          'image_type',
          '=',
          ['select', 'radio']
        ],
      ],
      // image_type: END 




      // Range Type: START
      'numeral_type' => [
        'type' => 'select',
        'inline' => true,
        'default' => 'text',
        'label' => esc_html__('Input Type', 'gutenbricks'),
        'options' => [
          'text' => 'Text',
          'select' => 'Select',
          'radio' => 'Radio',
          'range' => 'Range',
        ],
        'required' => [
          [
            'target',
            '=',
            array_keys(\GutenBricks\Style_Editor::$numeral_targets)
          ],
        ],
      ],

      'numeral_type_options' => [
        'type' => 'textarea',
        'description' => esc_html__('', 'gutenbricks'),
        'label' => esc_html__('Options', 'gutenbricks'),
        'placeholder' => esc_html__('value:Label', 'gutenbricks'),
        'required' => [
          'numeral_type',
          '=',
          ['select', 'radio']
        ],
      ],

      // For Range: START
      'range_min' => [
        'type' => 'text',
        'inline' => true,
        'hasDynamicData' => false,
        'label' => esc_html__('Range Min', 'gutenbricks'),
        'placeholder' => 'Default: 0',
        'default' => '0',
        'required' => [
          'numeral_type',
          '=',
          ['range']
        ],
      ],
      'range_max' => [
        'type' => 'text',
        'inline' => true,
        'hasDynamicData' => false,
        'placeholder' => 'Default: 100',
        'label' => esc_html__('Range Max', 'gutenbricks'),
        'default' => '100',
        'required' => [
          'numeral_type',
          '=',
          ['range']
        ],
      ],
      'range_step' => [
        'type' => 'text',
        'inline' => true,
        'hasDynamicData' => false,
        'placeholder' => 'Default: 1',
        'label' => esc_html__('Step', 'gutenbricks'),
        'default' => '1',
        'required' => [
          'numeral_type',
          '=',
          ['range']
        ],
      ],
      'range_unit' => [
        'type' => 'text',
        'label' => esc_html__('Unit', 'gutenbricks'),
        'placeholder' => esc_html__('px, em, rem...', 'gutenbricks'),
        'inline' => true,
        'hasDynamicData' => false,
        'required' => [
          'numeral_type',
          '=',
          ['range']
        ],
      ],
      // For Range: END

      // Range Type: END


      'color_type' => [
        'type' => 'select',
        'inline' => true,
        'default' => 'text',
        'label' => esc_html__('Input Type', 'gutenbricks'),
        'options' => [
          'text' => 'Text',
          'select' => 'Select',
          'radio' => 'Radio',
          'color' => 'Color Picker',
          'color_swatch' => 'Color Swatch',
        ],
        'required' => [
          [
            'target',
            '=',
            array_keys(\GutenBricks\Style_Editor::$color_targets)
          ],
        ],
      ],

      'color_type_options' => [
        'type' => 'textarea',
        'description' => esc_html__('', 'gutenbricks'),
        'label' => esc_html__('Options', 'gutenbricks'),
        'placeholder' => esc_html__('value:Label', 'gutenbricks'),
        'required' => [
          'color_type',
          '=',
          ['select', 'radio', 'color_swatch']
        ],
      ],

      'color_value_type' => [
        'type' => 'select',
        'inline' => true,
        'label' => esc_html__('Color Value Type', 'gutenbricks'),
        'default' => 'hex',
        'description' => 'Make sure the default value is also set in the same type.',
        'searchable' => true,
        'options' => [
          'hex' => 'HEX',
          'rgb' => 'RGB',
          'hsl' => 'HSL',
          'raw' => 'Raw',
        ],
        'required' => [
          [
            'target',
            '=',
            array_keys(\GutenBricks\Style_Editor::$color_targets)
          ],
        ],
      ],

      // Targets: END



      'custom_label' => [
        'type' => 'text',
        'hasDynamicData' => false,
        'placeholder' => 'Show Button',
        'description' => esc_html__('If left empty, CSS target name will be the label.', 'gutenbricks'),
        'label' => esc_html__('Custom Label', 'gutenbricks'),
      ],
      'group' => [
        'type' => 'text',
        'label' => esc_html__('Group Name', 'gutenbricks'),
        'hasDynamicData' => false,
        'description' => 'Same Group Name will be grouped together in the right panel.',
        'default' => 'Style',
        'placeholder' => 'Default: Style',
      ],
      'instructions' => [
        'type' => 'textarea',
        'rows' => 5,
        'label' => esc_html__('Instruction', 'gutenbricks'),
        'hasDynamicData' => false,
      ]
    ]
  ];
}

