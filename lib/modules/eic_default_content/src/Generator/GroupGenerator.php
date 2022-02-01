<?php

namespace Drupal\eic_default_content\Generator;


use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;

/**
 * Class GroupGenerator
 *
 * @package Drupal\eic_default_content\Generator
 */
class GroupGenerator extends CoreGenerator {

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\group_flex\GroupFlexGroupSaver $group_flex_saver */
    $group_flex_saver = \Drupal::service('group_flex.group_saver');
    /** @var \Drupal\oec_group_features\GroupFeaturePluginManager $group_feature_manager */
    $group_feature_manager = \Drupal::service('plugin.manager.group_feature');
    $available_features = array_keys($group_feature_manager->getDefinitions());

    for ($i = 0; $i < 5; $i++) {
      $visibility = $i % 2 ? 'public' : 'private';
      $group_number = $i + 1;
      $values = [
        'label' => "Group #$group_number",
        'type' => 'group',
        'field_body' => $this->getFormattedText('full_html'),
        'field_welcome_message' => $this->getFormattedText('full_html'),
        'status' => TRUE,
        'moderation_state' => GroupsModerationHelper::GROUP_PUBLISHED_STATE,
        'field_hero' => $this->createMedia([
          'oe_media_image' => $this->getRandomImage(),
        ], 'image'),
        'field_thumbnail' => $this->createMedia([
          'oe_media_image' => $this->getRandomImage(),
        ], 'image'),
        'field_vocab_topics' => $this->getRandomEntities('taxonomy_term', ['vid' => 'topics'], 1),
        'field_vocab_geo' => $this->getRandomEntities('taxonomy_term', ['vid' => 'geo'], 1),
        'features' => $available_features,
        'uid' => 1,
      ];

      $group = Group::create($values);
      $group->save();
      $group_flex_saver->saveGroupVisibility($group, $visibility);

      $this->createDiscussions($group);
      $this->createGroupEvents($group);
    }
  }

  /**
   * Creates a single discussion for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createDiscussions(GroupInterface $group) {
    $content_type_id = $group
      ->getGroupType()
      ->getContentPlugin('group_node:discussion')
      ->getContentTypeConfigId();

    $node = Node::create([
      'field_body' => $this->getFormattedText('full_html'),
      'type' => 'discussion',
      'title' => 'Discussion in ' . $group->label(),
      'field_discussion_type' => 'idea',
      'field_vocab_topics' => $this->getRandomEntities('taxonomy_term', ['vid' => 'topics'], 3),
      'field_vocab_geo' => $this->getRandomEntities('taxonomy_term', ['vid' => 'geo'], 2),
      'status' => TRUE,
      'uid' => 1
    ]);

    $node->save();
    $group_content = GroupContent::create([
      'type' => $content_type_id,
      'gid' => $group->id(),
      'entity_id' => $node->id(),
    ]);
    $group_content->save();
  }

  /**
   * Creates a single group event (node) for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createGroupEvents(GroupInterface $group) {
    $content_type_id = $group
      ->getGroupType()
      ->getContentPlugin('group_node:event')
      ->getContentTypeConfigId();

    $node = Node::create([
      'field_body' => $this->getFormattedText('full_html'),
      'type' => 'event',
      'title' => 'Event in ' . $group->label(),
      'field_link' => 'https://myevent.site',
      'field_organised_by' => 'Someone',
      'field_vocab_event_type' => $this->getRandomEntities('taxonomy_term', ['vid' => 'event_type'], 1),
      'field_vocab_topics' => $this->getRandomEntities('taxonomy_term', ['vid' => 'topics'], 3),
      'field_vocab_geo' => $this->getRandomEntities('taxonomy_term', ['vid' => 'geo'], 2),
      'status' => TRUE,
      'uid' => 1
    ]);

    $node->save();
    $group_content = GroupContent::create([
      'type' => $content_type_id,
      'gid' => $group->id(),
      'entity_id' => $node->id(),
    ]);
    $group_content->save();
  }

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    $this->unloadEntities('group_permission');
    $this->unloadEntities('group');
  }

}
