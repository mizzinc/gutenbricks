<?php

if (!$template_edit_disabled) {
  if (!$is_container) {
    $options['_gb_sep_client_exp'] = [
      'tab' => 'content',
      'group' => 'gutenbricks_group',
      'label' => esc_html__('Editing', 'gutenbricks'),
      'description' => '',
      'type' => 'separator',
      'required' => ['_gb_disable_edit', '!=', true],
    ];
    
    $options['_gb_enable_native_editor'] = [
      'type' => 'checkbox',
      'group' => 'gutenbricks_group',
      'tab' => 'content',
      'label' => 'Enable Editor Field',
      'description' => 'When enabled, its editing field will be shown in the right panel.. <a style="display:none;" href="#" target="_blank">Learn More</a>',
      'required' => ['_gb_disable_edit', '!=', true],
    ];
    $options['_gb_native_editor_label'] = [
      'type' => 'text',
      'group' => 'gutenbricks_group',
      'tab' => 'content',
      'label' => 'Field Label',
      'hasDynamicData' => false,
      'required' => [
        ['_gb_disable_edit', '!=', true],
        ['_gb_enable_native_editor', '=', true],
      ],
    ];
    $options['_gb_native_editor_group'] = [
      'type' => 'text',
      'group' => 'gutenbricks_group',
      'tab' => 'content',
      'label' => 'Editor Group Name',
      'description' => '(Optional) Same Group Name will be grouped together in the right panel. <a style="display:none;" href="#" target="_blank">Learn More</a>',
      'hasDynamicData' => false,
      'required' => [
        ['_gb_disable_edit', '!=', true],
        ['_gb_enable_native_editor', '=', true],
      ],
    ];
    $options['_gb_native_editor_instructions'] = [
      'type' => 'textarea',
      'group' => 'gutenbricks_group',
      'tab' => 'content',
      'label' => 'Field Instruction',
      'hasDynamicData' => false,
      'required' => [
        ['_gb_disable_edit', '!=', true],
        ['_gb_enable_native_editor', '=', true],
      ],
    ];

    /*
      $options['_gb_instruction'] = [
        'type' => 'textarea',
        'group' => 'gutenbricks_group',
        'tab' => 'content',
        'description' => 'This description will be shown in the right panel.. <a style="display:none;" href="#" target="_blank">Learn More</a>',
        'label' => 'Element Instruction',
        'required' => ['_gb_disable_edit', '!=', true],
      ];
    */
  }
}

