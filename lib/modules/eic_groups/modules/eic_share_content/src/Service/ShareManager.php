<?php

namespace Drupal\eic_share_content\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\eic_messages\ActivityStreamOperationTypes;
use Drupal\eic_messages\Service\MessageBusInterface;
use Drupal\eic_messages\Util\ActivityStreamMessageTemplates;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;

/**
 * Class ShareManager
 *
 * @package Drupal\eic_share_content\Service
 */
class ShareManager {

  use StringTranslationTrait;

  /**
   * The Group Content plugin ID for shared content.
   *
   * @var string
   */
  const GROUP_CONTENT_SHARED_PLUGIN_ID = 'group_shared_content';

  /**
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  private $groupsHelper;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  private $groupContentPluginManager;

  /**
   * @var \Drupal\eic_messages\Service\MessageBusInterface
   */
  private $messageBus;

  /**
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $groups_helper
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   */
  public function __construct(
    EICGroupsHelperInterface $groups_helper,
    EntityTypeManagerInterface $entity_type_manager,
    GroupContentEnablerManagerInterface $plugin_manager,
    MessageBusInterface $message_bus
  ) {
    $this->groupsHelper = $groups_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->groupContentPluginManager = $plugin_manager;
    $this->messageBus = $message_bus;
  }

  /**
   * @param string $node_bundle
   *
   * @return bool
   */
  public function isSupported(string $node_bundle): bool {
    static $shareableNodeBundles = [
      'video',
      'wiki_page',
      'document',
      'discussion',
      'gallery',
      'event',
      'news',
    ];

    return in_array($node_bundle, $shareableNodeBundles) ?? FALSE;
  }

  /**
   * @param \Drupal\group\Entity\GroupInterface $source_group
   * @param \Drupal\group\Entity\GroupInterface $target_group
   * @param \Drupal\node\NodeInterface $node
   * @param string|null $message
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function share(
    GroupInterface $source_group,
    GroupInterface $target_group,
    NodeInterface $node,
    ?string $message
  ) {
    // First we check if this node belongs to a group.
    if (!$this->groupsHelper->getGroupByEntity($node)) {
      throw new \InvalidArgumentException($this->t("This content can't be shared"));
    }

    // If source and target groups are the same, we can't share.
    if ($target_group->id() === $source_group->id()) {
      throw new \InvalidArgumentException($this->t("This content can't be shared"));
    }

    // If the content is already shared to the target group, we can't share.
    if ($this->isShared($node, $target_group)) {
      throw new \InvalidArgumentException($this->t('This content is already shared with this group'));
    }

    // Create the group content entity.
    $shared_group_content = GroupContent::create([
      'type' => $target_group->getGroupType()->id() . '-' . self::GROUP_CONTENT_SHARED_PLUGIN_ID,
      'gid' => $target_group->id(),
      'entity_id' => $node->id(),
    ]);
    $shared_group_content->save();

    // Dispatch the message.
    $this->messageBus->dispatch([
      'template' => ActivityStreamMessageTemplates::SHARE_CONTENT,
      'uid' => $node->getOwnerId(),
      'field_operation_type' => ActivityStreamOperationTypes::SHARED_ENTITY,
      'field_referenced_node' => $node->id(),
      'field_entity_type' => $node->bundle(),
      'field_source_group' => $source_group,
      'field_group_ref' => $target_group,
      'field_share_message' => $message,
    ]);

    // Reindex the node immediately.
    // There seem to be multiple implementation of reindexing logics.
    // @TODO Write a single service for this.
    $indexes = ContentEntity::getIndexesForEntity($node);
    foreach ($indexes as $index) {
      $index->trackItemsUpdated(
        'entity:node',
        [$node->id() . ':' . $node->language()->getId()]
      );
    }
  }

  /**
   * Determines if a node has been shared.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity for which we're looking shares.
   * @param \Drupal\group\Entity\GroupInterface|null $target_group
   *   The group to filter on. If null, will check for all groups.
   *
   * @return bool
   *   TRUE if node has been shared.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isShared(NodeInterface $node, GroupInterface $target_group = NULL): bool {
    return !empty($this->getSharedEntities($node, $target_group));
  }

  /**
   * Returns the list of shared group_content entities.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity for which we're looking shares.
   * @param \Drupal\group\Entity\GroupInterface|null $target_group
   *   The group to filter on. If null, shared entities will be returned for all
   *   groups.
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   *   The list of found group_content entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSharedEntities(NodeInterface $node, GroupInterface $target_group = NULL): array {
    $query = $this->entityTypeManager->getStorage('group_content')->getQuery();
    $query->condition('type', '%-' . self::GROUP_CONTENT_SHARED_PLUGIN_ID, 'LIKE');
    $query->condition('entity_id', $node->id());
    if ($target_group) {
      $query->condition('gid', $target_group->id());
    }
    return $this->entityTypeManager->getStorage('group_content')->loadMultiple($query->execute());
  }

}
