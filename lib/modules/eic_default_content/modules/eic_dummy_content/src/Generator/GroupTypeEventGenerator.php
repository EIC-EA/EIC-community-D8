<?php

namespace Drupal\eic_dummy_content\Generator;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_default_content\Generator\CoreGenerator;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;

/**
 * Class to generate global events using fixtures.
 *
 * @package Drupal\eic_dummy_content\Generator
 */
class GroupTypeEventGenerator extends CoreGenerator {

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

      switch ($this->faker->randomNumber(1, TRUE)) {
        case 1:
          // On going event.
          $start_date = new DrupalDateTime('-2 days');
          $end_date = new DrupalDateTime('+2 days');
          break;

        case 2:
          // Future event.
          $start_date = new DrupalDateTime('+2 days');
          $end_date = new DrupalDateTime('+4 days');
          break;

        default:
          // Past event.
          $start_date = new DrupalDateTime('-4 days');
          $end_date = new DrupalDateTime('-2 days');
          break;

      }

      $values = [
        'label' => "Global Event #$group_number",
        'type' => 'event',
        'field_body' => $this->getFormattedText('full_html'),
        'field_tag_line' => 'EIC event',
        'field_welcome_message' => $this->getFormattedText('full_html'),
        'status' => TRUE,
        'moderation_state' => GroupsModerationHelper::GROUP_PUBLISHED_STATE,
        'field_header_visual' => $this->createMedia([
          'oe_media_image' => $this->getRandomImage(),
        ], 'image'),
        'field_image' => $this->createMedia([
          'oe_media_image' => $this->getRandomImage(),
        ], 'image'),
        'field_documents' => [
          $this->createMedia([
            'field_body' => $this->getFormattedText('full_html'),
            'field_media_file' => $this->getRandomImage(),
            'field_language' => $this->getRandomEntities('taxonomy_term', ['vid' => 'languages'], 1),
          ], 'eic_document'),
        ],
        'field_date_range' => [
          'value'=> $start_date->format('Y-m-d\TH:i:s'),
          'end_value' => $end_date->format('Y-m-d\TH:i:s'),
        ],
        'field_event_registration_date' => [
          'value'=> $start_date->format('Y-m-d\TH:i:s'),
          'end_value' => $end_date->format('Y-m-d\TH:i:s'),
        ],
        'field_organised_by' => 'EIC Admin',
        'field_link' => 'https://myevent.site',
        'field_website_url' => 'https://myevent.site',
        'field_location_type' => $i % 2 ? 'on_site' : 'remote',
        'field_location' => [
          'country_code' => 'BE',
          'address_line1' => 'Grand Place',
          'locality' => 'Bruxelles',
          'postal_code' => '1000',
        ],
        'field_vocab_event_type' => $this->getRandomEntities('group', ['type' => 'group'], 2),
        'field_vocab_event_type' => $this->getRandomEntities('taxonomy_term', ['vid' => 'event_type'], 1),
        'field_vocab_topics' => $this->getRandomEntities('taxonomy_term', ['vid' => 'topics'], 1),
        'field_vocab_geo' => $this->getRandomEntities('taxonomy_term', ['vid' => 'geo'], 1),
        'field_vocab_language' => $this->getRandomEntities('taxonomy_term', ['vid' => 'languages'], 1),
        'field_funding_source' => $this->getRandomEntities('taxonomy_term', ['vid' => 'funding_source'], 1),
        'features' => $available_features,
        'uid' => 1,
      ];

      $group = Group::create($values);
      $group->save();
      $this->groupFlexSaver->saveGroupVisibility($group, $visibility);

      // Update moderation state of group book page.
      $book = Node::load($this->eicGroupsHelper->getGroupBookPage($group));
      $book->set('moderation_state', DefaultContentModerationStates::PUBLISHED_STATE);
      $book->save();

      $this->createDiscussions($group);
      $this->createWikiPages($group);
    }
  }

  /**
   * Creates a single discussion for the given group event.
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
      'moderation_state' => DefaultContentModerationStates::PUBLISHED_STATE,
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
   * Creates multiple wiki pages for the given group event.
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
        'moderation_state' => DefaultContentModerationStates::PUBLISHED_STATE,
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

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    $this->unloadEntities('group_permission');
    $this->unloadEntities('group');
    return;
  }

}
