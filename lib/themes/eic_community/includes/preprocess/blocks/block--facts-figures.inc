<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block() for facts_figures.
 */

use Drupal\Core\Cache\Cache;

/**
 * Implements hook_preprocess_block() for facts_figures.
 */
function eic_community_preprocess_block__facts_figures(&$variables) {
  $block_content = $variables['content']['#block_content'];

  // Initializes cache tags.
  $cache_tags = $block_content->getCacheTags();

  if (!$block_content->get('field_facts_figures')->isEmpty()) {
    /** @var \Drupal\eic_statistics\StatisticsStorageInterface $eic_statistics_storage */
    $eic_statistics_storage = \Drupal::service('eic_statistics.storage');

    /** @var \Drupal\fragments\Entity\FragmentInterface[] $fragments */
    $fragments = $block_content->get('field_facts_figures')->referencedEntities();

    // Adds each fact and figure to the theme variables.
    foreach ($fragments as $fragment) {
      $fragment_translation = \Drupal::service('entity.repository')->getTranslationFromContext($fragment, $block_content->language()->getId());
      $cache_tags = Cache::mergeTags($cache_tags, $fragment->getCacheTags());

      // Extracts the entity type and bundle from
      // the value of field_fact_figure_type.
      $entity_info = explode('__', $fragment_translation->get('field_fact_figure_type')->getString());
      $entity_type = reset($entity_info);
      $bundle = end($entity_info);

      switch ($entity_type) {
        case 'user':
          $counter = $eic_statistics_storage->getEntityCounter($entity_type);
          $cache_tags[] = $eic_statistics_storage->getEntityCounterCacheTag($entity_type);
          break;

        default:
          $counter = $eic_statistics_storage->getEntityCounter($entity_type, $bundle);
          $cache_tags[] = $eic_statistics_storage->getEntityCounterCacheTag($entity_type, $bundle);
          break;

      }

      $variables['block_content']['items'][] = [
        'title' => eic_field_fact_figure_type_allowed_values()[$fragment_translation->get('field_fact_figure_type')->getString()],
        'description' => $fragment_translation->get('field_body')->value,
        'value' => $counter,
        'fact_figure_type' => $fragment_translation->get('field_fact_figure_type')->getString(),
      ];
    }
  }
}
