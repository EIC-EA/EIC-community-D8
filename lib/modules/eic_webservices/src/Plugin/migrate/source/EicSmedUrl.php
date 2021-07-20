<?php

namespace Drupal\eic_webservices\Plugin\migrate\source;

use Drupal\Core\Site\Settings;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\source\Url;

/**
 * Source plugin for retrieving data via URLs, with SMED API authentication.
 *
 * This source plugin uses environment variables to connect to the SMED API.
 *
 * Example:
 *
 * @code
 * id: my_taxonomy_import
 * label: My Taxonomy Import
 * migration_tags:
 *   - eic_smed_api_authentication
 * source:
 *   plugin: eic_smed_url
 * @endcode
 *
 * @MigrateSource(
 *   id = "eic_smed_url"
 * )
 */
class EicSmedUrl extends Url {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    // Get the current migration tags.
    $migration_tags = $migration->getMigrationTags();

    // Inject the username and password values into the authentication
    // configuration.
    if (in_array('eic_smed_api_authentication', $migration_tags)) {
      if (isset($configuration['authentication'])) {
        $configuration['authentication']['username'] = Settings::get('smed_api_taxonomy_username');
        $configuration['authentication']['password'] = Settings::get('smed_api_taxonomy_password');
      }
    }

    // Run the parent constructor.
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

}
