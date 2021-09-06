<?php

namespace Drupal\eic_media_statistics\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * Event that is fired when the download count for an entity is updated.
 */
class DownloadCountUpdate extends Event {

  const EVENT_NAME = 'eic_media_statistics_download_count_update';

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The file download count.
   *
   * @var int
   */
  protected $downloadCount;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being updated.
   * @param int $download_count
   *   The download count for the entity.
   */
  public function __construct(EntityInterface $entity, int $download_count) {
    $this->entity = $entity;
    $this->downloadCount = $download_count;
  }

  /**
   * Returns the download count.
   *
   * @return int
   *   The number of file downloads.
   */
  public function getDownloadCount() {
    return $this->downloadCount;
  }

  /**
   * Returns the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity object.
   */
  public function getEntity() {
    return $this->entity;
  }

}
