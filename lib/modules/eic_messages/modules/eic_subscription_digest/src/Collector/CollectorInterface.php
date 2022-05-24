<?php

namespace Drupal\eic_subscription_digest\Collector;

use DateTimeInterface;
use Drupal\user\UserInterface;

/**
 * Interface that classes collecting messages for a particular topic must implement.
 */
interface CollectorInterface {

  /**
   * @param \Drupal\user\UserInterface $user
   * @param \DateTimeInterface $start_date
   * @param \DateTimeInterface $end_date
   *
   * @return array
   */
  public function getMessages(UserInterface $user, DateTimeInterface $start_date, DateTimeInterface $end_date): array;

}
