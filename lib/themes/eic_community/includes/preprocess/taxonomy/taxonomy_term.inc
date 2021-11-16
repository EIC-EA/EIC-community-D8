<?php

/**
 * @file
 * Contains hook_preprocess() implementation for taxonomy terms.
 */

use Drupal\eic_topics\Constants\Topics;

/**
 * Implements hook_preprocess_taxonomy_term().
 */
function eic_community_preprocess_taxonomy_term(&$variables) {
  /** @var \Drupal\taxonomy\TermInterface $term */
  $term = $variables['term'];

  $vid = $term->get('vid')->getValue();
  $vid = reset($vid)['target_id'];

  if (Topics::TERM_VOCABULARY_TOPICS_ID !== $vid) {
    return;
  }

  if ($variables['view_mode'] == 'full') {
    $banner_image = $term->get('field_topic_banner')->referencedEntities();
    $file = $banner_image ? File::load($banner_image[0]->get('oe_media_image')->target_id) : NULL;
    $file_url = $file ? file_url_transform_relative(file_create_url($file->get('uri')->value)) : NULL;
    $variables['banner_image_url'] = $file_url;
  }
}
