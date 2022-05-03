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
  public function getGroupContentByEntity(ContentEntityInterface $entity) {
    if (!$this->moduleHandler->moduleExists('group')) {
      return FALSE;
    }

    try {
      return $this->entityTypeManager->getStorage('group_content')->loadByEntity($entity);
    }
    catch (PluginException $e) {
      return FALSE;
    }
  }

}
