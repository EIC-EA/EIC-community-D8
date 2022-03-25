<?php

namespace Drupal\eic_wysiwyg\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to prepend Cookie Consent Kit on iframe elements.
 *
 * @Filter(
 *   id = "eic_filter_div_tables",
 *   title = @Translation("Wrap tables with div tag"),
 *   description = @Translation("Wraps <code>table</code> elements with a <code>div</code>."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterTableDivs extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['div_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Div classes'),
      '#default_value' => $this->settings['div_classes'] ?? '',
      '#description' => $this->t('Separate classes by spaces.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'table') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      // Create the div element.
      $new_div = $dom->createElement('div');
      if (!empty($this->settings['div_classes'])) {
        $new_div->setAttribute('class', $this->settings['div_classes']);
      }

      // Find table elements.
      /** @var \DOMNode $node */
      foreach ($xpath->query('//table') as $node) {
        $new_div_clone = $new_div->cloneNode();
        $node->parentNode->replaceChild($new_div_clone, $node);
        $new_div_clone->appendChild($node);
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

}
