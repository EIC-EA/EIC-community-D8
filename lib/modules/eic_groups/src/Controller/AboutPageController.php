<?php

namespace Drupal\eic_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\GroupVisibilityRecord;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route response for the About page.
 */
class AboutPageController extends ControllerBase {

  /**
   * The EIC User helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $userHelper;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $groupsHelper;

  /**
   * Constructs a new AboutPageController object.
   *
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The EIC User helper service.
   * @param \Drupal\eic_groups\EICGroupsHelper $groups_helper
   *   The EIC Groups helper service.
   */
  public function __construct(UserHelper $user_helper, EICGroupsHelper $groups_helper) {
    $this->userHelper = $user_helper;
    $this->groupsHelper = $groups_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_user.helper'),
      $container->get('eic_groups.helper')
    );
  }

  /**
   * Builds the homepage title.
   */
  public function title(GroupInterface $group) {
    return $this->t('@group_name - About', ['@group_name' => $group->label()]);
  }

  /**
   * Returns the About page for a given group.
   *
   * @return array
   *   A simple renderable array.
   */
  public function build(GroupInterface $group) {
    // Initialise variables.
    $variables['owners'] = [];
    $variables['admins'] = [];
    $variables['regions_countries'] = [];
    $variables['description'] = '';

    // Get group owners.
    foreach ($group->getMembers('group-owner') as $item) {
      $variables['owners'][] = $this->userHelper->getUserLink($item->getUser());
    }
    // Get group admins.
    foreach ($group->getMembers('group-admin') as $item) {
      $variables['admins'][] = $this->userHelper->getUserLink($item->getUser());
    }
    // Get group topics.
    foreach ($group->get('field_vocab_topics')->referencedEntities() as $item) {
      $variables['topics'][] = $item->label();
    }
    // Get group regions and countries.
    foreach ($group->get('field_vocab_geo')->referencedEntities() as $item) {
      $variables['regions_countries'][] = $item->label();
    }
    $variables['description'] = $group->get('field_body')->value;
    // Get group visibility.
    $variables['visibility'] = $this->groupsHelper->getGroupVisibilitySettings($group);
    if (!empty($variables['visibility']['settings']) && $variables['visibility']['settings'] instanceof GroupVisibilityRecord) {
      $variables['visibility']['settings'] = $this->groupsHelper->getGroupVisibilityRecordSettings($variables['visibility']['settings']);
    }
    // Get joining methods.
    $variables['joining_methods'] = $this->groupsHelper->getGroupJoiningMethod($group);

    return $variables;
  }

}
