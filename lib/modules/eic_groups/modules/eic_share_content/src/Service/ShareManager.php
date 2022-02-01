<?php

namespace Drupal\eic_share_content\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_content\EICContentHelperInterface;
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
   * @var \Drupal\eic_content\EICContentHelperInterface
   */
  private $contentHelper;

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
   * @param \Drupal\eic_content\EICContentHelperInterface $content_helper
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   */
  public function __construct(
    EICContentHelperInterface $content_helper,
    EntityTypeManagerInterface $entity_type_manager,
    GroupContentEnablerManagerInterface $plugin_manager,
    MessageBusInterface $message_bus
  ) {
    $this->contentHelper = $content_helper;
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
    $group_content = $this->contentHelper->getGroupContent($source_group, $node);
    if (empty($group_content)) {
      throw new \InvalidArgumentException($this->t('This content can\'t be shared'));
    }

    if (
      $target_group->id() === $source_group->id() ||
      $group_content->getGroup()->id() !== $source_group->id()
    ) {
      throw new \InvalidArgumentException($this->t('This content can\'t be shared'));
    }

    if ($this->isShared($node, $target_group)) {
      throw new \InvalidArgumentException($this->t('This content is already shared with this group'));
    }

    $plugin_id = $this->getGroupContentId($target_group, $node);
    if (!$plugin_id) {
      throw new \InvalidArgumentException($this->t('This content can\'t be shared'));
    }

    $shared_group_content = GroupContent::create([
      'type' => $plugin_id,
      'gid' => $target_group->id(),
      'entity_id' => $node->id(),
    ]);
    $shared_group_content->save();

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
   * @param \Drupal\node\NodeInterface $node
   * @param \Drupal\group\Entity\GroupInterface $group
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isShared(NodeInterface $node, GroupInterface $group): bool {
    return !empty($this->getShareEntity($node, $group));
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   * @param \Drupal\group\Entity\GroupInterface $group
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getShareEntity(
    NodeInterface $node,
    GroupInterface $group
  ): array {
    return $this->entityTypeManager->getStorage('message')
      ->loadByProperties([
        'template' => ActivityStreamMessageTemplates::SHARE_CONTENT,
        'field_group_ref' => $group->id(),
        'field_referenced_node' => $node->id(),
      ]);
  }

  /**
   * Return the group content plugin id for the given node.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   * @param \Drupal\node\NodeInterface $node
   *
   * @return string|null
   */
  public function getGroupContentId(
    GroupInterface $group,
    NodeInterface $node
  ): ?string {
    static $enabled_ids;
    if (empty($enabled_ids)) {
      $plugin_ids = $this->groupContentPluginManager
        ->getInstalledIds($group->getGroupType());

      foreach ($plugin_ids as $plugin_id) {
        if (strpos($plugin_id, 'group_node:') !== 0) {
          continue;
        }

        [, $content_type] = explode(':', $plugin_id);
        $enabled_ids[$content_type] = $group->getGroupType()
          ->getContentPlugin("group_node:$content_type")
          ->getContentTypeConfigId();;
      }
    }

    return $enabled_ids[$node->bundle()] ?? NULL;
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getShares(NodeInterface $node): array {
    return $this->entityTypeManager->getStorage('message')
      ->loadByProperties([
        'template' => ActivityStreamMessageTemplates::SHARE_CONTENT,
        'field_referenced_node' => $node->id(),
      ]);
  }

}
