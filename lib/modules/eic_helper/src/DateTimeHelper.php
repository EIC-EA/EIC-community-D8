<?php

namespace Drupal\eic_helper;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides helper methods for dates.
 */
class DateTimeHelper {

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $dateTime;

  /**
   * Constructs a DateTimeHelper object.
   *
   * @param \Drupal\Component\Datetime\Time $date_time
   *   The datetime.time service.
   */
  public function __construct(Time $date_time) {
    $this->dateTime = $date_time;
  }

  /**
   * Returns the status of an entity based on a date range.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node event to check.
   * @param string $field_name
   *   The date_range field name.
   *
   * @return string
   *   The status of the event. Can be one of the following values:
   *   - default
   *   - ongoing
   *   - past
   */
  public function getDateRangeStatus(EntityInterface $entity, string $field_name) {
    if (!$entity->hasField($field_name)) {
      return 'undefined';
    }

    if ($entity->get($field_name)->getFieldDefinition()->getType() != 'daterange') {
      return 'undefined';
    }

    if ($entity->get($field_name)->isEmpty()) {
      return 'undefined';
    }

    $current_time = $this->dateTime->getCurrentTime();
    $start_date = strtotime($entity->get($field_name)->value);
    $end_date = strtotime($entity->get($field_name)->end_value);

    if ($start_date > $current_time) {
      $status = 'upcoming';
    }
    elseif ($start_date <= $current_time && $end_date >= $current_time) {
      $status = 'ongoing';
    }
    else {
      $status = 'past';
    }

    return $status;
  }

}
