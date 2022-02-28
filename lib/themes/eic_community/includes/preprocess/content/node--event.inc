<?php

/**
 * @file
 * Prepares variables for node Event templates.
 */

use Drupal\eic_helper\DateTimeHelper;
use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_node__event() for event node.
 */
function eic_community_preprocess_node__event(array &$variables) {
  /** @var \Drupal\node\NodeInterface $node */
  $node = $variables['node'];

  switch ($variables['view_mode']) {
    case 'full':
      _eic_community_render_event_detail_page($variables, $variables['node']);
      break;
    case 'teaser':
      $teaser = _eic_community_prepare_node_teaser_array($node);

      // Get the thumbnail.
      /** @var \Drupal\media\MediaInterface $media */
      if (!empty($medias = $node->get('field_image')->referencedEntities())) {
        $media = $medias[0];
        if ($media && !empty($media->get('oe_media_image')->getValue())) {
          $style_name = 'oe_theme_ratio_3_2_medium';
          $image_item = ImageValueObject::fromStyledImageItem($media->get('oe_media_image')->first(), $style_name);
          $teaser['image'] = [
            'src' => $image_item->getSource(),
            'alt' => $image_item->getAlt(),
          ];
        }
      }

      // Get the start date of the event.
      $start_date = 0;
      $end_date = 0;
      if (!$node->field_date_range->isEmpty()) {
        $start_date = strtotime($node->get('field_date_range')->value);
        $end_date = strtotime($node->get('field_date_range')->end_value);
      }
      $start_date_formatted = _eic_community_preprocess_ecl_date_block($start_date, NULL, DateTimeHelper::DATE_FORMAT_SHORT);
      $teaser['date'] = $start_date_formatted;
      $teaser['date']['start'] = $start_date_formatted;
      $teaser['date']['end'] = _eic_community_preprocess_ecl_date_block($end_date, NULL, DateTimeHelper::DATE_FORMAT_SHORT);
      $teaser['timestamp']['label'] = eic_community_get_teaser_time_display($node->get('published_at')->value);

      // Get the event state.
      $teaser['date']['variant'] = _eic_community_get_ecl_date_block_variant($node, 'field_date_range');
      $location_type = $node->get('field_location_type')->value;

      $location = $node->hasField('field_location') ?
        $node->get('field_location')->getValue() :
        NULL;
      $location_formatted = '';

      if (!empty($location)) {
        $location = reset($location);
        $city = $location ? $location['locality'] : '';
        $country = $node->get('field_vocab_geo')->entity;
        $locality_data = [
          $city,
          $country instanceof \Drupal\taxonomy\TermInterface ? $country->label() : NULL,
        ];

        $locality_data = array_filter($locality_data, function($locality) {
          return $locality;
        });

        $location_formatted = !empty($locality_data) ? implode(', ', $locality_data) : '';
      }

      $teaser['type'] = [
        'label' => $location_type == 'remote' ?
          t('Remote event', [], ['context' => 'eic_community']) :
          $location_formatted,
        'icon' => [
          'name' => $location_type === 'remote' ? 'video' : 'mappin',
          'type' => $location_type === 'remote' ? 'general' : 'custom',
        ],
      ];

      // Remove unwanted items.
      $teaser['flags'] = [];
      $teaser['stats'] = [];
      $teaser['icon_file_path'] = $variables['eic_icon_path'];

      // If we have flags, attach the js library.
      if (!empty($teaser['flags'])) {
        $variables['#attached']['library'][] = 'flag/flag.link_ajax';
      }

      $variables['event_item'] = $teaser;
      break;

  }

}
