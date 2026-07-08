<?php

namespace Drupal\eic_groups\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class UpdatePermissionGroupsCommand extends DrushCommands {

  /**
   * Constructs an UpdatePermissionGroupsCommand object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EICGroupsHelperInterface $groupsHelper,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_groups.helper'),
    );
  }

  /**
   * Command to manually add or remove permissions on existing group entities.
   */
  #[CLI\Command(name: 'eic_groups:update-permissions', aliases: ['gupdp'])]
  #[CLI\Argument(name: 'group_type', description: 'Group type to run on.')]
  #[CLI\Argument(name: 'role', description: 'Role to alter the permissions of.')]
  #[CLI\Option(name: 'add', description: 'List of permissions to add to all groups of given group type.')]
  #[CLI\Option(name: 'remove', description: 'List of permissions to remove from all groups of given group type')]
  #[CLI\Usage(name: 'eic_groups:update-permissions --group-type=organisation --add="edit organisation description","post comments"', description: 'Add the given permissions to organisation-member role for all group entities.')]
  public function updatePermissions($group_type, $role, $options = ['add' => [], 'remove' => []]) {
    $add = $options['add'];
    $remove = $options['remove'];

    if (empty($add) && empty($remove)) {
      $this->logger()->error(dt('You have to either add a permission or remove it!'));
      return self::EXIT_FAILURE_WITH_CLARITY;
    }
    if (!empty($add)) {
      $add = explode(',', $add[0]);
    }
    if (!empty($remove)) {
      $remove = explode(',', $remove[0]);
    }

    $group_permissions = $this->entityTypeManager
      ->getStorage('group_permission')->getQuery()
      ->condition('gid.entity:group.type', $group_type)
      ->accessCheck(FALSE)
      ->execute();

    $max_count = count($group_permissions);
    $this->io()->progressStart($max_count);
    $role = "$group_type-$role";
    foreach ($group_permissions as $group_permission_id) {
      $group_permission = $this->entityTypeManager->getStorage('group_permission')->load($group_permission_id);
      if (!empty($add)) {
        $group_permission = $this->groupsHelper->addRolePermissionsToGroup($group_permission, $role, $add);
      }
      if (!empty($remove)) {
        $group_permission = $this->groupsHelper->removeRolePermissionsFromGroup($group_permission, $role, $remove);
      }
      $this->groupsHelper->saveGroupPermissions($group_permission);
      $this->io()->progressAdvance();
    }

    $this->io()->progressFinish();
    $this->logger()->success(dt('Successfully updated permissions'));
    return self::EXIT_SUCCESS;
  }

}
