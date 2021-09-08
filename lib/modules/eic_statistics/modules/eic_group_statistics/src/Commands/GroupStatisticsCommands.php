<?php

namespace Drupal\eic_group_statistics\Commands;

use Drupal\eic_group_statistics\GroupStatisticsHelperInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class GroupStatisticsCommands extends DrushCommands {

  /**
   * The EIC group statistics helper service.
   *
   * @var \Drupal\eic_group_statistics\GroupStatisticsHelperInterface
   */
  protected $groupStatisticsHelper;

  /**
   * Constructs a new GroupStatisticsStorage object.
   *
   * @param \Drupal\eic_group_statistics\GroupStatisticsHelperInterface $group_statistics_helper
   *   The EIC group statistics helper service.
   */
  public function __construct(GroupStatisticsHelperInterface $group_statistics_helper) {
    $this->groupStatisticsHelper = $group_statistics_helper;
  }

  /**
   * Command description here.
   *
   * @usage eic_group_statistics:updateGroupStatistics
   *   Usage description
   *
   * @command eic_group_statistics:updateGroupStatistics
   * @aliases update-group-statistics
   */
  public function updateGroupStatistics() {
    $this->groupStatisticsHelper->updateAllGroupsStatistics();
    $this->output()->writeln('All group statistics have been updated successfully.');
    $this->logger()->info('All group statistics have been updated successfully.');
  }

}
