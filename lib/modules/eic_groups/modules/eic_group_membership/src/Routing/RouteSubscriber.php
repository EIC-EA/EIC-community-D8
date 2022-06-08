<?php

namespace Drupal\eic_group_membership\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {


  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The action forms manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Make sure our implementation runs after other modules.
    // Attention: be sure that this method runs before
    // \Drupal\eic_admin\Routing\RouteSubscriber::alterRoutes().
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -50];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Clone the 'entity.group_content.edit_form' route for group memberships.
    // This will allow us to customise the group membership edit form without
    // changing the generic group_content edit form.
    if ($route = $collection->get('entity.group_content.edit_form')) {
      $new_route = clone $route;
      $new_route->setPath('/group/{group}/members/{group_content}/edit');
      $parameters = $new_route->getOption('parameters');
      foreach ($parameters as $key => $param) {
        if ($key == 'group_content') {
          $parameters[$key]['bundle'] = $this->getGroupMembershipBundles();
        }
        $new_route->setOption('parameters', $parameters);
      }
      $new_route->setOption('_admin_route', FALSE);
      // We need to remove the '_group_operation_route' option to prevent this
      // page to be rendered in the admin theme.
      if ($route->hasOption('_group_operation_route')) {
        $options = $route->getOptions();
        unset($options['_group_operation_route']);
        $route->setOptions($options);
      }
      $collection->add('eic_group_membership.group_membership.edit_form', $new_route);
    }
  }

  /**
   * Returns the list of group_membership bundle for all group types.
   *
   * @return string[]
   *   An array of group_membership plugin IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getGroupMembershipBundles() {
    $bundles = [];
    $group_types = $this->entityTypeManager->getStorage('group_type')->loadMultiple();
    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    foreach ($group_types as $group_type) {
      if (!$group_type->hasContentPlugin('group_membership')) {
        continue;
      }

      $bundles[] = $group_type->getContentPlugin('group_membership')->getContentTypeConfigId();
    }
    return $bundles;
  }

}
