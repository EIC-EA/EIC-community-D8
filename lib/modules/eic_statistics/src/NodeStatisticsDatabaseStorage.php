<?php

namespace Drupal\eic_statistics;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Drupal\eic_statistics\Event\PageViewCountUpdate;
use Drupal\statistics\NodeStatisticsDatabaseStorage as CoreNodeStatisticsDatabaseStorage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the default database storage backend for statistics.
 */
class NodeStatisticsDatabaseStorage extends CoreNodeStatisticsDatabaseStorage {

  /**
   * The Node statistics database storage inner service.
   *
   * @var \Drupal\statistics\NodeStatisticsDatabaseStorage
   */
  protected $innerService;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs the statistics storage.
   *
   * @param \Drupal\statistics\NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorageInner
   *   The Node statistics database storage inner service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection for the node view storage.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(
    CoreNodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorageInner,
    Connection $connection,
    StateInterface $state,
    RequestStack $request_stack,
    EventDispatcherInterface $event_dispatcher
  ) {
    parent::__construct($connection, $state, $request_stack);
    $this->innerService = $nodeStatisticsDatabaseStorageInner;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function recordView($id) {
    $page_views = $this->innerService->recordView($id);
    // Dispatch an event.
    $event = new PageViewCountUpdate($id, $page_views);
    $this->eventDispatcher->dispatch($event, PageViewCountUpdate::EVENT_NAME);
    $node_view_counter_state_cache = $this->state->get(StatisticsHelper::NODE_VIEW_COUNTER_REINDEX_STATE_CACHE, []);
    // The node view counter is not re-indexed everytime we view a node page.
    // Therefore, there is a cron responsible for re-indexing the nodes at a
    // later stage. Here we reset the state cache used in the cron so that we
    // avoid re-indexing the entity multiple times.
    if (!in_array($id, $node_view_counter_state_cache)) {
      $node_view_counter_state_cache[] = $id;
      $this->state->set(StatisticsHelper::NODE_VIEW_COUNTER_REINDEX_STATE_CACHE, $node_view_counter_state_cache);
    }
    // On each stat update we invalidate the stat cache for the given node and all nodes (this one isn't used for the moment)
    Cache::invalidateTags(["eic_statistics:node:$id"]);

    return $page_views;
  }

  /**
   * Magic method to return any method call inside the inner service.
   */
  public function __call($method, $args) {
    return call_user_func_array([$this->innerService, $method], $args);
  }

}
