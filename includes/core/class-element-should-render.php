<?php
namespace Gutenbricks;

/**
 * Class Element_Should_Render
 * 
 * This class is responsible for determining whether a given element should be rendered
 * based on various conditions and settings. It checks for rendering disable flags,
 * show/hide conditions, and variant settings. The class uses Gutenberg attributes
 * to make these determinations and provides a centralized way to manage element
 * visibility logic across the Gutenbricks plugin.
 */
class Element_Should_Render {
    private static $current_gutenberg_attributes;

    public static function set_current_gutenberg_attributes($attributes) {
        self::$current_gutenberg_attributes = $attributes;
    }

    public static function check($should_render, $element)
    {
        // PerformanceMonitor::start('gutenbricks/process_elements/should_render_element');
        
        $element = is_object($element) ? (array) $element : $element;
        $settings = $element['settings'];
        $element_id = $element['id'];

        $render_decision = self::check_rendering_conditions($settings, $element_id);

        // PerformanceMonitor::end('gutenbricks/process_elements/should_render_element');
        
        return $render_decision ?? $should_render;
    }

    private static function check_rendering_conditions($settings, $element_id)
    {
        if (isset($settings['_gb_disable_rendering'])) {
            return $settings['_gb_disable_rendering'] !== true;
        }

        if (isset($settings['_gb_enable_show_hide'])) {
            return self::check_show_hide_condition($settings, $element_id);
        }

        if (isset($settings['_gb_enable_variant']) && $settings['_gb_enable_variant'] === true) {
            return self::check_variant_condition($settings);
        }

        return null;
    }

    private static function check_show_hide_condition($settings, $element_id)
    {
        $attribute_key = isset($settings['_gb_binding_name']) 
            ? 'bind_' . $settings['_gb_binding_name'] 
            : 'gb-' . $element_id;

        if (isset(self::$current_gutenberg_attributes[$attribute_key]['_gb_show_element'])) {
            return self::$current_gutenberg_attributes[$attribute_key]['_gb_show_element'] === true;
        }

        return $settings['_gb_show_hide_default'] ?? false;
    }

    private static function check_variant_condition($settings)
    {
        $_gb_variant_name = $settings['_gb_variant_name'] ?? '';

        if (empty($_gb_variant_name)) {
            return null;
        }

        if (empty(self::$current_gutenberg_attributes['_gb_current_variant'])) {
            self::$current_gutenberg_attributes['_gb_current_variant'] = $_gb_variant_name;
            return true;
        }

        return self::$current_gutenberg_attributes['_gb_current_variant'] === $_gb_variant_name;
    }
}