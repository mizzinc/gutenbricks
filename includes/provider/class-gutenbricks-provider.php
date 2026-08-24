<?php
namespace Bricks\Integrations\Dynamic_Data\Providers;
use Gutenbricks\PerformanceMonitor;

if (!defined('ABSPATH')) exit;

if (!class_exists("\Bricks\Integrations\Dynamic_Data\Providers\Base")) {
  return;
}

class Provider_Gutenbricks extends \Bricks\Integrations\Dynamic_Data\Providers\Base
{
  public function __construct()
  {
    parent::__construct('gutenbricks');

    add_filter('bricks/dynamic_tags_list', [$this, 'filter__add_tags_to_builder']);
    add_filter('bricks/dynamic_data/render_content', [$this, 'filter__render_content'], 10, 3);
		add_filter('bricks/dynamic_data/render_tag', [$this, 'filter__render_tag'], 10, 3);
    add_filter('bricks/frontend/render_data', [$this, 'filter__render_data'], 99, 3);
    add_filter('gutenbricks/dynamic_fields', [$this, 'filter__dynamic_fields'], 10, 2);
  }

  public function filter__render_data($render_data, $post, $area) {
    if ($area === 'content') {
      PerformanceMonitor::start('gutenbricks/inject_gbmeta');
      $render_data = $this->filter__render_content($render_data, $post, 'text');
      //PerformanceMonitor::end('gutenbricks/inject_gbmeta');
    }

    return $render_data;
  }


  public function filter__render_content($content, $post, $context) {
    $content = preg_replace_callback('/\{(' . GUTENBRICKS_META_PREFIX . '[^\}]+)\}/', function ($matches) use ($context) {
      $meta_key = preg_replace('/' . preg_quote(GUTENBRICKS_META_PREFIX, '/') . '/', '', $matches[1], 1);
      $meta_key = str_replace('{', '', $meta_key);
      $meta_key = str_replace('}', '', $meta_key);
      $value = \GutenBricks\Render_Context::gb_get_field($meta_key, $context);
      return $value;
    }, $content);

    return $content;
  }

  public function filter__render_tag($tag, $post, $context) {
    if (is_string($tag) && strpos($tag, GUTENBRICKS_META_PREFIX) === 1) {
      $meta_key = str_replace(GUTENBRICKS_META_PREFIX, '', $tag);
      $meta_key = str_replace('{', '', $meta_key);
      $meta_key = str_replace('}', '', $meta_key);
      $value = \GutenBricks\Render_Context::gb_get_field($meta_key, $context);
      return $value;
    }

    return $tag;
  }

  public function register_tags()
  {
    $tags = $this->get_tags_config();

    $new_tags = [];

    foreach ($tags as $key => $tag) {
      $new_tags[$key] = [
        'name' => '{' . $key . '}',
        'label' => $tag['label'],
        'group' => $tag['group'],
        'provider' => $this->name,
      ];
    }

    $this->tags = $new_tags;
  }

  public function get_tags_config()
  {
    $tags = [];

    $tags['gb_block_id'] = [
      'label' => 'GutenBricks Block ID',
      'group' => 'GutenBricks',
    ];

    $tags['gb_element_id'] = [
      'label' => 'GutenBricks Element Unique ID',
      'group' => 'GutenBricks',
    ];

    $tags['gb_query_index'] = [
      'label' => 'Bricks Query Index',
      'group' => 'GutenBricks',
    ];

    $tags['gb_event_block_load'] = [
      'label' => '<b>GutenBricks Event:</b> Block Load',
      'group' => 'GutenBricks',
    ];

    $tags['gb_event_block_remove'] = [
      'label' => '<b>GutenBricks Event:</b> Block Remove',
      'group' => 'GutenBricks',
    ];

    $tags['gb_event_block_prerender'] = [
      'label' => '<b>GutenBricks Event:</b> Block Prerender',
      'group' => 'GutenBricks',
    ];

    return $tags;
  }

  // we need to add the tags to the builder like this because we need to access
  // the current post (global $post) which is not available during initiation
  // so we can't use the regular 'add_tags_to_builder' method
  public function filter__add_tags_to_builder($tags)
  {
    global $post;

    if (empty($post)) {
      return $tags;
    }

    $settings = get_post_meta($post->ID, GUTENBRICKS_DB_TEMPLATE_SETTINGS, true);

    if (empty($settings['_gb_meta_fields'])) {
      return $tags;
    }

    foreach ($settings['_gb_meta_fields'] as $field) {
      $tags[] = [
        'name' => '{' . GUTENBRICKS_META_PREFIX . $field['name'] . '}',
        'label' => '<b>GB:Meta:</b> ' . $field['label'],
        'group' => 'GutenBricks',
      ];
    }

    return $tags;
  }

  public function get_tag_value($tag, $post, $args, $context = 'text')
  {
    if (strpos($tag, GUTENBRICKS_META_PREFIX) === 0) {
      $meta_key = str_replace(GUTENBRICKS_META_PREFIX, '', $tag);
      $value = \GutenBricks\Render_Context::gb_get_field($meta_key, $context);
      return $value;
    }
   
    $block_id = \GutenBricks\Render_Context::get_current_block_id();

    switch ($tag) {
      case 'gb_block_id':
        return $block_id;
      case 'gb_element_id':
        return \GutenBricks\Render_Context::get_current_element_id();
      case 'gb_query_index':
        return \GutenBricks\Bricks_Bridge::get_query_index();
      case 'gb_event_block_load':
        return 'gb:block:load:' . $block_id;
      case 'gb_event_block_remove':
        return 'gb:block:remove:' . $block_id;
      case 'gb_event_block_prerender':
        return 'gb:block:prerender:' . $block_id;
    }

    return $tag;
  }

  public function filter__dynamic_fields($fields, $template_id) {

    $template_settings = \GutenBricks\Render_Context::get_template_settings($template_id);
    $template_settings = $template_settings['_gb_meta_fields'] ?? [];

    foreach ($template_settings as $field) {
      if (empty($field['type'])) {
        $field['type'] = 'text';
      }
        
      $field['_provider'] = 'gbmeta';

      $fields[$field['id']] = $field;
    }

    return $fields;
  }


}