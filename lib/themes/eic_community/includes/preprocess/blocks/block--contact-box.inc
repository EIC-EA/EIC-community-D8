<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block() for contact_box.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * Implements hook_preprocess_block() for contact_box.
 */
function eic_community_preprocess_block__contact_box(&$variables, &$cache_tags = []) {
  /** @var \Drupal\block_content\Entity\BlockContent $block_content */
  $block_content = $variables['content']['#block_content'];

  $variables['contact_box'] = [
    'title' => $block_content->get('field_title')->value,
    'body' => Markup::create(
      Xss::filter(
        $block_content->get('body')->value,
        ['a', 'b', 'strong', 'u', 'em', 'br']
      )
    ),
    'cta' => [],
  ];

  if (!$block_content->get('field_cta_button')->isEmpty()) {
    $variables['contact_box']['cta'] = [
      'link' => Url::fromUri($block_content->get('field_cta_button')->uri)->toString(),
      'label' => $block_content->get('field_cta_button')->title,
    ];

  }
}
