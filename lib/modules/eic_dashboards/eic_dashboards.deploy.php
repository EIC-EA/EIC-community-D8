<?php

use Drupal\eic_dashboards\Constants\DashboardsDatabase;

/**
 * Implements hook_deploy_NAME().
 */
function eic_dashboards_deploy_0001_members_past_stats(array &$sandbox) {
  if (!isset($sandbox['total'])) {
    $sandbox['total'] = \Drupal::entityQuery('user')
      ->condition('status', '1')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $sandbox['current'] = 0;

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $users_per_batch = 50;
  $uids = \Drupal::entityQuery('user')
    ->condition('status', '1')
    ->accessCheck(FALSE)
    ->range($sandbox['current'], $users_per_batch)
    ->execute();
  if (empty($uids)) {
    $sandbox['#finished'] = 1;
    return;
  }

  foreach ($uids as $uid) {
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
    $monthKey = \Drupal::service('date.formatter')->format($user->get('created')->value, 'custom', 'Y-m') . '-01';
    \Drupal::service('eic_dashboards.cumulative')->insertOrUpdate(DashboardsDatabase::MEMBERS_DASHBOARD_TYPE, $monthKey);
    $sandbox['current']++;
  }

    \Drupal::messenger()
      ->addMessage($sandbox['current'] . ' users processed.');

    if ($sandbox['current'] >= $sandbox['total']) {
      $sandbox['#finished'] = 1;
      // We are done with populating the database. We need to fix the records so
      // each month we add the previous count plus the running's.
      \Drupal::service('eic_dashboards.cumulative')->calculatePastStats(DashboardsDatabase::MEMBERS_DASHBOARD_TYPE);
    }
    else {
      $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
    }

}
