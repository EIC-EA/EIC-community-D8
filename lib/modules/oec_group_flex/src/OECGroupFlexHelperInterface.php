<?php

namespace Drupal\oec_group_flex;

use Drupal\group\Entity\GroupInterface;

/**
 * Interface for GroupVisibilityRecord objects.
 */
interface OECGroupFlexHelperInterface {

  /**
   * Returns an array containing the visibility settings for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity for which we return visibility settings.
   *
   * @return array
   *   An array containing:
   *   - plugin_id: the plugin ID of the selected visibility.
   *   - label: the plugin label.
   *   - settings (optional): object of type
   *     Drupal\oec_group_flex\GroupVisibilityRecord (currently only for
   *     CustomRestrictedVisibility).
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getGroupVisibilitySettings(GroupInterface $group);

  /**
   * Returns a human-readable array for the given group visibility record.
   *
   * @param \Drupal\oec_group_flex\GroupVisibilityRecord $visibility_record
   *   The Group visibility record.
   *
   * @return array
   *   An array containing:
   *   - plugin_id: the plugin ID as key.
   *     - label: Label of the plugin ID.
   *     - options: the options of the plugin. Currently can be any type of
   *       data.
   */
  public function getGroupVisibilityRecordSettings(GroupVisibilityRecord $visibility_record);

  /**
   * Returns an array containing the joining method for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity for which we return joining method.
   *
   * @return array
   *   An array containing the labels of the enabled joining methods.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getGroupJoiningMethod(GroupInterface $group);

}
