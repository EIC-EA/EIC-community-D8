<?php

namespace Drupal\eic_groups\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a field to display group members by role.
 *
 * This plugin may not be suitable with large data if you need performance.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_members_by_role")
 */
class GroupMembersByRole extends FieldPluginBase {

  /**
   * The EIC groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $groupsHelper;

  /**
   * Constructs a GroupMembersByRole object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EICGroupsHelper $eic_groups_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupsHelper = $eic_groups_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_groups.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['roles'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = $this->getGroupRoleOptions();
    $form['roles'] = [
      '#title' => $this->t('Select the role(s) to filter on'),
      '#description' => $this->t('If none selected, all roles will be returned.'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->options['roles'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

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
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $values->_entity;
    if (!$group instanceof GroupInterface) {
      return '';
    }

    $usernames = [];
    $memberships = $group->getMembers($this->getSelectedRoles());
    foreach ($memberships as $membership) {
      $usernames[] = $membership->getUser()->getDisplayName();
    }

    // @todo Create formatting options for the field.
    return implode(', ', $usernames);
  }

  /**
   * Returns the list of user defined roles for all group types.
   *
   * @return array
   *   An array of role_id => label.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getGroupRoleOptions() {
    // @todo Cache the results to avoid running this code on each row.
    $roles = [];

    foreach ($this->groupsHelper->getGroupRoles() as $info) {
      foreach ($info['roles'] as $role_id => $role_label) {
        $roles[$role_id] = $info['label'] . ' - ' . $role_label;
      }
    }

    return $roles;
  }

  /**
   * Returns the selected roles for this instance.
   *
   * @return array
   *   An array of role machine names.
   */
  protected function getSelectedRoles() {
    $selected_roles = [];
    foreach ($this->options['roles'] as $role_id => $value) {
      if ($role_id === $value) {
        $selected_roles[] = $role_id;
      }
    }
    return $selected_roles;
  }

}
