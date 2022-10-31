<?php

namespace Drupal\eic_content;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * EICContentHelper service that provides helper functions for content.
 */
class EICContentHelper implements EICContentHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new EICContentHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritDoc}
   */
  public function getGroupContentByEntity(
    ContentEntityInterface $entity,
    array $filter_group_types = [],
    array $filter_group_content_types = []
  ) {
    if (!$this->moduleHandler->moduleExists('group')) {
      return FALSE;
    }

    try {
      if (!$filter_group_types && !$filter_group_content_types) {
        return $this->entityTypeManager->getStorage('group_content')->loadByEntity($entity);
      }

      /** @var \Drupal\group\Entity\GroupContentTypeInterface[] $group_content_types */
      $group_content_types = $this->entityTypeManager->getStorage('group_content_type')
        ->loadMultiple();
      if (!$group_content_types) {
        return FALSE;
      }

      $load_group_content_types = [];
      foreach ($group_content_types as $group_content_type) {
        // Gets group content type plugin ids for specific group types.
        if ($filter_group_types) {
          foreach ($filter_group_types as $group_type_id) {
            if (
              $group_content_type->getGroupTypeId() === $group_type_id &&
              (
                !$filter_group_content_types ||
                in_array($group_content_type->getContentPluginId(), $filter_group_content_types)
              )
            ) {
              $load_group_content_types[] = $group_content_type->id();
            }
          }
          continue;
        }

        // Gets group content type plugin ids for all group types.
        if (
          !$filter_group_content_types ||
          in_array($group_content_type->getContentPluginId(), $filter_group_content_types)
        ) {
          $load_group_content_types[] = $group_content_type->id();
        }
      }

      if (!$load_group_content_types) {
        return FALSE;
      }

      return $this->entityTypeManager->getStorage('group_content')
        ->loadByProperties([
          'type' => $load_group_content_types,
          'entity_id' => $entity->id()
        ]);
    }
    catch (PluginException $e) {
      return FALSE;
    }
  }

}
