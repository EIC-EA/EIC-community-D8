<?php

namespace Drupal\eic_statistics\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event that is fired when the page view count for a node is updated.
 */
class PageViewCountUpdate extends Event {

  const EVENT_NAME = 'eic_statistics_page_view_count_update';

  /**
   * The node.
   *
   * @var int
   */
  protected $nid;

  /**
   * The node page view count.
   *
   * @var int
   */
  protected $pageViewCount;

  /**
   * Constructs the object.
   *
   * @param int $nid
   *   The node ID.
   * @param int $page_view_count
   *   The page view count for the node.
   */
  public function __construct(int $nid, int $page_view_count) {
    $this->nid = $nid;
    $this->pageViewCount = $page_view_count;
  }

  /**
   * Returns the page view count.
   *
   * @return int
   *   The number of page views.
   */
  public function getPageViewCount() {
    return $this->pageViewCount;
  }

  /**
   * Returns the node.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The node object.
   */
  public function getNode() {
    return \Drupal::entityTypeManager()->getStorage('node')->load($this->nid);
  }

}
