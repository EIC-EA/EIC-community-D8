<?php

namespace Drupal\eic_groups\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush command class that contains a command to find and remove
 * nodes that are no longer part of a group.
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
    $aliases = $this->database->select('path_alias', 'pa')
      ->fields('pa', ['path', 'alias'])
      ->condition('pa.path', '%node/%', 'LIKE')
      ->condition('pa.alias', '%/groups/%', 'LIKE')
      ->execute()->fetchAllKeyed();
    $nodes_to_remove = [];
    foreach ($aliases as $path => $alias) {
      $parts = explode('/', $alias);
      $group_url = array_slice($parts, 0, 3);

      // Let's make sure the node is/was indeed part of a group.
      if ($group_url[1] === 'groups') {
        $group_url = implode('/', $group_url);
        $q = $this->database->select('path_alias', 'pa')
          ->fields('pa', ['alias'])
          ->condition('pa.alias', $group_url)
          ->execute()->fetchAssoc();
        if (empty($q)) {
          $node_id = explode('/', $path);
          $q1 = $this->database->select('node_field_data', 'nfd')
            ->fields('nfd', ['nid', 'status'])
            ->condition('nfd.status', '0')
            ->condition('nfd.nid', $node_id[2])
            ->execute()->fetchAssoc();
          if (empty($q1)) {
            $nodes_to_remove[$node_id[2]] = $alias;
          }
        }
      }
    }

    $count = count($nodes_to_remove);
    if ($this->confirm("$count orphaned nodes will be unpublished. Proceed?")) {
      $this->io()->progressStart($count);
      foreach ($nodes_to_remove as $item_node_id => $alias) {
        $node = $this->entityTypeManager->getStorage('node')->load($item_node_id);
        if (is_null($node)) {
          $this->io()->warning("No node found for $alias");
        }
        elseif ($node instanceof NodeInterface && $node->isPublished()) {
          $node->delete();
        }
        $this->io()->progressAdvance();
      }
      $this->io()->progressFinish();
      $this->io()->success("$count orphaned nodes were unpublished.");
    }
    else {
      $this->io()->note('No action has taken place.');
    }
  }
}
