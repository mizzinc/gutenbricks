<?php
/**
 * CSSParser Class
 *
 * A PHP class for parsing and manipulating CSS code. The CSSParser can handle standard CSS,
 * at-rules, and nested CSS (e.g., SCSS or LESS syntax). It parses the CSS into a hierarchical
 * structure, allowing for manipulation of selectors and declarations, and renders the CSS back
 * into a string.
 *
 * Features:
 * - Parses CSS into a structured format.
 * - Handles at-rules (e.g., @media, @charset) with or without blocks.
 * - Supports nested CSS by treating nested rules as part of the declarations of the outermost selector.
 * - Allows updating selectors throughout the entire CSS structure, including within at-rules.
 * - Renders the CSS back into a string with options for compact or formatted output.
 *
 * Usage:
 *
 * ```php
 * // Include or require the CSSParser class
 * require_once 'CSSParser.php';
 *
 * // Sample CSS input
 * $css = <<<CSS
 * .login-form {
 *     color: #333;
 *     & .input {
 *         border: 1px solid #ccc;
 *     }
 * }
 *
 * @media screen and (max-width: 600px) {
 *     .login-form {
 *         font-size: 14px;
 *     }
 * }
 * CSS;
 *
 * // Initialize the parser with the CSS string
 * $parser = new CSSParser($css);
 *
 * // Update selectors to prepend a class (e.g., '.scope')
 * $parser->updateSelectors(function ($selector) {
 *     return '.scope ' . $selector;
 * });
 *
 * // Render the updated CSS
 * $updatedCSS = $parser->render();
 * echo $updatedCSS;
 * ```
 *
 * Methods:
 *
 * - **__construct($cssString = '')**
 *   - Initializes the parser with a CSS string and parses it.
 *   - **Parameters:**
 *     - `$cssString` (string): The CSS code to parse.
 *
 * - **updateSelectors(callable $callback)**
 *   - Updates selectors within the parsed CSS structure using a callback function.
 *   - **Parameters:**
 *     - `$callback` (callable): A function that takes an individual selector string and returns the updated selector.
 *   - **Usage:**
 *     ```php
 *     $parser->updateSelectors(function ($selector) {
 *         return '.scope ' . $selector;
 *     });
 *     ```
 *
 * - **render($compact = false)**
 *   - Renders the CSS data back into a CSS string.
 *   - **Parameters:**
 *     - `$compact` (bool): If `true`, renders the CSS in a compact form without unnecessary whitespace.
 *   - **Returns:**
 *     - (string): The rendered CSS string.
 *   - **Usage:**
 *     ```php
 *     $cssOutput = $parser->render();
 *     ```
 *
 * Notes:
 *
 * - **Nested CSS Handling:**
 *   - The parser treats nested CSS (like in SCSS or LESS) by saving the outermost selector and including all nested content within its declarations.
 *   - Nested selectors within declarations are not individually parsed or updated.
 *   - To manipulate nested selectors individually, a more advanced parser would be required.
 *
 * - **At-Rules Handling:**
 *   - At-rules (e.g., `@media`, `@charset`) are correctly parsed, and their contents are handled similarly to other rules.
 *   - The `updateSelectors` method recursively updates selectors within at-rules.
 *
 * - **Comments:**
 *   - Comments within the CSS are preserved within the declarations.
 *
 * Example:
 *
 * .container {
 *     width: 100%;
 *     & .item {
 *         display: inline-block;
 *     }
 * }
 *
 * @media (max-width: 600px) {
 *     .container {
 *         width: 100%;
 *     }
 * }
 * CSS;
 *
 * $parser = new CSSParser($css);
 *
 * // Update selectors to add a data attribute
 * $parser->updateSelectors(function ($selector) {
 *     return $selector . '[data-active="true"]';
 * });
 *
 * // Get the rendered CSS
 * $updatedCSS = $parser->render();
 * echo $updatedCSS;
 * ```
 *
 * Output:
 *
 * ```
 * .container[data-active="true"] {
 *     width: 100%;
 *     & .item {
 *         display: inline-block;
 *     }
 * }
 * @media (max-width: 600px) {
 *     .container[data-active="true"] {
 *         width: 100%;
 *     }
 * }
 * ```
 */
namespace Gutenbricks\Utilities;

class CSSParser
{
  private $cssString;
  private $parsedCSS = [];

  /**
   * Constructor to initialize the CSS string and parse it.
   *
   * @param string $cssString The CSS string to parse.
   */
  public function __construct($cssString = '')
  {
    if (!empty($cssString)) {
      $this->cssString = $cssString;
      $this->parseCSS();
    }
  }

  /**
   * Parses the CSS string into a hierarchical structure.
   */
  private function parseCSS()
  {
    // Remove comments
    $css = preg_replace('/\/\*[\s\S]*?\*\//', '', $this->cssString);
    $length = strlen($css);
    $position = 0;

    $this->parsedCSS = $this->parseRules($css, $position, $length);
  }

  /**
   * Recursively parses CSS rules.
   */
  private function parseRules($css, &$position, $length)
  {
    $rules = [];

    while ($position < $length) {
      // Skip whitespace
      while ($position < $length && ctype_space($css[$position])) {
        $position++;
      }

      // If we reach the end, break
      if ($position >= $length) {
        break;
      }

      // Check for at-rule
      if ($css[$position] === '@') {
        $atRule = $this->parseAtRule($css, $position, $length);
        if ($atRule) {
          $rules[] = $atRule;
        } else {
          // Skip invalid at-rule
          $position++;
        }
      } else {
        // Parse selector rule
        $rule = $this->parseRule($css, $position, $length);
        if ($rule) {
          $rules[] = $rule;
        } else {
          // Skip invalid rule
          $position++;
        }
      }
    }

    return $rules;
  }

  /**
   * Parses an at-rule.
   */
  private function parseAtRule($css, &$position, $length)
  {
    // Read at-rule name
    $start = $position;
    $position++; // Skip '@'
    while ($position < $length && (ctype_alpha($css[$position]) || $css[$position] === '-')) {
      $position++;
    }
    $name = '@' . substr($css, $start + 1, $position - $start - 1);

    // Skip whitespace
    while ($position < $length && ctype_space($css[$position])) {
      $position++;
    }

    // Read parameters
    $params = '';
    while ($position < $length && $css[$position] !== '{' && $css[$position] !== ';') {
      $params .= $css[$position];
      $position++;
    }
    $params = trim($params);

    if ($position < $length && $css[$position] === ';') {
      // At-rule without block
      $position++; // Skip ';'
      return [
        'type' => 'at-rule',
        'name' => $name,
        'params' => $params,
        'rules' => null,
      ];
    } elseif ($position < $length && $css[$position] === '{') {
      // At-rule with block
      $position++; // Skip '{'

      // Read the block with brace depth tracking
      $contentStart = $position;
      $braceDepth = 1;
      while ($position < $length && $braceDepth > 0) {
        $char = $css[$position];
        if ($char === '{') {
          $braceDepth++;
        } elseif ($char === '}') {
          $braceDepth--;
        }
        $position++;
      }

      $blockContent = substr($css, $contentStart, $position - $contentStart - 1); // Exclude the last '}'

      // Parse the nested rules within the block
      $nestedPosition = 0;
      $nestedLength = strlen($blockContent);
      $rules = $this->parseRules($blockContent, $nestedPosition, $nestedLength);

      return [
        'type' => 'at-rule',
        'name' => $name,
        'params' => $params,
        'rules' => $rules,
      ];
    } else {
      // Invalid at-rule
      return null;
    }
  }

  /**
   * Parses a selector rule, including nested rules.
   */
  private function parseRule($css, &$position, $length)
  {
    // Read selector
    $selector = '';
    $parenDepth = 0;
    while ($position < $length && ($css[$position] !== '{' || $parenDepth > 0)) {
      $char = $css[$position];
      if ($char === '(') {
        $parenDepth++;
      } elseif ($char === ')') {
        $parenDepth--;
      }
      $selector .= $char;
      $position++;
    }
    $selector = trim($selector);

    // Skip '{'
    if ($position < $length && $css[$position] === '{') {
      $position++;
    } else {
      // Invalid rule
      return null;
    }

    // Read declarations including nested content
    $declarations = '';
    $braceDepth = 1;
    while ($position < $length && $braceDepth > 0) {
      $char = $css[$position];

      if ($char === '{') {
        $braceDepth++;
      } elseif ($char === '}') {
        $braceDepth--;
      }

      if ($braceDepth > 0) {
        $declarations .= $char;
      }

      $position++;
    }

    // Trim declarations
    $declarations = trim($declarations);

    return [
      'type' => 'rule',
      'selector' => $selector,
      'declarations' => $declarations,
    ];
  }

  /**
   * Updates selectors within the parsed CSS structure.
   *
   * @param callable $callback A function that takes an individual selector string and returns the updated selector.
   */
  public function updateSelectors(callable $callback)
  {
    foreach ($this->parsedCSS as &$rule) {
      $this->updateSelectorsRecursive($rule, $callback);
    }
  }

  /**
   * Recursively updates selectors.
   */
  private function updateSelectorsRecursive(&$rule, callable $callback)
  {
    if ($rule['type'] === 'rule') {
      $selectors = preg_split('/,(?![^(]*\))/', $rule['selector']);
      $newSelectors = [];
      foreach ($selectors as $selector) {
        $selector = trim($selector);
        // Check if the selector contains a pseudo-class function
        if (preg_match('/:[a-zA-Z-]+\(/', $selector)) {
          // If it does, apply the callback to the whole selector
          $newSelectors[] = $callback($selector);
        } else {
          // Otherwise, apply callback to the entire selector
          $newSelectors[] = $callback($selector);
        }
      }
      $rule['selector'] = implode(',', $newSelectors);
    } elseif ($rule['type'] === 'at-rule' && is_array($rule['rules'])) {
      foreach ($rule['rules'] as &$nestedRule) {
        $this->updateSelectorsRecursive($nestedRule, $callback);
      }
    }
  }

  /**
   * Renders the CSS data back into a CSS string.
   */
  public function render($compact = false)
  {
    return $this->renderRules($this->parsedCSS, $compact);
  }

  /**
   * Recursively renders CSS rules.
   */
  private function renderRules($rules, $compact, $indentLevel = 0)
  {
    $cssString = '';
    $indent = str_repeat('    ', $indentLevel);

    foreach ($rules as $rule) {
      if ($rule['type'] === 'rule') {
        if ($compact) {
          $declarations = preg_replace('/\s+/', ' ', $rule['declarations']);
          $declarations = preg_replace('/\s*:\s*/', ':', $declarations);
          $declarations = preg_replace('/;\s*/', ';', $declarations);
          $cssString .= $rule['selector'] . '{' . $declarations . '}';
        } else {
          $cssString .= $indent . $rule['selector'] . " {\n";
          $cssString .= $this->indentContent($rule['declarations'], $indentLevel + 1) . "\n";
          $cssString .= $indent . "}\n";
        }
      } elseif ($rule['type'] === 'at-rule') {
        if ($rule['rules'] === null) {
          // At-rule without block
          $cssString .= $indent . $rule['name'] . ' ' . $rule['params'] . ";\n";
        } else {
          // At-rule with block
          $cssString .= $indent . $rule['name'] . ' ' . $rule['params'] . " {\n";
          $cssString .= $this->renderRules($rule['rules'], $compact, $indentLevel + 1);
          $cssString .= $indent . "}\n";
        }
      }
    }

    return $cssString;
  }

  /**
   * Indents content for formatted output.
   */
  private function indentContent($content, $indentLevel)
  {
    $indent = str_repeat('    ', $indentLevel);
    $lines = preg_split('/\r\n|\r|\n/', $content);
    $indentedLines = array_map(function ($line) use ($indent) {
      return $indent . $line;
    }, $lines);
    return implode("\n", $indentedLines);
  }


  /**
   * Returns the parsed CSS rules.
   *
   * @return array The parsed CSS rules.
   */
  public function getRules()
  {
    return $this->parsedCSS;
  }
}



