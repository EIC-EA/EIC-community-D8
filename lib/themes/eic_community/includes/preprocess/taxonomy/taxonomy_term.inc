<?php

/**
 * @file
 * Contains hook_preprocess() implementation for taxonomy terms.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\eic_topics\Constants\Topics;
use Drupal\file\Entity\File;

/**
 * Implements hook_preprocess_page__taxonomy__term().
 */
function eic_community_preprocess_page__taxonomy__term(&$variables) {
  /** @var \Drupal\taxonomy\TermInterface $term */
  $term = \Drupal::routeMatch()->getParameter('taxonomy_term');

  if (!$term) {
    return;
  }

  if ($term->bundle() !== Topics::TERM_VOCABULARY_TOPICS_ID) {
    return;
  }

  $banner_image = $term->get('field_topic_banner')->referencedEntities();
  $file = $banner_image ? File::load($banner_image[0]->get('oe_media_image')->target_id) : NULL;
  $file_url = $file ? \Drupal::service('file_url_generator')->transformRelative(file_create_url($file->get('uri')->value)) : NULL;
  $variables['banner_image_url'] = $file_url;
  $variables['topic_title'] = $term->label();
  // Adds breadcrumbs block to the theme variables.
  $variables['eic_community_breadcrumbs'] = _eic_community_load_breadcrumb_block();
}

/**
 * Implements hook_preprocess_page__taxonomy__term().
 */
function eic_community_preprocess_taxonomy_term(&$variables) {
  /** @var \Drupal\taxonomy\TermInterface $term */
  $term = $variables['elements']['#taxonomy_term'];

  if ($term->bundle() !== Topics::TERM_VOCABULARY_TOPICS_ID) {
    return;
  }

  switch ($variables['view_mode']) {

    case 'teaser':
      $teaser = [
        'title' => $term->label(),
        'url' => $term->toUrl(),
      ];

      // Get the thumbnail.
      if (!$term->get('field_topic_banner')->isEmpty()) {
        /** @var \Drupal\media\MediaInterface $media */
        $media = $term->get('field_topic_banner')->referencedEntities()[0];
        if ($media && !empty($media->get('oe_media_image')->getValue())) {
          $style_name = 'gallery_teaser_crop_160x160';
          $teaser['image'] = ImageValueObject::fromStyledImageItem($media->get('oe_media_image')->first(), $style_name);
        }
      }

      $needed_stats = ['experts', 'organisations'];
      $stats = _eic_community_get_entity_stats($term, $needed_stats);
      $teaser['stats'] = $stats;

      $variables['term_item'] = $teaser;
      $variables['icon_file_path'] = $variables['eic_icon_path'];
      break;
  }

}
