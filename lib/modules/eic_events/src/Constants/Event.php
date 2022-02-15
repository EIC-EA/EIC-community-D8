<?php

namespace Drupal\eic_events\Constants;

/**
 * Defines constants for the Events.
 *
 * @package Drupal\eic_events\Constants
 */
final class Event {

  const WEIGHT_STATE_ONGOING = 1;

  const WEIGHT_STATE_FUTURE = 2;

  const WEIGHT_STATE_PAST = 3;

  const SOLR_FIELD_ID_WEIGHT_STATE = 'its_event_weight_state';

  const SOLR_FIELD_ID_WEIGHT_STATE_LABEL = 'ss_event_weight_state_label';

  const CRON_STATE_ID_LAST_REQUEST_TIME = 'eic_events_last_update_events';

  /**
   * Group owner role machine name for Events.
   */
  const GROUP_OWNER_ROLE = 'event-owner';

  /**
   * Group admin role machine name for Events.
   */
  const GROUP_ADMINISTRATOR_ROLE = 'event-admin';

  /**
   * Group member role machine name for Events.
   *
   * @todo Should we keep this?
   */
  const GROUP_MEMBER_ROLE = 'event-member';

  /**
   * @return array
   */
  public static function getStateLabelsMapping(): array {
    return [
      self::WEIGHT_STATE_ONGOING => t('Ongoing', [], ['context' => 'eic_events']),
      self::WEIGHT_STATE_FUTURE => t('Future', [], ['context' => 'eic_events']),
      self::WEIGHT_STATE_PAST => t('Past', [], ['context' => 'eic_events']),
    ];
  }

}
