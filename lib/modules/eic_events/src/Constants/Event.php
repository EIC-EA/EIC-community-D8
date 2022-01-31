<?php

namespace Drupal\eic_events\Constants;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class Event
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
