<?php

namespace Drupal\eic_stakeholder\Plugin\GroupContentEnabler;

use Drupal\eic_stakeholder\Entity\StakeholderType;
use Drupal\Component\Plugin\Derivative\DeriverBase;

class GroupStakeholderDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach (StakeholderType::loadMultiple() as $name => $stakeholder_type) {
      $label = $stakeholder_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group stakeholder (@type)', ['@type' => $label]),
        'description' => t('Adds %type content to groups both publicly and privately.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
