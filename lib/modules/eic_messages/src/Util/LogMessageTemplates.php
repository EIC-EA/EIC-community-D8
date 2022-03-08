<?php

namespace Drupal\eic_messages\Util;

/**
 * Helper class for log message templates.
 */
final class LogMessageTemplates {

  /**
   * LOG Message template for archival/delete requests.
   */
  const REQUEST_ARCHIVAL_DELETE = 'log_request_accepted';

  /**
   * LOG Message template for ownership transfer requests.
   */
  const REQUEST_OWNERSHIP_TRANSFER = 'log_req_owner_transfer_accepted';

  /**
   * LOG Message template for masquerade as user.
   */
  const MASQUERADE_USER = 'log_masquerade_user';

}
