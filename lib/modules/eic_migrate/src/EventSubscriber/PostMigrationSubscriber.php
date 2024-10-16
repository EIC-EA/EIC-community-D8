<?php

namespace Drupal\eic_migrate\EventSubscriber;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\Constants\GroupJoiningMethodType;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_organisations\Constants\Organisations;
use Drupal\eic_projects\PostMigrationCordisProject;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_flex\GroupFlexGroupSaver;
use Drupal\group_flex\Plugin\GroupVisibilityInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\MigrateLookup;
use Drupal\oec_group_features\GroupFeatureHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Perform post migration tasks.
 *
 * @package Drupal\eic_migrate
 */
class PostMigrationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Maps the old group features names to the new ones.
   *
   * @var array
   */
  const GROUP_FEATURES_MAPPING = [
    'event' => [
      'c4m_features_og_documents' => 'eic_groups_files',
      'c4m_features_og_highlights' => NULL,
      'c4m_features_og_media' => 'eic_groups_files',
      'c4m_features_og_members' => 'eic_groups_members',
      'c4m_features_og_news' => 'eic_groups_news',
      'c4m_features_og_wiki' => 'eic_groups_wiki',
    ],
    'group' => [
      'c4m_features_og_discussions' => 'eic_groups_discussions',
      'c4m_features_og_documents' => 'eic_groups_files',
      'c4m_features_og_events' => 'eic_groups_group_events',
      'c4m_features_og_highlights' => NULL,
      'c4m_features_og_media' => 'eic_groups_files',
      'c4m_features_og_members' => 'eic_groups_members',
      'c4m_features_og_news' => 'eic_groups_news',
      'c4m_features_og_wiki' => 'eic_groups_wiki',
    ],
    'organisation' => [
      'c4m_features_og_events' => 'eic_groups_anchor_group_events',
      'c4m_features_og_media' => NULL,
      'c4m_features_og_members' => 'eic_groups_anchor_members',
      'c4m_features_og_news' => 'eic_groups_anchor_news',
    ],
  ];

  /**
   * Group features that we enable by default.
   *
   * @var array
   */
  const GROUP_FEATURES_ENABLED_BY_DEFAULT = [
    'eic_groups_latest_activity_stream',
  ];

  /**
   * The database connection to the migrate DB.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookup
   */
  protected $migrateLookup;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Group Flex Group saver service.
   *
   * @var \Drupal\group_flex\GroupFlexGroupSaver
   */
  protected $groupFlexGroupSaver;

  /**
   * The OEC Group feature helper.
   *
   * @var \Drupal\oec_group_features\GroupFeatureHelper
   */
  protected $groupFeatureHelper;

  /**
   *
   * @var \Drupal\eic_projects\PostMigrationCordisProject
   */
  protected $postMigrationCordis;

  /**
   * Constructs a new MessageCreatorBase object.
   *
   * @param \Drupal\migrate\MigrateLookup $migrate_lookup
   *   The migrate lookup service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group_flex\GroupFlexGroupSaver $group_flex_group_saver
   *   The Group Flex Group saver service.
   * @param \Drupal\oec_group_features\GroupFeatureHelper $group_feature_helper
   *   The entity storage.
   */
  public function __construct(
    MigrateLookup $migrate_lookup,
    EntityTypeManagerInterface $entity_type_manager,
    GroupFlexGroupSaver $group_flex_group_saver,
    GroupFeatureHelper $group_feature_helper,
    PostMigrationCordisProject $postMigrationCordisProject,
  ) {
    $connection = Database::getConnection('default', 'migrate');

    $this->connection = $connection;
    $this->migrateLookup = $migrate_lookup;
    $this->entityTypeManager = $entity_type_manager;
    $this->groupFlexGroupSaver = $group_flex_group_saver;
    $this->groupFeatureHelper = $group_feature_helper;
    $this->postMigrationCordis = $postMigrationCordisProject;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_IMPORT][] = ['onMigratePostImport'];
    $events[MigrateEvents::POST_ROW_SAVE][] = ['onMigratePostRowSave'];
    return $events;
  }

  /**
   * Run tasks on post migration event.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  public function onMigratePostImport(MigrateImportEvent $event) {
    switch ($event->getMigration()->getBaseId()) {
      case 'upgrade_d7_node_complete_group':
        $this->completeRelatedGroups($event);
        $this->completeStoriesRelatedGroups($event);
        break;

      case 'upgrade_d7_node_complete_article':
        $this->completeRelatedStories($event);
        break;
      case 'cordis_xml':
        $this->postMigrationCordis->handlePostMigration();
        break;

    }
  }

  /**
   * Run tasks on row save event.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The post row save event object.
   */
  public function onMigratePostRowSave(MigratePostRowSaveEvent $event) {
    switch ($event->getMigration()->getBaseId()) {
      case 'upgrade_d7_node_complete_group':
      case 'upgrade_d7_node_complete_event_site':
        // Check if we have a group ID.
        if (!$gid = $event->getDestinationIdValues()[0]) {
          return;
        }

        // Load the migrated group.
        if ($group = $this->entityTypeManager->getStorage('group')->load($gid)) {
          // We enable syncing to avoid creating new revisions.
          $group->setSyncing(TRUE);
          $this->setGroupVisibility($group, $event);
          $this->setGroupJoiningMethod($group, $event);
          $this->setGroupFeatures($group, $event);
        }
        break;

      case 'upgrade_d7_node_complete_organisation':
        // Check if we have a group ID.
        if (!$gid = $event->getDestinationIdValues()[0]) {
          return;
        }

        // Load the migrated group.
        if ($group = $this->entityTypeManager->getStorage('group')->load($gid)) {
          // We enable syncing to avoid creating new revisions.
          $group->setSyncing(TRUE);
          $this->setGroupFeatures($group, $event);
        }
        break;

      case 'upgrade_d7_node_complete_article':
      case 'upgrade_d7_node_complete_news':
        // Check if we have a node revision ID.
        if (!$vid = $event->getDestinationIdValues()[1]) {
          return;
        }

        // Load the migrated node.
        if ($node = $this->entityTypeManager->getStorage('node')->loadRevision($vid)) {
          // We enable syncing to avoid creating new revisions.
          $node->setSyncing(TRUE);

          $fragments = $this->entityTypeManager->getStorage('fragment')
            ->loadByProperties([
              'type' => 'disclaimer',
            ]
          );

          if (!empty($fragments)) {
            // We grab and set the disclaimer fragment in the node.
            $fragment = reset($fragments);
            $node->field_disclaimer->target_id = $fragment->id();
          }

          // Make sure we have a value for the 'private' field.
          $node->private->value = 0;

          $node->save();
        }
    }
  }

  /**
   * Sets the group joining methods after row has been saved.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The migrated group entity.
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The post row save event object.
   */
  protected function setGroupJoiningMethod(GroupInterface $group, MigratePostRowSaveEvent $event) {
    /** @var \Drupal\migrate\Row $row */
    $row = $event->getRow();

    // Get the group access for this group.
    if (!isset($row->getSourceProperty('field_membership_open_request')[0]['value'])) {
      $event->logMessage($this->t('No joining method found for group.'), 'warning');
      return;
    }

    $joining_method = $row->getSourceProperty('field_membership_open_request')[0]['value'];

    switch ($joining_method) {
      // Open (auto-join).
      case 1:
        $this->groupFlexGroupSaver->saveGroupJoiningMethods($group, [
          GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_OPEN => GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_OPEN,
        ]);
        break;

      // Moderated (membership request).
      case 0:
        $this->groupFlexGroupSaver->saveGroupJoiningMethods($group, [
          GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_MEMBERSHIP_REQUEST => GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_MEMBERSHIP_REQUEST,
        ]);
        break;
    }
  }

  /**
   * Sets the group visibility after row has been saved.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The migrated group entity.
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The post row save event object.
   */
  protected function setGroupVisibility(GroupInterface $group, MigratePostRowSaveEvent $event) {
    /** @var \Drupal\migrate\Row $row */
    $row = $event->getRow();

    // Get the group access for this group.
    if (!isset($row->getSourceProperty('group_access')[0]['value'])) {
      $event->logMessage($this->t('No group access found for group.'), 'warning');
      return;
    }

    $group_access = $row->getSourceProperty('group_access')[0]['value'];

    switch ($group_access) {
      // Public.
      case 0:
        $this->groupFlexGroupSaver->saveGroupVisibility($group, GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PUBLIC);
        break;

      case 1:
        $access_conf = $this->getPluggableNodeAccessConfiguration($row->getSourceProperty('pluggable_node_access'));
        if ($access_conf) {
          // Custom restricted.
          $options = [];
          foreach ($access_conf as $restriction_type => $values) {
            switch ($restriction_type) {
              case 'email_domain':
                $visibility_option = GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN;
                $email_domains = implode(',', array_values($values));
                $options[$visibility_option] = $this->buildCustomRestrictedVisibilityRecord($visibility_option, $email_domains);
                break;

              case 'organisation':
                $visibility_option = GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATIONS;
                $organisations = [];
                foreach ($this->findOrganisationIds($values) as $org_id) {
                  $organisations[]['target_id'] = $org_id;
                }
                $options[$visibility_option] = $this->buildCustomRestrictedVisibilityRecord($visibility_option, $organisations);
                break;
            }
            $this->groupFlexGroupSaver->saveGroupVisibility($group, GroupVisibilityType::GROUP_VISIBILITY_CUSTOM_RESTRICTED, $options);
          }
        }
        else {
          // Private.
          $this->groupFlexGroupSaver->saveGroupVisibility($group, GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PRIVATE);
        }
        break;
    }

  }

  /**
   * Sets the group features after row has been saved.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The migrated group entity.
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The post row save event object.
   */
  protected function setGroupFeatures(GroupInterface $group, MigratePostRowSaveEvent $event) {
    /** @var \Drupal\migrate\Row $row */
    $row = $event->getRow();

    $group_bundle = $group->bundle();

    $nid = $row->getSourceIdValues()['nid'];

    $enabled_features = [];

    $migrate_connection = Database::getConnection('default', 'migrate');
    // Get the group features.
    $d7_query = $migrate_connection->select('variable_store', 'vs');
    $d7_query->fields('vs', ['value']);
    $d7_query->condition('vs.realm', 'og');
    $d7_query->condition('vs.realm_key', "node_$nid");
    $d7_query->condition('vs.name', 'c4m_og_features_group');

    $results = $d7_query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    if (count($results)) {
      $record = reset($results);
      $features = unserialize($record['value']);

      // Add features that are enabled in the D7.
      foreach ($features as $old_feature_name => $enabled) {
        if ($enabled === $old_feature_name) {
          $enabled_features[] = self::GROUP_FEATURES_MAPPING[$group_bundle][$old_feature_name];
        }
      }
    }
    else {
      // If there is no record this means that all features are enabled by
      // default.
      $enabled_features = array_merge($enabled_features, array_values(self::GROUP_FEATURES_MAPPING[$group_bundle]));
    }

    // Filter out feature that are not available for this group type.
    $group_type_allowed_features = array_keys($this->groupFeatureHelper->getGroupTypeAvailableFeatures(
      $group_bundle)
    );
    foreach ($enabled_features as $index => $enabled_feature) {
      if (!in_array($enabled_feature, $group_type_allowed_features)) {
        unset($enabled_features[$index]);
      }
    }

    // This will be the final features that will be enabled.
    $final_enabled_features = array_merge($enabled_features, self::GROUP_FEATURES_ENABLED_BY_DEFAULT);

    $is_all_features_enabled = TRUE;
    $current_features = $group->get(GroupFeatureHelper::FEATURES_FIELD_NAME)->getValue();
    if (!empty($current_features)) {
      foreach ($current_features as $feature) {
        if (!in_array($feature['value'], $final_enabled_features)) {
          $is_all_features_enabled = FALSE;
          break;
        }
      }
    }

    // If we are updating a group, we need to clear the group features before
    // we save it again. We need this since there is logic to prevent updating
    // group features if the enabled features didn't change.
    if ($is_all_features_enabled) {
      $group->set(
        GroupFeatureHelper::FEATURES_FIELD_NAME,
        []
      );
      $group->save();
    }

    // Updates group features that should be enabled by default.
    $group->set(
      GroupFeatureHelper::FEATURES_FIELD_NAME,
      array_merge($enabled_features, self::GROUP_FEATURES_ENABLED_BY_DEFAULT)
    );
    $group->save();
  }

  /**
   * Returns the configuration of the pluggable_node_access property.
   *
   * @param array $pluggable_node_access
   *   The pluggable_node_access property from the row.
   *
   * @return array
   *   The configuration items or FALSE if not found.
   */
  protected function getPluggableNodeAccessConfiguration(array $pluggable_node_access) {
    $ids = [];
    foreach ($pluggable_node_access as $item) {
      $ids[] = $item['target_id'];
    }

    if (empty($ids)) {
      return FALSE;
    }

    // Get the pluggable node access records.
    $d7_query = $this->connection->select('pluggable_node_access', 'pna');
    $d7_query->fields('pna', ['type', 'data']);
    $d7_query->condition('pna.id', $ids, 'IN');

    $results = [];
    foreach ($d7_query->execute()->fetchAll(\PDO::FETCH_ASSOC) as $result) {
      $results[$result['type']] = unserialize($result['data']);
    }
    return $results ?? FALSE;
  }

  /**
   * Sets the correct entity references for field_related_groups field.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  protected function completeRelatedGroups(MigrateImportEvent $event) {
    $migration = $event->getMigration();

    // Get the original nodes for which we have related groups.
    $d7_query = $this->connection->select('field_data_c4m_related_group', 'rg');
    $d7_query->innerJoin('node', 'n', 'n.nid = rg.c4m_related_group_target_id');
    $d7_query->fields('n', ['nid', 'vid', 'language']);
    $d7_query->fields('rg', ['c4m_related_group_target_id', 'entity_id']);
    $d7_query->addField('rg', 'revision_id', 'parent_vid');
    $d7_query->addField('rg', 'language', 'parent_language');
    $d7_query->condition('rg.entity_type', 'node')
      ->condition('rg.bundle', 'group');

    // Gather all items for each row.
    // Since we are using the node_complete migration, we assume that source ID
    // is always:
    // - destid1: nid.
    // - destid2: vid.
    // - destid3: langcode.
    $node_values = [];
    foreach ($d7_query->execute()->fetchAll(\PDO::FETCH_ASSOC) as $result) {
      $source_ids = [$result['nid'], $result['vid'], $result['language']];
      $parent_source_ids = [
        $result['entity_id'],
        $result['parent_vid'],
        $result['parent_language'],
      ];
      $migrated_row = $migration->getIdMap()->getRowBySource($source_ids);
      $parent_migrated_row = $migration->getIdMap()->getRowBySource($parent_source_ids);
      // Populate the node values.
      $node_values[$parent_migrated_row['destid2']][] = $migrated_row['destid1'];
    }

    // Set the values for each row.
    foreach ($node_values as $parent_id => $items) {
      if ($group = $this->entityTypeManager->getStorage('group')->loadRevision($parent_id)) {
        $this->updateEntityReferenceValue($group, 'field_related_groups', $items, FALSE);
      }
    }
  }

  /**
   * Sets the correct entity references for field_related_stories field.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  protected function completeRelatedStories(MigrateImportEvent $event) {
    $migration = $event->getMigration();

    // Get the original nodes for which we have related stories.
    $d7_query = $this->connection->select('field_data_c4m_related_articles', 'ra');
    $d7_query->innerJoin('node', 'n', 'n.nid = ra.c4m_related_articles_target_id');
    $d7_query->fields('n', ['nid', 'vid', 'language']);
    $d7_query->fields('ra', ['c4m_related_articles_target_id', 'entity_id']);
    $d7_query->addField('ra', 'revision_id', 'parent_vid');
    $d7_query->addField('ra', 'language', 'parent_language');
    $d7_query->condition('ra.entity_type', 'node')
      ->condition('ra.bundle', 'article');

    // Gather all items for each row.
    // Since we are using the node_complete migration, we assume that source ID
    // is always:
    // - destid1: nid.
    // - destid2: vid.
    // - destid3: langcode.
    $node_values = [];
    foreach ($d7_query->execute()->fetchAll(\PDO::FETCH_ASSOC) as $result) {
      $source_ids = [$result['nid'], $result['vid'], $result['language']];
      $parent_source_ids = [
        $result['entity_id'],
        $result['parent_vid'],
        $result['parent_language'],
      ];
      $migrated_row = $migration->getIdMap()->getRowBySource($source_ids);
      $parent_migrated_row = $migration->getIdMap()->getRowBySource($parent_source_ids);
      // Populate the node values.
      $node_values[$parent_migrated_row['destid2']][] = $migrated_row['destid1'];
    }

    // Set the values for each row.
    foreach ($node_values as $parent_nid => $items) {
      if ($node = $this->entityTypeManager->getStorage('node')->loadRevision($parent_nid)) {
        $this->updateEntityReferenceValue($node, 'field_related_stories', $items, FALSE);
      }
    }
  }

  /**
   * Sets the correct entity references for field_related_groups field.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  protected function completeStoriesRelatedGroups(MigrateImportEvent $event) {
    $migration = $event->getMigration();

    // Get the original nodes for which we have related groups.
    $d7_query = $this->connection->select('field_data_c4m_related_group', 'rg');
    $d7_query->innerJoin('node', 'n', 'n.nid = rg.c4m_related_group_target_id');
    $d7_query->fields('n', ['nid', 'vid', 'language']);
    $d7_query->fields('rg', ['c4m_related_group_target_id', 'entity_id']);
    $d7_query->addField('rg', 'revision_id', 'parent_vid');
    $d7_query->addField('rg', 'language', 'parent_language');
    $d7_query->condition('rg.entity_type', 'node')
      ->condition('rg.bundle', 'article');

    // Gather all items for each row.
    // Since we are using the node_complete migration, we assume that source ID
    // is always:
    // - destid1: nid.
    // - destid2: vid.
    // - destid3: langcode.
    $node_values = [];
    foreach ($d7_query->execute()->fetchAll(\PDO::FETCH_ASSOC) as $result) {
      $source_ids = [$result['nid'], $result['vid'], $result['language']];
      $parent_source_ids = [
        $result['entity_id'],
        $result['parent_vid'],
        $result['parent_language'],
      ];
      $migrated_row = $migration->getIdMap()->getRowBySource($source_ids);
      $parent_migrated_row = $this->migrateLookup->lookup(
        [
          'upgrade_d7_node_complete_article',
        ],
        $parent_source_ids
      );

      if (empty($migrated_row) || empty($parent_migrated_row)) {
        continue;
      }
      // Populate the node values.
      $node_values[$parent_migrated_row[0]['vid']][] = $migrated_row['destid1'];
    }

    $related_story_node_ids = [];
    // Set the values for each row.
    foreach ($node_values as $parent_id => $items) {
      if ($node = $this->entityTypeManager->getStorage('node')->loadRevision($parent_id)) {
        $this->updateEntityReferenceValue($node, 'field_related_groups', $items, FALSE);
      }
      $related_story_node_ids[$node->id()] = $node->id();
    }

    // @todo We need to check if it's necessary to migrate the related stories
    // into the group or if we can just keep the related groups in stories.
    // $group_related_stories = [];
    // foreach ($related_story_node_ids as $node_id) {
    //   if ($node = $this->entityTypeManager->getStorage('node')->load($node_id)) {
    //     if ($related_groups = $node->field_related_groups->referencedEntities()) {
    //       foreach ($related_groups as $group) {
    //         $group_related_stories[$group->id()][] = $node_id;
    //       }
    //     }
    //   }
    // }
    // foreach ($group_related_stories as $group_id => $node_ids) {
    //   if ($group = $this->entityTypeManager->getStorage('group')->load($group_id)) {
    //     $this->updateEntityReferenceValue($group, 'field_related_news_stories', $node_ids);
    //   }
    // }
  }

  /**
   * Updates an entity's entity references field with passed values.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The source id values of the migrated item.
   * @param string $field_name
   *   The field name to update.
   * @param array $values
   *   The values to be set.
   * @param bool $preserve_existing_values
   *   If FALSE, all pre-existing values will be removed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateEntityReferenceValue(ContentEntityInterface $entity, string $field_name, array $values, bool $preserve_existing_values = TRUE) {
    if (!$preserve_existing_values) {
      $entity->set($field_name, []);
    }

    $changed = $entity->getChangedTime();
    $entity->set($field_name, array_unique(array_merge($entity->get($field_name)->getValue(), $values)));
    $entity->setNewRevision(FALSE);
    // Preserve the changed timestamp.
    $entity->setChangedTime($changed);
    // We enable syncing to avoid creating new revisions.
    $entity->setSyncing(TRUE);
    $entity->save();
  }

  /**
   * Builds the expected array for CustomRestrictedVisibility plugin.
   *
   * There is no available function to reproduce this through the oec_group_flex
   * module, so we mimic this here.
   * It can be used to set the visibility record.
   *
   * @param string $plugin_id
   *   The CustomRestrictedVisibility plugin ID.
   * @param mixed $value
   *   The value to assign.
   *
   * @return array
   *   The configuration array.
   */
  protected function buildCustomRestrictedVisibilityRecord(string $plugin_id, $value) {
    return [
      "{$plugin_id}_status" => 1,
      "{$plugin_id}_conf" => $value,
    ];
  }

  /**
   * Returns the organisation destination IDs.
   *
   * @param string[] $names
   *   The organisation names.
   *
   * @return int[]
   *   Array of organisation IDs.
   */
  protected function findOrganisationIds(array $names = []) {
    $results = [];
    foreach ($names as $name) {
      foreach ($this->entityTypeManager->getStorage('group')->loadByProperties([
        'label' => $name,
        'type' => Organisations::GROUP_ORGANISATION_BUNDLE,
      ]) as $group) {
        $results[] = $group->id();
      }
    }
    return $results;
  }

}
