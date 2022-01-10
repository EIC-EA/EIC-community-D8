<?php

/**
 * @file
 * Prepares variables for group type organisation templates.
 */

use Drupal\taxonomy\TermInterface;

/**
 * Implements hook_preprocess_group__TYPE().
 */
function eic_community_preprocess_group__organisation(&$variables) {
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $variables['group'];

  switch ($variables['view_mode']) {
    case 'full':
      _eic_community_render_organisation_detail_page($variables, $group);
      break;

    case 'teaser':
    case 'small_teaser':
      // Add event topics to the theme variables.
      if (!$group->get('field_vocab_topics')->isEmpty()) {
        $topics = $group->get('field_vocab_topics')->referencedEntities();
      }
  }
}

/**
 * Fill informations for organisation detail page full.
 *
 * @param $variables
 * @param $group
 *
 * @throws \Drupal\Core\Entity\EntityMalformedException
 */
function _eic_community_render_organisation_detail_page(&$variables, $group) {
  /** @var \Drupal\eic_group_statistics\GroupStatisticsHelperInterface $group_helper */
  $group_helper = Drupal::service('eic_group_statistics.helper');
  $group_statistics = $group_helper->loadGroupStatistics($group);

  $topics = $group->get('field_vocab_topics')->referencedEntities();
  $markets = $group->get('field_vocab_target_markets')->referencedEntities();
  $products = $group->get('field_vocab_services_products')->referencedEntities(
  );

  $variables['details'] = [
    'title' => t('Organisation details', [], ['context' => 'eic_community']),
    'items' => [
      [
        'title' => t('Organisation', [], ['context' => 'eic_community']),
        'type' => 'stats',
        'items' => [
          [
            'name' => t('Employees', [], ['context' => 'eic_community']),
            'value' => $group->get('field_organisation_employees')->value,
          ],
          [
            'name' => t('Active members', [], ['context' => 'eic_community']),
            'value' => $group_statistics->getMembersCount(),
          ],
          [
            'name' => t('Annual turnover', [], ['context' => 'eic_community']),
            'value' => $group->get('field_organisation_turnover')->value,
          ],
          [
            'name' => t(
              'Date of establishement',
              [],
              ['context' => 'eic_community']
            ),
            'value' => $group->get('field_date_establishement')->value,
          ],
        ],
      ],
      [
        'title' => t(
          'Services and products offered',
          [],
          ['context' => 'eic_community']
        ),
        'type' => 'tags',
        'title_element' => 'h4',
        'icon_file_path' => $variables['eic_icon_path'],
        'is_collapsible' => TRUE,
        'grid' => TRUE,
        'collapse_label' => 'Less',
        'expand_label' => 'More',
        'items' => array_map(function (TermInterface $term) {
          return
            [
              'tag' => [
                'type' => 'link',
                'path' => $term->toUrl()->toString(),
                'label' => $term->getName(),
              ],
            ];
        }, $products),
      ],
      [
        'title' => t('Target markets', [], ['context' => 'eic_community']),
        'type' => 'tags',
        'title_element' => 'h4',
        'icon_file_path' => $variables['eic_icon_path'],
        'is_collapsible' => TRUE,
        'grid' => TRUE,
        'collapse_label' => 'Less',
        'expand_label' => 'More',
        'items' => array_map(function (TermInterface $term) {
          return
            [
              'tag' => [
                'type' => 'link',
                'path' => $term->toUrl()->toString(),
                'label' => $term->getName(),
              ],
            ];
        }, $markets),
      ],
      [
        'title' => t('Topics', [], ['context' => 'eic_community']),
        'type' => 'tags',
        'title_element' => 'h4',
        'icon_file_path' => $variables['eic_icon_path'],
        'is_collapsible' => TRUE,
        'grid' => TRUE,
        'collapse_label' => 'Less',
        'expand_label' => 'More',
        'items' => array_map(function (TermInterface $term) {
          return
            [
              'tag' => [
                'type' => 'link',
                'path' => $term->toUrl()->toString(),
                'label' => $term->getName(),
              ],
            ];
        }, $topics),
      ],
    ],
  ];
}
