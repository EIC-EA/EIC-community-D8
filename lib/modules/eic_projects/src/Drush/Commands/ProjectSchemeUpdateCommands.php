<?php

namespace Drupal\eic_projects\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class ProjectSchemeUpdateCommands extends DrushCommands {

  /**
   * Constructs a ProjectSchemeUpdateCommands object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ModuleExtensionList        $moduleExtensionList,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('extension.list.module'),
    );
  }

  /**
   * Command description here.
   */
  #[CLI\Command(name: 'eic_projects:update-scheme', aliases: ['projects_update_scheme'])]
  #[CLI\Usage(name: 'eic_projects:update-scheme', description: 'Load the mapping CSV and update the terms')]
  public function updateProjectScheme() {
    $module_path = DRUPAL_ROOT . '/' . $this->moduleExtensionList->getPath('eic_projects');
    $csv_path = $module_path . '/includes/projects-scheme.csv';

    if (!file_exists($csv_path)) {
      $this->logger()
        ->error(dt('CSV file not found at @path', ['@path' => $csv_path]));
      return self::EXIT_FAILURE_WITH_CLARITY;
    }

    $handle = fopen($csv_path, 'r');
    if ($handle === FALSE) {
      $this->logger()->error(dt('Unable to open CSV file.'));
      return self::EXIT_FAILURE_WITH_CLARITY;
    }

    // Skip header row if exists
    fgetcsv($handle);

    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $updated_count = 0;
    $not_found_count = 0;
    $skipped_count = 0;

    // Write a foreach on all CSV rows
    while (($row = fgetcsv($handle)) !== FALSE) {
      if (count($row) < 3) {
        continue;
      }

      // col1 is term label (project ID)
      $project_id = trim($row[0]);
      // col2 is term field_label (project label)
      // col3 is final term field_programme_title (mapped project label)
      $mapped_project_label = trim($row[2]);

      // Search term entity by project ID
      $terms = $term_storage->loadByProperties([
        'name' => $project_id,
      ]);

      if (!empty($terms)) {
        /** @var \Drupal\taxonomy\Entity\Term $term */
        $term = reset($terms);

        // Set value col3 to entity
        if ($term->hasField('field_programme_title')) {
          if (
            !($term->get('field_programme_title')->isEmpty()) &&
            ($term->get('field_programme_title')->value === $mapped_project_label)
          ) {
            $skipped_count++;
            continue;
          }
          $term->set('field_programme_title', $mapped_project_label);
          $term->save();
          $updated_count++;
          $this->logger()->success(dt('Updated term @id with label: @label', [
            '@id' => $project_id,
            '@label' => $mapped_project_label,
          ]));
        }
        else {
          $this->logger()
            ->warning(dt('Term @id does not have field_programme_title field.', ['@id' => $project_id]));
        }
      }
      else {
        $not_found_count++;
        $this->logger()
          ->warning(dt('Term with ID @id not found.', ['@id' => $project_id]));
      }
    }

    fclose($handle);

    $this->logger()
      ->success(dt('Processing complete. Updated: @updated, Skipped: @skipped, Not found: @not_found', [
        '@updated' => $updated_count,
        '@skipped' => $skipped_count,
        '@not_found' => $not_found_count,
      ]));

    return self::EXIT_SUCCESS;
  }


}
