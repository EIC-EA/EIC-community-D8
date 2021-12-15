<?php

namespace Drupal\eic_groups\Plugin\views\field;

use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\Plugin\views\field\GroupFlexFieldPluginBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field for custom restricted organisation types.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_visibility_custom_restricted_organisation_types")
 */
class RestrictedOrganisationTypes extends GroupFlexFieldPluginBase {

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
    if (empty($visibility_record_settings['restricted_organisation_types'])) {
      return '';
    }

    if (empty($visibility_record_settings['restricted_organisation_types']['options'])) {
      return '';
    }

    // @todo Create formatting options for the field.
    $terms = [];
    foreach ($visibility_record_settings['restricted_organisation_types']['options'] as $term_id => $status) {
      if ($status) {
        $terms[] = Term::load($term_id)->label();
      }
    }
    return implode(', ', $terms);
  }

}
