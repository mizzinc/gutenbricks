<?php
namespace Gutenbricks\Utilities;
use Gutenbricks\PerformanceMonitor;

require_once 'class-css-parser.php';

class CSSScoper
{
  public static function scope_css($original, $compact = false)
  {
    PerformanceMonitor::start('gutenbricks/editor/scope_css');
    $parser = new CSSParser($original);

    $parser->updateSelectors(function ($selector) {
      return self::scope_css_selector($selector);
    });

    $scopedCSS = $parser->render();

    PerformanceMonitor::end('gutenbricks/editor/scope_css');
    return $scopedCSS;
  }

   public static function scope_css_file($sub_path_inside_upload, $src_path, $file_name)
   {
     // check 
     $upload_dir = wp_upload_dir();
     $gutenbricks_upload_dir = $upload_dir['basedir'] . '/gutenbricks' . $sub_path_inside_upload;
     $dest_path = $gutenbricks_upload_dir . '/' . $file_name;
 
     if (file_exists($dest_path) && filemtime($src_path) < filemtime($dest_path)) {
       return false; // no need to regenerate
     }
 
     $cssContent = file_get_contents($src_path);
 
     // Remove all CSS comments
     $cssWithoutComments = preg_replace('/\/\*.*?\*\//s', '', $cssContent);
 
     $modifiedCss = self::scope_css($cssWithoutComments);
 
     self::upsert_upload_folder('/' . $sub_path_inside_upload);
 
     file_put_contents($dest_path, $modifiedCss);
 
     return true;
   }

  private static function upsert_upload_folder($sub_folder = '')
  {
    $upload_dir = wp_upload_dir();
    $gutenbricks_dir = $upload_dir['basedir'] . '/gutenbricks' . $sub_folder;

    if (!is_dir($gutenbricks_dir)) {
      if (!mkdir($gutenbricks_dir, 0755, true)) {
        return false;
      }
    }

    return true;
  }

  private static function scope_css_selector($selector) {
    $selectorTrimmed = trim($selector);
    if ($selectorTrimmed === '.is-root-container') {
      return $selectorTrimmed;
    }

    if ($selectorTrimmed === 'html') {
      return 'html';
    }

    if ($selectorTrimmed === ':root') {
      return $selectorTrimmed;
    }

    // if selector starts with :root and followed by some spaces and another selector
    if (preg_match('/^:root\s+/', $selectorTrimmed)) {
      $root_remoted = preg_replace('/^:root\s+/', '', $selectorTrimmed);
      return ':root .gbrx-edit-block ' . $root_remoted;
    }

    // addons might override p tag styles, so we need to scope it
    if ($selectorTrimmed === 'p') {
      return '.gbrx-edit-block p:not(.wp-block)';
    }

    // @since 1.1 brx-body style needs to be scoped properly (credit to Matthias)
    if ($selectorTrimmed === 'body' || $selectorTrimmed === '.brx-body') {
      return 'body .is-root-container';
    }

    // Handle selectors containing 'body '
    if (strpos($selectorTrimmed, 'body ') === 0) {
      return preg_replace('/^body /', 'body .is-root-container ', $selectorTrimmed);
    }

    // @since RC5.1.1
    // To avoid something like:
    // ```
    //   @supports (padding: clamp(1vw,.is-root-container 2vw,.is-root-container 3vw))
    // ```
    // if $selectorTrimmed starts with a number or var( then we skip it
    if (preg_match('/^(\d+|var\()/i', $selectorTrimmed)) {
      return $selectorTrimmed;
    }

    return '.gbrx-edit-block ' . $selectorTrimmed;
  }

 
}