<?php

namespace Drupal\eic_group_membership\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for EIC Group membership routes.
 */
class GroupMembershipController extends ControllerBase {

  /**
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new GroupMembershipController object.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination helper.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    RedirectDestinationInterface $redirect_destination,
    RequestStack $request_stack
  ) {
    $this->redirectDestination = $redirect_destination;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('redirect.destination'),
      $container->get('request_stack')
    );
  }

  /**
   * Builds the response.
   */
  public function transferGroupOwnership(GroupInterface $group, GroupContentInterface $group_content) {
    /** @var \Drupal\user\UserInterface $new_owner */
    $new_owner = $group_content->getEntity();
    $new_owner_membership = $group->getMember($new_owner);

    /** @var \Drupal\group\GroupMembership $old_owner_membership */
    $old_owner_membership = EICGroupsHelper::getGroupOwner($group, TRUE);

    // Removes group owner role from the old owner and add group admin role.
    $this->updateOldOwnerRoles($old_owner_membership);

    $group_owner_role = $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_OWNER_ROLE;
    // Transfer old group owner role to the new owner.
    $new_owner_membership->addRole($group_owner_role);

    // Default response when destination is not in the URL.
    $response = new RedirectResponse($group->toUrl()->toString());

    // Check if destination is in the URL query and if so, we create new
    // redirect response to the destination URL.
    if ($this->requestStack->getCurrentRequest()->query->has('destination')) {
      $response = new RedirectResponse($this->redirectDestination->get());
    }

    $this->messenger()->addStatus(
      $this->t(
        'The @group-type ownership has been successfully transfered!',
        [
          '@group-type', $group->bundle(),
        ]
      )
    );
    return $response->send();
  }

  /**
   * Builds the transfer group ownership page title.
   */
  public function transferGroupOwnershipTitle(GroupInterface $group, GroupContentInterface $group_content) {
    return $this->t('Transfer @group-type ownership.',
      [
        '@group-type' => $group->bundle(),
      ]
    );
  }

  /**
   * Adds/removes roles from the old owner when transfering group ownership.
   *
   * @param \Drupal\group\GroupMembership $old_owner_membership
   *   The old group owner membership.
   */
  private function updateOldOwnerRoles(GroupMembership $old_owner_membership) {
    $group = $old_owner_membership->getGroup();

    $add_roles = [
      $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_ADMINISTRATOR_ROLE,
    ];

    $group_content = $old_owner_membership->getGroupContent();

    // Add roles.
    foreach ($add_roles as $new_role) {
      $has_role = FALSE;
      // Check if a member already has the role.
      foreach ($group_content->group_roles as $key => $role_item) {
        if ($role_item->target_id === $new_role) {
          $has_role = TRUE;
          break;
        }
      }

      if ($has_role) {
        continue;
      }

      $group_content->group_roles[] = $new_role;
    }

    $remove_roles = [
      $group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_OWNER_ROLE,
    ];

    // Remove roles.
    foreach ($remove_roles as $old_role) {
      foreach ($group_content->group_roles as $key => $role_item) {
        if ($role_item->target_id == $old_role) {
          $group_content->group_roles->removeItem($key);
        }
      }
    }

    $group_content->save();
  }

}
