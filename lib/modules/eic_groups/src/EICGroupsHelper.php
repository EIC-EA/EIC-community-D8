<?php

namespace Drupal\eic_groups;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;

/**
 * EICGroupsHelper service.
 */
class EICGroupsHelper implements EICGroupsHelperInterface {

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new EventsHelperService object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupFromRoute() {
    $entity = FALSE;
    $parameters = $this->routeMatch->getParameters()->all();
    if (!empty($parameters['group']) && is_numeric($parameters['group'])) {
      $group = Group::load($parameters['group']);
      return $group;
    }
    if (!empty($parameters)) {
      foreach ($parameters as $parameter) {
        if ($parameter instanceof EntityInterface) {
          $entity = $parameter;
          break;
        }
      }
    }
    if ($entity) {
      return $this->getGroupByEntity($entity);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupByEntity(EntityInterface $entity) {
    $group = FALSE;
    if ($entity instanceof GroupInterface) {
      return $entity;
    }
    elseif ($entity instanceof NodeInterface) {
      // Load all the group content for this entity.
      $group_content = GroupContent::loadByEntity($entity);
      // Assuming that the content can be related only to 1 group.
      $group_content = reset($group_content);
      if (!empty($group_content)) {
        $group = $group_content->getGroup();
      }
    }
    return $group;
  }

}
