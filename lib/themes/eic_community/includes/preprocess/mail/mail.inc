<?php

/**
 * @file
 * Contains mail related functions.
 */

use Drupal\Core\Url;

/**
 * Implements template_preprocess_mimemail_message().
 */
function eic_community_preprocess_mimemail_message(&$variables) {
  $link = Url::fromRoute('eic_user.anonymous.my_settings', [], ['absolute' => TRUE])->toString();
  $variables['my_settings_link'] = $link;
}
