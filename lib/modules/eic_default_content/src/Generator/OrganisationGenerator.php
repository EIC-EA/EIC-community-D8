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
    /** @var \Drupal\oec_group_features\GroupFeaturePluginManager $group_feature_manager */
    $group_feature_manager = \Drupal::service('plugin.manager.group_feature');
    $available_features = array_keys($group_feature_manager->getDefinitions());

    for ($i = 0; $i < 5; $i++) {
      $group_number = $i + 1;
      $values = [
        'label' => "Organisation #$group_number",
        'type' => 'organisation',
        //'field_body' => $this->getFormattedText('full_html'),
        'status' => TRUE,
        'moderation_state' => GroupsModerationHelper::GROUP_PUBLISHED_STATE,
//        'field_hero' => $this->createMedia([
//          'oe_media_image' => $this->getRandomImage(),
//        ], 'image'),
//        'field_thumbnail' => $this->createMedia([
//          'oe_media_image' => $this->getRandomImage(),
//        ], 'image'),
//        'field_vocab_topics' => $this->getRandomEntities('taxonomy_term', ['vid' => 'topics'], 1),
        'field_organisation_type' => $this->getRandomEntities('taxonomy_term', ['vid' => 'organisation_types'], 3),
        'features' => $available_features,
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
