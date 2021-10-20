<?php

namespace Drupal\eic_groups\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Url;
use Drupal\gnode\Plugin\GroupContentEnabler\GroupNode as GroupNodeBase;

/**
 * Extends content enabler class for group content nodes.
 */
class GroupNode extends GroupNodeBase {

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $plugin_id = $this->getPluginId();
    $type = $this->getEntityBundle();
    $operations = [];

    $route_params = ['group' => $group->id(), 'plugin_id' => $plugin_id];
    $create_url = Url::fromRoute('entity.group_content.create_form', $route_params);

    // If the user has access to the route, we add the operation.
    if ($create_url->access($account)) {
      $operations["gnode-create-$type"] = [
        'title' => $this->t('Add @type', ['@type' => $this->getNodeType()->label()]),
        'url' => new Url('entity.group_content.create_form', $route_params),
        'weight' => 30,
      ];
    }

    return $operations;
  }

}
