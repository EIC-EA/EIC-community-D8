<?php

namespace Drupal\eic_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Sanitizes the given html string.
 *
 * @endcode
 * process:
 *   bar:
 *     plugin: eic_html_sanitizer
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "eic_html_sanitizer"
 * )
 */
class HtmlSanitizer extends ProcessPluginBase {

  /**
   * List of disallowed HTML attributes.
   *
   * @var string[]
   */
  protected const DISALLOWED_ATTRIBUTES = [
    'aria-setsize',
    'data-aria-level',
    'data-aria-posinset',
    'data-font',
    'data-leveltext',
    'data-listid',
    'paraeid',
    'paraid',
  ];

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $this->stripAttributes($value, self::DISALLOWED_ATTRIBUTES);
  }

  /**
   * Removes the HTML attributes for the given string.
   *
   * @param string|null $html
   *   The HTML to sanitize.
   * @param array $attributes
   *   Array of attributes to remove.
   *
   * @return string
   *   The sanitized HTML.
   */
  public function stripAttributes(string $html = NULL, array $attributes = []) {
    // Don't do anything if HTML is empty.
    if (empty($html)) {
      return $html;
    }

    // Find all nodes that contain the attribute and remove it.
    $html = '<div>' . $html . '</div>';
    $dom = new \DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR);
    $xPath = new \DOMXPath($dom);
    foreach ($attributes as $attribute) {
      $nodes = $xPath->query('//*[@' . $attribute . ']');
      foreach ($nodes as $node) {
        $node->removeAttribute($attribute);
      }
    }
    return substr($dom->saveHTML($dom->getElementsByTagName('div')->item(0)), 5, -6);
  }

}
