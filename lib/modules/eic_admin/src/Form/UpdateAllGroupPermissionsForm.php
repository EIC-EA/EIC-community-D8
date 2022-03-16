<?php

namespace Drupal\eic_admin\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupType;
use Drupal\group_permissions\GroupPermissionsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateAllGroupPermissionsForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface $em
   */
  private EntityTypeManagerInterface $em;

  /**
   * The group permissions manager.
   *
   * @var GroupPermissionsManagerInterface $permissionsManager
   */
  private GroupPermissionsManagerInterface $permissionsManager;

  /**
   * The datetime time service.
   *
   * @var TimeInterface $datetime
   */
  private TimeInterface $datetime;

  /**
   * UpdateAllGroupPermissionsForm constructor.
   *
   * @param EntityTypeManagerInterface $em
   *   The entity type manager.
   * @param GroupPermissionsManagerInterface $permissions_manager
   *   The group permission manager.
   * @param TimeInterface $datetime
   *   The datetime time service.
   */
  public function __construct(
    EntityTypeManagerInterface $em,
    GroupPermissionsManagerInterface $permissions_manager,
    TimeInterface $datetime
  ) {
    $this->em = $em;
    $this->permissionsManager = $permissions_manager;
    $this->datetime = $datetime;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('group_permission.group_permissions_manager'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eic_admin_update_all_group_perissions_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['groups'] = [
      '#title' => $this->t('Select multiple groups', [], ['context' => 'eic_admin']),
      '#description' => $this->t(
        'Add multiple groups <b>separating them with a comma</b>.
      If empty, it will select all.'
      ),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'group',
      '#tags' => TRUE,
      '#selection_settings' => [
        'sort' => [
          'field' => 'label',
          'direction' => 'ASC',
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rebuild permissions', [], ['context' => 'eic_admin']),
      '#attributes' => [
        'class' => [
          'ecl-button',
          'ecl-button--search',
          'ecl-search-form__button',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $groups = $form_state->getValue('groups');
    $this->updatePermissionGroupType($groups);
  }

  /**
   * Update all groups depending permissions from their group type.
   *
   * @param \Drupal\group\Entity\GroupInterface[] $groups
   *   The group type id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function updatePermissionGroupType(?array $groups_id) {
    // If empty groups field, update all groups.
    if (empty($groups_id)) {
      $groups = $this->em->getStorage('group')->loadMultiple();
    }
    else {
      $groups = Group::loadMultiple(
        array_map(function ($group_id) {
          return $group_id['target_id'];
        }, $groups_id)
      );
    }

    foreach ($groups as $group) {
      if (!$group instanceof GroupInterface) {
        continue;
      }

      /** @var GroupType $group_type */
      $group_type = $group->getGroupType();
      $type_roles = $group_type->getRoles();
      $groupPermissions = $this->permissionsManager->loadByGroup($group);

      if (!$groupPermissions) {
        continue;
      }

      $permissions = $groupPermissions->getPermissions();
      foreach ($type_roles as $type_role) {
        $permissions[$type_role->id()] = $type_role->getPermissions();
      }

      $groupPermissions->setPermissions($permissions);

      $violations = $groupPermissions->validate();

      if (count($violations) > 0) {
        $message = '';
        foreach ($violations as $violation) {
          $message .= "\n" . $violation->getMessage();
        }
        $this->messenger()->addMessage('Group permissions are not saved correctly, because:' . $message);
        continue;
      }

      // Saves the GroupPermission object with a new revision.
      $groupPermissions->setNewRevision();
      $groupPermissions->setRevisionUserId(1);
      $groupPermissions->setRevisionCreationTime($this->datetime->getRequestTime());
      $groupPermissions->setRevisionLogMessage('Group features enabled/disabled.');
      $groupPermissions->save();

      $this->messenger()->addMessage(
        $this->t(
          'Group @name permissions has been updated.',
          ['@name' => $group->label()],
          ['context' => 'eic_admin']
        )
      );
    }
  }

}
