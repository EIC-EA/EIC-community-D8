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
 * Class that manages functions for the sharing feature.
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
   * The EIC Groups helper.
   *
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  private $groupsHelper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The group content enabler manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  private $groupContentPluginManager;

  /**
   * The EIC message bus.
   *
   * @var \Drupal\eic_messages\Service\MessageBusInterface
   */
  private $messageBus;

  /**
   * Constructs a new ShareManager object.
   *
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $groups_helper
   *   The EIC Groups helper.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler manager.
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   *   The EIC message bus.
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
   * Checks if a node bundle is eligible for group sharing.
   *
   * @param string $node_bundle
   *   The node bundle machine name.
   *
   * @return bool
   *   TRUE if node bundle is supported.
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
   * Shares a content from one group to another.
   *
   * @param \Drupal\group\Entity\GroupInterface $source_group
   *   The group entity from which we want to share.
   * @param \Drupal\group\Entity\GroupInterface $target_group
   *   The group entity to which we want to share.
   * @param \Drupal\node\NodeInterface $node
   *   The node entity we want to share.
   * @param string|null $message
   *   The message that accompanies the share action.
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
      throw new \InvalidArgumentException("This content can't be shared");
    }

    // If source and target groups are the same, we can't share.
    if ($target_group->id() === $source_group->id()) {
      throw new \InvalidArgumentException("This content can't be shared");
    }

    // If the content is already shared to the target group, we can't share.
    if ($this->isShared($node, $target_group)) {
      throw new \InvalidArgumentException('This content is already shared with this group');
    }

    // Create the group content entity.
    $shared_group_content = GroupContent::create([
      'type' => $this->defineGroupContentType($target_group),
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
    // @todo Write a single service for this.
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

  /**
   * Returns the type for a shared content and given group type.
   *
   * @param \Drupal\group\Entity\GroupInterface $target_group
   *   The target group.
   *
   * @return string
   *   The type to be used for the group_content entity.
   */
  public function defineGroupContentType(GroupInterface $target_group) {
    return $target_group->getGroupType()->id() . '-' . self::GROUP_CONTENT_SHARED_PLUGIN_ID;
  }

}
