<?php

namespace Drupal\eic_groups\MediaLibrary;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\media_library\MediaLibraryFieldWidgetOpener as MediaLibraryFieldWidgetOpenerBase;
use Drupal\media_library\MediaLibraryState;

/**
 * Decorates the media library opener service for field widgets.
 *
 * @internal
 *   This service is an internal part of Media Library's field widget.
 */
class MediaLibraryFieldWidgetOpener extends MediaLibraryFieldWidgetOpenerBase {

  /**
   * The MediaLibraryFieldWidgetOpener inner service.
   *
   * @var \Drupal\media_library\MediaLibraryFieldWidgetOpener
   */
  protected $mediaLibraryFieldWidgetOpener;

  /**
   * MediaLibraryFieldWidgetOpener constructor.
   *
   * @param \Drupal\media_library\MediaLibraryFieldWidgetOpener $media_library_field_widget_opener_inner_service
   *   The MediaLibraryFieldWidgetOpener inner service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    MediaLibraryFieldWidgetOpenerBase $media_library_field_widget_opener_inner_service,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($entity_type_manager);
    $this->mediaLibraryFieldWidgetOpener = $media_library_field_widget_opener_inner_service;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(MediaLibraryState $state, AccountInterface $account) {
    $access = $this->mediaLibraryFieldWidgetOpener->checkAccess($state, $account);

    if (!$access->isAllowed()) {

      // If not in group context we return current access.
      if (!$state->get('in_group_context') || !$state->get('group_id')) {
        return $access;
      }

      $group = $this->entityTypeManager->getStorage('group')->load($state->get('group_id'));

      if (!$group) {
        return $access;
      }

      $parameters = $state->getOpenerParameters() + ['entity_id' => NULL];

      // Forbid access if any of the required parameters are missing.
      foreach (['entity_type_id', 'bundle'] as $key) {
        if (empty($parameters[$key])) {
          return $access;
        }
      }

      $entity_type_id = $parameters['entity_type_id'];

      // If this is not attached to a node we return current access.
      if ($entity_type_id !== 'node') {
        return $access;
      }

      $entity_bundle = $parameters['bundle'];

      /** @var \Drupal\group\Entity\GroupInterface $group */

      $group_type = $group->getGroupType();

      $group_content_plugins = $group_type->getInstalledContentPlugins()->getInstanceIds();

      if (!in_array("group_node:$entity_bundle", $group_content_plugins)) {
        return $access;
      }

      if ($group->hasPermission("create group_node:$entity_bundle entity", $account)) {
        return AccessResult::allowed()->addCacheableDependency($state);
      }
    }

    return $access;
  }

  /**
   * Magic method to return any method call inside the inner service.
   */
  public function __call($method, $args) {
    return call_user_func_array([$this->mediaLibraryFieldWidgetOpener, $method], $args);
  }

}
