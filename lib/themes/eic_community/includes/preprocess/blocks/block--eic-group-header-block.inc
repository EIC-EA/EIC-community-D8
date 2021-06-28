<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block() for eic_group_header.
 */

use Drupal\Core\Url;
use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_eic_group_header_block() for eic_group_header block.
 */
function eic_community_preprocess_eic_group_header_block(array &$variables) {
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $variables['group'];

  // Adds group banner image.
  if (!$group->get('field_hero')->isEmpty()) {
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($group->get('field_hero')->entity, $group->language()->getId());
    $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
    $variables['group_values']['image'] = [
      'src' => $image_item->getSource(),
      'alt' => $image_item->getAlt(),
    ];
  }

  // @todo replace the hardcoded items with structure coming form operation_links.
  $actions = [];

  // Adds group operation links.
  if (!empty($variables['group_values']['group_content_operation_links'])) {
    $actions[] = [
      'label' => t('Post content'),
      'items' => [],
    ];
    foreach ($variables['group_values']['group_content_operation_links'] as $action) {
      $actions[0]['items'][]['link'] = [
        'label' => $action['title'],
        'path' => $action['url'],
      ];
    }
  }

  // Adds membership links.
  foreach ($variables['group_values']['membership_links'] as $action) {
    $actions[]['items'][]['link'] = [
      'label' => $action['title'],
      'path' => $action['url'],
    ];
  }

  // @todo Re-adapt variables that will be needed for the cog wheel button.
  if (isset($variables['group_values']['group_operation_links']['edit'])) {
    $actions[]['items'][]['link'] = [
      'label' => $variables['group_values']['group_operation_links']['edit']['title'],
      'path' => $variables['group_values']['group_operation_links']['edit']['url'],
    ];
  }

  $variables['group_values']['actions'] = $actions;
  // @todo add content from module for these sections.
  $variables['group_values']['stats'] = [];
  $variables['group_values']['tags'] = [];
  // Adds url to navigate back to the list of groups.
  $back_url = Url::fromRoute('view.global_overviews.page_1');
  $variables['back_url'] = $back_url->toString();

}
