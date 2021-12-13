<?php

namespace Drupal\eic_groups\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
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
    $roles = [];
    $group_types = $this->entityTypeManager->getStorage('group_type')->loadMultiple();
    foreach ($group_types as $group_type) {
      $group_roles = $group_type->getRoles(FALSE);
      foreach ($group_roles as $group_role) {
        $roles[$group_role->id()] = $group_type->label() . ' - ' . $group_role->label();
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
