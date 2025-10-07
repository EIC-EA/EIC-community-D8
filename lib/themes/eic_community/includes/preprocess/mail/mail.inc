<?php

/**
 * @file
 * Contains mail related functions.
 */

use Drupal\Core\Url;

// Set the mail module and keys that should not display the manage notification
// section. The mimemail module alters the module name and key, replacing
// underscores with hyphens.
define('NOTIFICATION_PREFERENCES_MAIL_KEYS', ['message-notify' => ['notify-contact-global-confirm', 'notify-contact-global-copy'], 'contact' => ['page-mail']]);

/**
 * Implements template_preprocess_mimemail_message().
 */
function eic_community_preprocess_mimemail_message(&$variables) {
  $link = Url::fromRoute('eic_user.anonymous.my_settings', [], ['absolute' => TRUE])->toString();
  $variables['my_settings_link'] = $link;

  $variables['show_manage_notification_section'] = TRUE;
  if (array_key_exists($variables['module'], NOTIFICATION_PREFERENCES_MAIL_KEYS)
    && in_array($variables['key'], NOTIFICATION_PREFERENCES_MAIL_KEYS[$variables['module']], TRUE)) {
    $variables['show_manage_notification_section'] = FALSE;
  }
}
