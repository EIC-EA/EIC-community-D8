<?php

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Implements hook_preprocess_HOOK().
 */
function eic_community_preprocess_eic_flag_count_text(&$variables) {
  if (\Drupal::currentUser()->isAnonymous() && \Drupal::request()->get('_entity') instanceof EntityInterface) {
    $variables['flag_anonymous_href_link'] = Url::fromRoute(
      'eic_flags.anonymous_flag_member_access',
      options: [
        'query' => ['destination' => \Drupal::request()->get('_entity')->toUrl()->toString()],
      ]
    )->toString();
    $variables['#attached']['library'][] =  'core/drupal.dialog.ajax';
  }
  else {
    $variables['flag_anonymous_href_link'] = FALSE;
  }
}
