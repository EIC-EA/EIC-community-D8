<?php

namespace Drupal\eic_migrate\Commands;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate_tools\Commands\MigrateToolsCommands;
use Drupal\migrate_tools\MigrateTools;

/**
 * Migrate Tools Override drush commands.
 */
class MigrateToolsOverrideCommands extends MigrateToolsCommands {

  const STATE_MIGRATIONS_IS_RUNNING = 'is_migrations_running';

  const STATE_MIGRATIONS_MESSAGES_IS_RUNNING = 'is_migrations_messages_running';

  /**
   * @TODO Migration does not exist for the moment, update if needed when it will be developed.
   */
  const MIGRATION_ID_MESSAGES = 'upgrade_d7_messages';

  private StateInterface $state;

  /**
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $migration_plugin_manager
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(
    MigrationPluginManager $migration_plugin_manager,
    DateFormatter $date_formatter,
    EntityTypeManagerInterface $entity_type_manager,
    KeyValueFactoryInterface $key_value,
    StateInterface $state
  ) {
    parent::__construct($migration_plugin_manager, $date_formatter, $entity_type_manager, $key_value);

    $this->state = $state;
  }

  /**
   * Perform one or more migration processes.
   *
   * @param string $migration_names
   *   ID of migration(s) to import. Delimit multiple using commas.
   * @param array $options
   *   Additional options for the command.
   *
   * @command migrate:import
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
   * @usage migrate:import --all
   *   Perform all migrations
   * @usage migrate:import --group=beer
   *   Import all migrations in the beer group
   * @usage migrate:import --tag=user
   *   Import all migrations with the user tag
   * @usage migrate:import --group=beer --tag=user
   *   Import all migrations in the beer group and with the user tag
   * @usage migrate:import beer_term,beer_node
   *   Import new terms and nodes
   * @usage migrate:import beer_user --limit=2
   *   Import no more than 2 users
   * @usage migrate:import beer_user --idlist=5
   *   Import the user record with source ID 5
   * @usage migrate:import beer_node_revision --idlist=1:2,2:3,3:5
   *   Import the node revision record with source IDs [1,2], [2,3], and [3,5]
   *
   * @validate-module-enabled migrate_tools
   *
   * @aliases mim, migrate-import
   *
   * @throws \Exception
   *   If there are not enough parameters to the command.
   */
  public function import(
    $migration_names = '',
    array $options = [
      'all' => FALSE,
      'group' => MigrateToolsCommands::REQ,
      'tag' => MigrateToolsCommands::REQ,
      'limit' => MigrateToolsCommands::REQ,
      'feedback' => MigrateToolsCommands::REQ,
      'idlist' => MigrateToolsCommands::REQ,
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
    $this->state->set(self::STATE_MIGRATIONS_IS_RUNNING, TRUE);

    try {
      parent::import($migration_names, $options);
    } catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
    }

    $this->logger()->notice('Migrations import ended.');
    $this->state->set(self::STATE_MIGRATIONS_IS_RUNNING, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeMigration(MigrationInterface $migration, $migration_id, array $options = []) {
    $this->state->set(self::STATE_MIGRATIONS_MESSAGES_IS_RUNNING, self::MIGRATION_ID_MESSAGES === $migration_id);
    parent::executeMigration($migration, $migration_id, $options);
    $this->state->set(self::STATE_MIGRATIONS_MESSAGES_IS_RUNNING, FALSE);
  }

}
