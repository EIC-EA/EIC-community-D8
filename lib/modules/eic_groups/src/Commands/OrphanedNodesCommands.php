<?php

namespace Drupal\eic_groups\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
class OrphanedNodesCommands extends DrushCommands {

  protected $entityTypeManager;

  protected $database;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, Connection $database) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
  }

  /**
   * Finds all nodes that were part of a group, which is now deleted.
   *
   * @usage eic_groups:orphanednodes
   *   Finds all nodes that were part of a group, which is now deleted.
   *
   * @command eic_groups:orphanednodes
   * @aliases eic-orphaned
   */
  public function actionOrphanedNodes() {
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple();
    foreach ($nodes as $node) {
      $url = $node->toUrl()->toString();
      $parts = explode('/', $url);
      $group_url = array_slice($parts, 0, 3);

      // Let's make sure the node is/was indeed part of a group.
      if ($group_url[1] === 'groups') {
        $group_url = implode('/', $group_url);
        $q = $this->database->select('path_alias', 'pa')
          ->fields('pa', ['alias'])
          ->condition('pa.alias', $group_url)
          ->execute()->fetchAssoc();
        if (empty($q)) {
          //$node->delete();
          //$node->setUnpublished();
          $this->io()->writeln($url);
        }
      }
    }
    $this->io()->success('Ran.');
  }

}
