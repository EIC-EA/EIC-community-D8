<?php

namespace Drupal\eic_overviews;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an overview page entity type.
 */
interface OverviewPageInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the overview page title.
   *
   * @return string
   *   Title of the overview page.
   */
  public function getTitle();

  /**
   * Sets the overview page title.
   *
   * @param string $title
   *   The overview page title.
   *
   * @return \Drupal\eic_overviews\OverviewPageInterface
   *   The called overview page entity.
   */
  public function setTitle($title);

  /**
   * Gets the overview page creation timestamp.
   *
   * @return int
   *   Creation timestamp of the overview page.
   */
  public function getCreatedTime();

  /**
   * Sets the overview page creation timestamp.
   *
   * @param int $timestamp
   *   The overview page creation timestamp.
   *
   * @return \Drupal\eic_overviews\OverviewPageInterface
   *   The called overview page entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the overview page status.
   *
   * @return bool
   *   TRUE if the overview page is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the overview page status.
   *
   * @param bool $status
   *   TRUE to enable this overview page, FALSE to disable.
   *
   * @return \Drupal\eic_overviews\OverviewPageInterface
   *   The called overview page entity.
   */
  public function setStatus($status);

}
