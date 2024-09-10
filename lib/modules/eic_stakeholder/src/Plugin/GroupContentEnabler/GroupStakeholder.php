<?php

namespace Drupal\eic_stakeholder\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\eic_stakeholder\Entity\StakeholderType;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a content enabler for nodes.
 *
 * @GroupContentEnabler(
 *   id = "group_stakeholder",
 *   label = @Translation("Group stakeholder"),
 *   description = @Translation("Adds stakeholders to groups both publicly and privately."),
 *   entity_type_id = "stakeholder",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Organisation name"),
 *   reference_description = @Translation("The organisation name of the stakeholder to add to the group"),
 *   deriver = "Drupal\eic_stakeholder\Plugin\GroupContentEnabler\GroupStakeholderDeriver",
 *   handlers = {
 *     "access" = "Drupal\group\Plugin\GroupContentAccessControlHandler",
 *     "permission_provider" = "Drupal\eic_stakeholder\Plugin\GroupStakeholderPermissionProvider",
 *   }
 * )
 */
class GroupStakeholder extends GroupContentEnablerBase {

  /**
   * Retrieves the stakeholder type this plugin supports.
   *
   * @return \Drupal\eic_stakeholder\StakeholderType
   *   The stakeholder type this plugin supports.
   */
  protected function getStakeholderType() {
    return StakeholderType::load($this->getEntityBundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $plugin_id = $this->getPluginId();
    $type = $this->getEntityBundle();
    $operations = [];

    if ($group->hasPermission("create $plugin_id entity", $account)) {
      $route_params = ['group' => $group->id(), 'plugin_id' => $plugin_id];
      $operations["eic_stakeholder-create-$type"] = [
        'title' => $this->t('Add @type', ['@type' => $this->getStakeholderType()->label()]),
        'url' => new Url('entity.group_content.create_form', $route_params),
        'weight' => 30,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies['config'][] = 'eic_stakeholder.stakeholder_type.' . $this->getEntityBundle();
    return $dependencies;
  }

}
