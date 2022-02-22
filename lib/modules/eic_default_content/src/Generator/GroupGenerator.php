<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;

/**
 * Class to generate groups using fixtures.
 *
 * @package Drupal\eic_default_content\Generator
 */
class GroupGenerator extends CoreGenerator {

  /**
   * Group flex saver service.
   *
   * @var \Drupal\group_flex\GroupFlexGroupSaver
   */
  protected $groupFlexSaver;

  /**
   * Group feature plugin manager.
   *
   * @var \Drupal\oec_group_features\GroupFeaturePluginManager
   */
  protected $groupFeatureManager;

  /**
   * The EIC groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  protected $eicGroupsHelper;

  /**
   * The book manager service.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->groupFlexSaver = \Drupal::service('group_flex.group_saver');
    $this->groupFeatureManager = \Drupal::service('plugin.manager.group_feature');
    $this->eicGroupsHelper = \Drupal::service('eic_groups.helper');
    $this->bookManager = \Drupal::service('book.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $available_features = array_keys($this->groupFeatureManager->getDefinitions());

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
      $this->groupFlexSaver->saveGroupVisibility($group, $visibility);

      $this->createDiscussions($group);
      $this->createGroupEvents($group);
      $this->createWikiPages($group);
    }
  }

  /**
   * Creates a single discussion for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
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
      'uid' => 1,
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
   *   The group entity.
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
      'uid' => 1,
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

  /**
   * Creates multiple wiki pages for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   */
  private function createWikiPages(GroupInterface $group) {
    $content_type_id = $group
      ->getGroupType()
      ->getContentPlugin('group_node:wiki_page')
      ->getContentTypeConfigId();

    // Prepare wiki page book outline.
    $book_nid = $this->eicGroupsHelper->getGroupBookPage($group);
    $wiki_page_book = $this->bookManager->getLinkDefaults(0);
    $wiki_page_book['bid'] = $wiki_page_book['original_bid'] = $book_nid;

    // Creates wiki pages.
    for ($i = 0; $i < 1; $i++) {
      $node = Node::create([
        'field_body' => $this->getFormattedText('full_html'),
        'type' => 'wiki_page',
        'title' => 'Wiki page #' . ($i + 1) . ' in ' . $group->label(),
        'field_vocab_topics' => $this->getRandomEntities('taxonomy_term', ['vid' => 'topics'], 3),
        'field_vocab_geo' => $this->getRandomEntities('taxonomy_term', ['vid' => 'geo'], 2),
        'status' => TRUE,
        'uid' => 1,
        'moderation_state' => 'published',
        'book' => $wiki_page_book,
      ]);

      // Sets the wiki page book parent.
      $node->book['pid'] = $book_nid;
      // Sets book weight.
      $node->book['weight'] = $i;

      $node->save();

      // Saves the group content.
      $group_content = GroupContent::create([
        'type' => $content_type_id,
        'gid' => $group->id(),
        'entity_id' => $node->id(),
      ]);
      $group_content->save();
    }
  }

}
