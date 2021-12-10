<?php

namespace Drupal\eic_groups\Plugin\views\field;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Group;
use Drupal\oec_group_flex\Plugin\views\field\GroupFlexFieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field for custom restricted organisations.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_visibility_custom_restricted_organisations")
 */
class RestrictedOrganisations extends GroupFlexFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $group = $values->_entity;
    if (!$group instanceof GroupInterface) {
      return '';
    }

    $visibility_settings = $this->oecGroupFlexHelper->getGroupVisibilitySettings($group);
    if ($visibility_settings['plugin_id'] != 'custom_restricted') {
      return '';
    }

    $visibility_record_settings = $this->oecGroupFlexHelper->getGroupVisibilityRecordSettings($visibility_settings['settings']);
    if (empty($visibility_record_settings['restricted_organisations'])) {
      return '';
    }

    if (empty($visibility_record_settings['restricted_organisations']['options'])) {
      return '';
    }

    // @todo Create formatting options for the field.
    $groups = [];
    foreach (array_column($visibility_record_settings['restricted_organisations']['options'], 'target_id') as $group_id) {
      $groups[] = Group::load($group_id)->label();
    }
    return implode(', ', $groups);

  }

}
