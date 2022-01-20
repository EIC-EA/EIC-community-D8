<?php

namespace Drupal\eic_default_content\Generator;


use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\group\Entity\Group;

/**
 * Class GroupGenerator
 *
 * @package Drupal\eic_default_content\Generator
 */
class OrganisationGenerator extends CoreGenerator {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $organisations_sample = [
      'Prophets',
      'Blue4You',
      'Foreach',
    ];
    /** @var \Drupal\oec_group_features\GroupFeaturePluginManager $group_feature_manager */
    $group_feature_manager = \Drupal::service('plugin.manager.group_feature');

    foreach ($organisations_sample as $organisation_sample) {
      $values = [
        'label' => $organisation_sample,
        'type' => 'organisation',
        'status' => TRUE,
        'field_body' => $this->getFormattedText('full_html'),
        'moderation_state' => GroupsModerationHelper::GROUP_PUBLISHED_STATE,
        'field_date_establishement' => random_int(1900, 2021),
        'field_organisation_turnover' => random_int(2000, 20000),
        'field_vocab_services_products' => $this->getRandomEntities('taxonomy_term', ['vid' => 'services_and_products'], 1),
        'field_vocab_target_markets' => $this->getRandomEntities('taxonomy_term', ['vid' => 'target_markets'], 1),
        'field_vocab_topics' => $this->getRandomEntities('taxonomy_term', ['vid' => 'topics'], 1),
        'field_header_visual' => $this->createMedia([
          'oe_media_image' => $this->getRandomImage(),
        ], 'image'),
        'field_organisation_type' => $this->getRandomEntities('taxonomy_term', ['vid' => 'organisation_types'], 3),
        'uid' => 1,
      ];

      $group = Group::create($values);
      $group->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    $this->unloadEntities('group_permission');
    $this->unloadEntities('group');
  }

}
