<?php

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\eic_dashboards\Constants\DashboardsDatabase;
use Drupal\eic_dashboards\Hooks\EntityOperations;

/**
 * Populate eic_dashboards for members dashboard.
 */
function eic_dashboards_deploy_0001_members_past_stats(array &$sandbox) {

  $entity_type_id = 'user';
  $entity_query = \Drupal::entityQuery($entity_type_id)
    ->condition('status', '1')
    ->accessCheck(FALSE);

  _eic_dashboards_populate_database_batch_helper(
    $sandbox,
    $entity_query,
    50,
    $entity_type_id,
    DashboardsDatabase::MEMBERS_DASHBOARD_TYPE
  );

}

/**
 * Populate eic_dashboards for groups dashboard.
 */
function eic_dashboards_deploy_0002_groups_past_stats(array &$sandbox) {

  $entity_type_id = 'group';
  $entity_query = \Drupal::entityQuery($entity_type_id)
    ->condition('type', 'group')
    ->accessCheck(FALSE);

  _eic_dashboards_populate_database_batch_helper(
    $sandbox,
    $entity_query,
    50,
    $entity_type_id,
    DashboardsDatabase::GROUPS_DASHBOARD_TYPE)
  ;

}

/**
 * Populate eic_dashboards for events dashboard.
 */
function eic_dashboards_deploy_0003_events_past_stats(array &$sandbox) {

  $entity_type_id = 'group';
  $entity_query = \Drupal::entityQuery($entity_type_id)
    ->condition('type', 'event')
    ->accessCheck(FALSE);

  _eic_dashboards_populate_database_batch_helper(
    $sandbox,
    $entity_query,
    50,
    $entity_type_id,
    DashboardsDatabase::EVENTS_DASHBOARD_TYPE
  );

}

/**
 * Populate eic_dashboards for organisations dashboard.
 */
function eic_dashboards_deploy_0004_organisations_past_stats(array &$sandbox) {

  $entity_type_id = 'group';
  $entity_query = \Drupal::entityQuery($entity_type_id)
    ->condition('type', 'organisation')
    ->accessCheck(FALSE);

  _eic_dashboards_populate_database_batch_helper(
    $sandbox,
    $entity_query,
    50,
    $entity_type_id,
    DashboardsDatabase::ORGANISATIONS_DASHBOARD_TYPE
  );

}

/**
 * Populate eic_dashboards for projects dashboard.
 */
function eic_dashboards_deploy_0005_projects_past_stats(array &$sandbox) {

  $entity_type_id = 'group';
  $entity_query = \Drupal::entityQuery($entity_type_id)
    ->condition('type', 'project')
    ->accessCheck(FALSE);

  _eic_dashboards_populate_database_batch_helper(
    $sandbox,
    $entity_query,
    50,
    $entity_type_id,
    DashboardsDatabase::PROJECTS_DASHBOARD_TYPE
  );

}

/**
 * Populate eic_dashboards for documents dashboard.
 */
function eic_dashboards_deploy_0006_documents_past_stats(array &$sandbox) {

  $entity_type_id = 'node';
  $entity_query = \Drupal::entityQuery($entity_type_id)
    ->condition('type', 'document')
    ->accessCheck(FALSE);

  _eic_dashboards_populate_database_batch_helper(
    $sandbox,
    $entity_query,
    50,
    $entity_type_id,
    DashboardsDatabase::DOCUMENTS_DASHBOARD_TYPE
  );

}

/**
 * Populate eic_dashboards for stories dashboard.
 */
function eic_dashboards_deploy_0007_stories_past_stats(array &$sandbox) {

  $entity_type_id = 'node';
  $entity_query = \Drupal::entityQuery($entity_type_id)
    ->condition('type', 'story')
    ->accessCheck(FALSE);

  _eic_dashboards_populate_database_batch_helper(
    $sandbox,
    $entity_query,
    50,
    $entity_type_id,
    DashboardsDatabase::STORIES_DASHBOARD_TYPE
  );

}

/**
 * Populate eic_dashboards for discussions dashboard.
 */
function eic_dashboards_deploy_0008_discussions_past_stats(array &$sandbox) {

  $entity_type_id = 'node';
  $entity_query = \Drupal::entityQuery($entity_type_id)
    ->condition('type', 'discussion')
    ->accessCheck(FALSE);

  _eic_dashboards_populate_database_batch_helper(
    $sandbox,
    $entity_query,
    50,
    $entity_type_id,
    DashboardsDatabase::DISCUSSIONS_DASHBOARD_TYPE
  );

}

/**
 * Add "Dashboard" link to Group content menu.
 */
function eic_dashboards_deploy_0009_add_link_to_groups(array &$sandbox) {
  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $em */
  $em = \Drupal::service('entity_type.manager');
  $groups = $em->getStorage('group')->loadByProperties([
    'type' => 'group',
  ]);

  foreach ($groups as $group) {
    \Drupal::classResolver(EntityOperations::class)->createGroupDashboardPageMenuLink($group);
  }
}

/**
 * Helper function to batch process entities to populate dashboards table.
 *
 * @param array $sandbox
 * @param \Drupal\Core\Entity\Query\QueryInterface $entity_query
 * @param int $entities_per_batch
 * @param int|string $entity_type_id
 * @param string $dashboard_type
 *
 * @return void
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function _eic_dashboards_populate_database_batch_helper(array &$sandbox, QueryInterface $entity_query, int $entities_per_batch, int|string $entity_type_id, string $dashboard_type): void {

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

  $data_table = \Drupal::entityTypeManager()->getStorage($entity_type_id)->getDataTable();
  $entity_column_id = \Drupal::entityTypeManager()->getStorage($entity_type_id)->getEntityType()->getKey('id');
  if (!$data_table) {
    $sandbox['current'] += count($ids);
    \Drupal::messenger()->addError(t("Could not process entities of $entity_type_id in $dashboard_type dashboard."));
    return;
  }
  foreach ($ids as $id) {
    $created_query = \Drupal::database()->select($data_table);
    $created_query->addField($data_table, 'created');
    $created_query->addField($data_table, $entity_column_id);
    $created_query->condition("$data_table.$entity_column_id", $id);
    $results = $created_query->execute()->fetchAssoc();
    $monthKey = \Drupal::service('date.formatter')->format($results['created'], 'custom', 'Y-m') . '-01';
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
