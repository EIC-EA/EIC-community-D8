<?php

namespace Drupal\eic_flags;

/**
 * Provides flag request status.
 *
 * @package Drupal\eic_flags
 */
final class RequestStatus {

  const ARCHIVED = 'archived';

  const DENIED = 'denied';

  const ACCEPTED = 'accepted';

  const OPEN = 'open';

  const CANCELLED = 'cancelled';

  const TIMEOUT = 'timeout';

}
