<?php

namespace Drupal\oec_group_flex\Plugin\views\field;

use Drupal\group\Entity\GroupInterface;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field for custom restricted email domains.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_visibility_custom_restricted_email_domains")
 */
class RestrictedEmailDomains extends GroupFlexFieldPluginBase {

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
    if (empty($visibility_record_settings['restricted_email_domains'])) {
      return '';
    }

    if (empty($visibility_record_settings['restricted_email_domains']['options'])) {
      return '';
    }

    return $visibility_record_settings['restricted_email_domains']['options'];
  }

}
