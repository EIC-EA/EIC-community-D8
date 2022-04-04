<?php

namespace Drupal\eic_migrate\Commands;

use Drupal\migrate_tools\MigrateTools;
use Drush\Commands\DrushCommands;

/**
 * Migrate Tools Override drush commands.
 */
class MigrateToolsOverrideCommands extends DrushCommands {

  const STATE_MIGRATIONS_OVERRIDE = 'is_migrations_running';

  /**
   * Perform one or more migration processes.
   *
   * @param string $migration_names
   *   ID of migration(s) to import. Delimit multiple using commas.
   * @param array $options
   *   Additional options for the command.
   *
   * @command migrate-override:import
   *
   * @option all Process all migrations.
   * @option group A comma-separated list of migration groups to import
   * @option tag Name of the migration tag to import
   * @option limit Limit on the number of items to process in each migration
   * @option feedback Frequency of progress messages, in items processed
   * @option idlist Comma-separated list of IDs to import
   * @option idlist-delimiter The delimiter for records
   * @option update  In addition to processing unprocessed items from the
   *   source, update previously-imported items with the current data
   * @option force Force an operation to run, even if all dependencies are not
   *   satisfied
   * @option continue-on-failure When a migration fails, continue processing
   *   remaining migrations.
   * @option execute-dependencies Execute all dependent migrations first.
   * @option skip-progress-bar Skip displaying a progress bar.
   * @option sync Sync source and destination. Delete destination records that
   *   do not exist in the source.
   *
   * @default $options []
   *
   * @usage migrate-override:import --all
   *   Perform all migrations
   * @usage migrate-override:import --group=beer
   *   Import all migrations in the beer group
   * @usage migrate-override:import --tag=user
   *   Import all migrations with the user tag
   * @usage migrate-override:import --group=beer --tag=user
   *   Import all migrations in the beer group and with the user tag
   * @usage migrate-override:import beer_term,beer_node
   *   Import new terms and nodes
   * @usage migrate-override:import beer_user --limit=2
   *   Import no more than 2 users
   * @usage migrate-override:import beer_user --idlist=5
   *   Import the user record with source ID 5
   * @usage migrate-override:import beer_node_revision --idlist=1:2,2:3,3:5
   *   Import the node revision record with source IDs [1,2], [2,3], and [3,5]
   *
   * @validate-module-enabled migrate_tools
   *
   * @aliases miom, migrate-override-import
   *
   * @throws \Exception
   *   If there are not enough parameters to the command.
   */
  public function import(
    $migration_names = '',
    array $options = [
      'all' => FALSE,
      'group' => \Drupal\migrate_tools\Commands\MigrateToolsCommands::REQ,
      'tag' => \Drupal\migrate_tools\Commands\MigrateToolsCommands::REQ,
      'limit' => \Drupal\migrate_tools\Commands\MigrateToolsCommands::REQ,
      'feedback' => \Drupal\migrate_tools\Commands\MigrateToolsCommands::REQ,
      'idlist' => \Drupal\migrate_tools\Commands\MigrateToolsCommands::REQ,
      'idlist-delimiter' => MigrateTools::DEFAULT_ID_LIST_DELIMITER,
      'update' => FALSE,
      'force' => FALSE,
      'continue-on-failure' => FALSE,
      'execute-dependencies' => FALSE,
      'skip-progress-bar' => FALSE,
      'sync' => FALSE,
    ]
  ) {
    $this->logger()->notice('Migrations import started.');
    \Drupal::state()->set(self::STATE_MIGRATIONS_OVERRIDE, TRUE);
    /** @var \Drupal\migrate_tools\Commands\MigrateToolsCommands $migrate_tools */
    $migrate_tools = \Drupal::service('migrate_tools.commands');

    try {
      $migrate_tools->import($migration_names, $options);
    } catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
    }
    $this->logger()->notice('Migrations import ended.');
    \Drupal::state()->set(self::STATE_MIGRATIONS_OVERRIDE, FALSE);
  }

}
