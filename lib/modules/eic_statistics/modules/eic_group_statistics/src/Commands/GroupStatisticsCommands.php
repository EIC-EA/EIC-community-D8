<?php

namespace Drupal\eic_group_statistics\Commands;

use Drupal\eic_group_statistics\GroupStatisticsHelperInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile for updating group statistics.
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
   * Command to update all group statistics.
   *
   * @usage eic:updateGroupStatistics
   * @command eic:updateGroupStatistics
   * @aliases update-group-statistics
   */
  public function updateGroupStatistics() {
    $this->groupStatisticsHelper->updateAllGroupsStatistics();
    $this->output()->writeln('All group statistics have been updated successfully.');
    $this->logger()->info('All group statistics have been updated successfully.');
  }

}
