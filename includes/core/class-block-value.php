<?php

namespace Gutenbricks;

// Handle conversion and extraction of block values from the post content
class Block_Value
{
  protected $post_id;

  public function __construct($post_id)
  {
    $this->post_id = $post_id;
  }

  public function update_post_content()
  {
    $post = get_post($this->post_id);

    if (!$post) {
      return;
    }

    $post_content = $post->post_content;

    $blocks = parse_blocks($post_content);

    $blocks = $this->update_gutenberg_blocks($blocks);

    $updated_post_content = serialize_blocks($blocks);

    wp_update_post(array(
      'ID' => $this->post_id,
      'post_content' => wp_slash($updated_post_content),
    ));
  }

  protected function update_gutenberg_blocks($blocks)
  {
    foreach ($blocks as $key => $block) {
      if (isset($block['innerHTML'])) {
        $results = self::get_attribute_from_dom($block['innerHTML']);

        $blocks[$key] = $this->update_block_attributes($blocks[$key], $results);
      }

      if (isset($block['innerBlocks']) && !empty($block['innerBlocks'])) {
        $blocks[$key]['innerBlocks'] = $this->update_gutenberg_blocks($blocks[$key]['innerBlocks']);
      }

      // For Debugging
      // gb_log(['message' => 'update_gutenberg_blocks', 'block' => $blocks[$key]['attrs'], 'results' => $results]);
    }
    return $blocks;
  }

  protected function update_block_attributes($block, $results)
  {
    if (!isset($block['attrs'])) {
      $block['attrs'] = [];
    }

    foreach ($results as $key => $value) {
      if ($value !== null) {
        $block['attrs'][$key] = $this->array_merge_recursive_distinct(
          $block['attrs'][$key] ?? [],
          $value
        );
      }
    }
  
    return $block;
  }

  /**
   * Recursively merge arrays while preserving numeric keys
   * and avoiding duplicate values in numeric arrays
   */
  protected function array_merge_recursive_distinct(array $array1, array $array2)
  {
    $merged = $array1;

    foreach ($array2 as $key => $value) {
      if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
        $merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $value);
      } else {
        $merged[$key] = $value;
      }
    }

    return $merged;
  }

  public static function get_attribute_from_dom($html)
  {
    $results = [];

    if (empty($html)) {
      return $results;
    }

    if (!class_exists('\DOMDocument')) {
      return $results;
    }

    // Decode HTML entities before processing
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $html = '<html><head><meta content="text/html; charset=utf-8" http-equiv="Content-Type"></head><body>' . $html . '</body></html>';

    $dom = new \DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    // Get all elements in the document
    $all_elements = $dom->getElementsByTagName('*');

    $current_data_name = null;

    foreach ($all_elements as $element) {
      if ($element->hasAttribute('data-gbrx-id')) {
        $gb_id = $element->getAttribute('data-gbrx-id');
        $data_name = $element->hasAttribute('data-gbrx-name') ? $element->getAttribute('data-gbrx-name') : null;

        if (!empty($data_name)) {
          $current_data_name = $data_name;
        }

        $innerHTML = '';
        if ($element->firstChild) {
          foreach ($element->childNodes as $child) {
            $innerHTML .= $dom->saveHTML($child);
          }
        }

        $results[$gb_id] = apply_filters('gutenbricks/element/' . $current_data_name . '/get_attribute_from_dom', null, $innerHTML, $element);
      }
    }

    return $results;
  }
}



