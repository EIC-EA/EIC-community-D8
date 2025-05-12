<?php

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\eic_dashboards\Constants\DashboardsDatabase;

/**
 * Populate eic_dashboards for members dashboard.
 */
function eic_dashboards_deploy_0001_members_past_stats(array &$sandbox) {

  $entity_type_id = 'user';
  $entity_query = \Drupal::entityQuery($entity_type_id)
    ->condition('status', '1')
    ->accessCheck(FALSE);

  _eic_dashboards_populate_database_batch_helper($sandbox, $entity_query, 50, $entity_type_id, DashboardsDatabase::GROUPS_DASHBOARD_TYPE);

}

/**
 * Populate eic_dashboards for groups dashboard.
 */
function eic_dashboards_deploy_0002_groups_past_stats(array &$sandbox) {

  $entity_type_id = 'group';
  $entity_query = \Drupal::entityQuery($entity_type_id)
    ->condition('type', 'group')
    ->accessCheck(FALSE);

  _eic_dashboards_populate_database_batch_helper($sandbox, $entity_query, 50, $entity_type_id, DashboardsDatabase::GROUPS_DASHBOARD_TYPE);

}

/**
 * Populate eic_dashboards for events dashboard.
 */
function eic_dashboards_deploy_0003_events_past_stats(array &$sandbox) {

  $entity_type_id = 'group';
  $entity_query = \Drupal::entityQuery($entity_type_id)
    ->condition('type', 'event')
    ->accessCheck(FALSE);

  _eic_dashboards_populate_database_batch_helper($sandbox, $entity_query, 50, $entity_type_id, DashboardsDatabase::EVENTS_DASHBOARD_TYPE);

}

function _eic_dashboards_populate_database_batch_helper(array &$sandbox, QueryInterface $entity_query, $entities_per_batch, $entity_type_id, $dashboard_type) {

  $count_entity_query = clone $entity_query;
  if (!isset($sandbox['total'])) {
    $sandbox['total'] = $count_entity_query->count()->execute();
    $sandbox['current'] = 0;

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $ids = $entity_query
    ->range($sandbox['current'], $entities_per_batch)
    ->execute();
  if (empty($ids)) {
    $sandbox['#finished'] = 1;
    return;
  }

  foreach ($ids as $id) {
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type_id)->load($id);
    $monthKey = \Drupal::service('date.formatter')->format($entity->get('created')->value, 'custom', 'Y-m') . '-01';
    \Drupal::service('eic_dashboards.cumulative')->insertOrUpdate($dashboard_type, $monthKey);
    $sandbox['current']++;
  }

  \Drupal::messenger()
    ->addMessage($sandbox['current'] . ' entities processed.');

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;
    // We are done with populating the database. We need to fix the records so
    // each month we add the previous count plus the running's.
    \Drupal::service('eic_dashboards.cumulative')->calculatePastStats($dashboard_type);
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }


}
