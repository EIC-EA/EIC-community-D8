<?php

namespace Drupal\eic_helper;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides helper methods for dates.
 */
class DateTimeHelper {

  /**
   * Define upcoming dates, that have not started yet.
   *
   * @var string
   */
  const DATE_RANGE_UPCOMING = 'upcoming';

  /**
   * Define ongoing dates, that have started but not finished yet.
   *
   * @var string
   */
  const DATE_RANGE_ONGOING = 'ongoing';

  /**
   * Define past dates, that have started and are finished.
   *
   * @var string
   */
  const DATE_RANGE_PAST = 'past';

  /**
   * Undefined state.
   *
   * @var string
   */
  const DATE_RANGE_UNDEFINED = 'undefined';

  /**
   * Define date format name for long dates.
   */
  const DATE_FORMAT_LONG = 'long';

  /**
   * Define date format name for short dates.
   */
  const DATE_FORMAT_SHORT = 'short';

  /**
   * Define date format name for month (full) + year.
   */
  const DATE_FORMAT_MONTH_FULL_YEAR = 'month_full_year';

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
      return self::DATE_RANGE_UNDEFINED;
    }

    if ($entity->get($field_name)->getFieldDefinition()->getType() != 'daterange') {
      return self::DATE_RANGE_UNDEFINED;
    }

    if ($entity->get($field_name)->isEmpty()) {
      return self::DATE_RANGE_UNDEFINED;
    }

    $current_time = $this->dateTime->getCurrentTime();
    $start_date = strtotime($entity->get($field_name)->value);
    $end_date = strtotime($entity->get($field_name)->end_value);

    if ($start_date > $current_time) {
      return self::DATE_RANGE_UPCOMING;
    }
    elseif ($start_date <= $current_time && $end_date >= $current_time) {
      return self::DATE_RANGE_ONGOING;
    }

    return self::DATE_RANGE_PAST;
  }

}
