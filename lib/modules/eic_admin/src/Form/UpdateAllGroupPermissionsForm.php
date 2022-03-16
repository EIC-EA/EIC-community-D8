<?php

namespace Drupal\eic_admin\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
    $group_types_update = [
      'event',
      'group',
      'organisation',
    ];

    foreach ($group_types_update as $group_type) {
      $this->updatePermissionGroupType($group_type);
    }
  }

  /**
   * By given group type, update all groups depending permissions from group type.
   *
   * @param string $group_type
   *   The group type id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function updatePermissionGroupType(string $group_type) {
    $groups = $this->em->getStorage('group')
      ->loadByProperties([
        'type' => $group_type,
      ]);
    /** @var GroupType $group_type */
    $group_type = GroupType::load($group_type);
    $type_roles = $group_type->getRoles();

    foreach ($groups as $group) {
      $groupPermissions = $this->permissionsManager->loadByGroup($group);

      if (!$groupPermissions)
        continue;

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
