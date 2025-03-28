<?php

namespace Drupal\eic_groups\EventSubscriber;

use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides an Event Subscriber for Search API events.
 */
class SearchApiSubscriber implements EventSubscriberInterface {

  /**
   * The Search API index field that contains user's group ids.
   */
  const USER_GROUP_MEMBERSHIP_FIELD = 'user__group_content__uid_gid';

  /**
   * The group entity from the route context.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Constructs a new SearchApiSubscriber instance.
   *
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $eic_groups_helper
   *   The EIC Groups helper service.
   */
  public function __construct(EICGroupsHelperInterface $eic_groups_helper) {
    $this->group = $eic_groups_helper->getGroupFromRoute();
  }

  /**
   * Reacts to the query alter event.
   *
   * @param \Drupal\search_api\Event\QueryPreExecuteEvent $event
   *   The query alter event.
   */
  public function queryAlter(QueryPreExecuteEvent $event) {
    $query = $event->getQuery();

    switch ($query->getSearchId()) {
      case 'views_page:group_overviews__members':
        // Add condition to filter out users based on the current group.
        // We need this until we have a views based solution.
        // @see https://www.drupal.org/project/search_api/issues/3059170
        $query->getConditionGroup()->addCondition(self::USER_GROUP_MEMBERSHIP_FIELD, $this->group->id(), 'IN');
        break;

    }

  }

  /**
   * Add x-requested-with headers for custom API call so Drupal handle it like Drupal Ajax call.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event object.
   */
  public function addXRequestWithApi(RequestEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $allowed_routes = [
      'eic_search.solr_search',
      'eic_content.entity_tree',
      'eic_content.entity_tree_children',
      'eic_content.entity_tree_search',
      'eic_content.entity_tree_create_term',
    ];

    $current_route = \Drupal::routeMatch()->getRouteName();

    if (!in_array($current_route, $allowed_routes)) {
      return;
    }

    $event->getRequest()->headers->set('X-Requested-With', 'XMLHttpRequest');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Workaround to avoid a fatal error during site install from existing
    // config.
    // @see https://www.drupal.org/project/drupal/issues/2825358
    if (!class_exists('\Drupal\search_api\Event\SearchApiEvents', TRUE)) {
      return [];
    }

    return [
      SearchApiEvents::QUERY_PRE_EXECUTE => 'queryAlter',
      KernelEvents::REQUEST => [
        ['addXRequestWithApi'],
      ],
    ];
  }

}
