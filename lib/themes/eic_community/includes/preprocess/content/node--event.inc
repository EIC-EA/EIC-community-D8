<?php

/**
 * @file
 * Prepares variables for node Event templates.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Locale\CountryManager;
use Drupal\Core\Render\Markup;
use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\eic_helper\DateTimeHelper;
use Drupal\eic_overviews\GroupOverviewPages;
use Drupal\eic_search\SearchHelper;
use Drupal\group\Entity\GroupInterface;

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
    case 'mail_teaser':
    case 'small_teaser':
      $teaser = _eic_community_prepare_node_teaser_array($node);
      $teaser['description'] = Markup::create(Xss::filter($node->get('body')->value));
      // Get the thumbnail.
      /** @var \Drupal\media\MediaInterface $media */
      if (!empty($medias = $node->get('field_image')->referencedEntities())) {
        $media = $medias[0];
        if ($media && !empty($media->get('oe_media_image')->getValue())) {
          $style_name = 'gallery_teaser_crop_160x160';
          $image_item = ImageValueObject::fromStyledImageItem($media->get('oe_media_image')->first(), $style_name);
          $teaser['image'] = [
            'src' => $image_item->getSource(),
            'alt' => $image_item->getAlt(),
          ];
        }
      }

      // Privacy information, if current user is anonymous remove the author.
      if (\Drupal::currentUser()->isAnonymous()) {
        $teaser['author'] =  NULL;
      }

      // Get the start date of the event.
      $start_date = 0;
      $end_date = 0;
      if (!$node->field_date_range->isEmpty()) {
        $start_date = strtotime($node->get('field_date_range')->value);
        $end_date = strtotime($node->get('field_date_range')->end_value);
      }
      $start_date_formatted = _eic_community_preprocess_ecl_date_block(
        $start_date,
        NULL,
        DateTimeHelper::DATE_FORMAT_SHORT
      );
      $teaser['date'] = $start_date_formatted;
      $teaser['date']['start'] = $start_date_formatted;
      $teaser['date']['end'] = _eic_community_preprocess_ecl_date_block(
        $end_date,
        NULL,
        DateTimeHelper::DATE_FORMAT_SHORT
      );
      $teaser['timestamp']['label'] = eic_community_get_teaser_time_display($node->get('published_at')->value);

      // Get the event state.
      $teaser['date']['variant'] = _eic_community_get_ecl_date_block_variant($node, 'field_date_range');
      $locations_type = $node->get('field_location_type')->getValue();

      $location = $node->hasField('field_location') ?
        $node->get('field_location')->getValue() :
        NULL;

      $locations_type_data = [];
      foreach ($locations_type as $location_type) {
        if ('remote' === $location_type['value']) {
          $locations_type_data[] = [
            'label' => t('Remote event', [], ['context' => 'eic_community']),
            'icon' => [
              'name' => 'video',
              'type' => 'general',
            ],
          ];
        }
        // Handle on site.
        elseif (!empty($location)) {
          $location = reset($location);
          $city = $location ? $location['locality'] : '';
          $countries_map = CountryManager::getStandardList();
          $country = array_key_exists($location['country_code'], $countries_map) ?
            $countries_map[$location['country_code']] :
            '';
          $locality_data = [
            $city,
            $country,
          ];

          $locality_data = array_filter($locality_data, function ($locality) {
            return $locality;
          });

          $location_formatted = !empty($locality_data) ? implode(', ', $locality_data) : '';
          $locations_type_data[] = [
            'label' => $location_formatted,
            'icon' => [
              'name' => 'mappin',
              'type' => 'custom',
            ],
          ];
        }
      }

      $teaser['type'] = $locations_type_data;

      // Get the event type.
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = \Drupal::routeMatch()->getParameter('group');
      if (!$node->field_vocab_event_type->isEmpty() && $group instanceof GroupInterface) {
        /** @var \Drupal\taxonomy\Entity\Term $event_type */
        $event_type = $node->get('field_vocab_event_type')->entity;

        // Build solr query parameters for the events overview page to filter
        // by topic.
        $params = SearchHelper::buildSolrQueryParams(
          [
            'ss_content_event_type_string' => $event_type->label(),
          ]
        );

        $url = GroupOverviewPages::getGroupOverviewPageUrl('events', $group);
        $url->setOption('query', $params)->toString();

        $teaser['tags'] = [
          [
            'type' => 'link',
            'label' => $event_type->get('name')->getString(),
            'path' => $url,
            'aria_label' => '',
          ],
        ];
      }

      $flags_count = \Drupal::service('flag.count')->getEntityFlagCounts($node);
      /** @var \Drupal\eic_statistics\NodeStatisticsDatabaseStorage $node_stats_storage */
      $node_stats_storage = \Drupal::service('eic_statistics.statistics.storage.node.decorator');

      $stats[] = [
        'icon' => [
          'type' => 'custom',
          'name' => 'like',
        ],
        'label' => t('Likes'),
        'value' => array_key_exists('like_content', $flags_count) ?
          $flags_count['like_content'] :
          0,
      ];

      $stats_views = 0;

      if ($statistics_views_result = $node_stats_storage->fetchView($node->id())) {
        $stats_views = $statistics_views_result->getTotalCount();
      }

      $stats[] = [
        'icon' => [
          'type' => 'custom',
          'name' => 'views',
        ],
        'label' => t('Views'),
        'value' => $stats_views,
      ];

      // Remove unwanted items.
      $teaser['flags'] = [];
      $teaser['stats'] = $stats;
      $teaser['icon_file_path'] = $variables['eic_icon_path'];

      // If we have flags, attach the js library.
      if (!empty($teaser['flags'])) {
        $variables['#attached']['library'][] = 'flag/flag.link_ajax';
      }

      $variables['event_item'] = $teaser;
      break;

  }

}
