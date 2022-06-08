<?php

namespace Drupal\eic_migrate\Plugin\migrate\source;

use Drupal\book\Plugin\migrate\source\Book as BookBase;
use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom Drupal 6/7 book source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "eic_d7_book",
 *   source_module = "book",
 * )
 */
class Book extends BookBase {

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->leftJoin('node', 'parent_n', 'parent_n.nid = b.bid');
    $query->addField('parent_n', 'type', 'parent_node_type');
    $query->leftJoin('node', 'n', 'n.nid = b.nid');
    $query->addField('n', 'type', 'node_type');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    /** @var static $migrateSource */
    $migrateSource = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state')
    );
    $migrateSource->eicGroupsHelper = $container->get('eic_groups.helper');
    $migrateSource->migrateLookup = $container->get('migrate.lookup');
    return $migrateSource;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $result = parent::prepareRow($row);
    $skip = FALSE;

    // Prepares nide property using migration lookup plugin and depending on
    // the node type.
    if ($row->getSourceProperty('node_type') === 'group') {
      $group_id = $this->migrateLookup->lookup(
        [
          'upgrade_d7_node_complete_group',
        ],
        [
          $row->getSourceProperty('nid'),
        ]
      );

      $row->setSourceProperty('nid', []);
      if (empty($group_id)) {
        $skip = TRUE;
      }
      elseif ($group_book_nid = $this->getGroupBookPage($group_id[0]['id'])) {
        // Replaces the old group NID from D7 with the group book page NID in D9.
        $row->setSourceProperty('nid', [['nid' => $group_book_nid]]);
      }
      else {
        $skip = TRUE;
      }
    }
    else {
      $nid = $this->migrateLookup->lookup(
        [
          'upgrade_d7_node_complete_book',
          'upgrade_d7_node_complete_wiki_page',
        ],
        [
          $row->getSourceProperty('nid'),
        ]
      );
      $row->setSourceProperty('nid', $nid);
    }

    // Prepares nide property using migration lookup plugin and depending on
    // the parent node type.
    if ($row->getSourceProperty('parent_node_type') === 'group') {
      $group_id = $this->migrateLookup->lookup(
        [
          'upgrade_d7_node_complete_group',
        ],
        [
          $row->getSourceProperty('bid'),
        ]
      );

      $row->setSourceProperty('bid', []);
      if (!empty($group_id)) {
        if ($group_book_nid = $this->getGroupBookPage($group_id[0]['id'])) {
          // Replaces the old group NID from D7 with the group book page NID in D9.
          $row->setSourceProperty('bid', [['nid' => $group_book_nid]]);
        }
      }
    }
    else {
      $nid = $this->migrateLookup->lookup(
        [
          'upgrade_d7_node_complete_book',
          'upgrade_d7_node_complete_wiki_page',
        ],
        [
          $row->getSourceProperty('bid'),
        ]
      );
      $row->setSourceProperty('bid', $nid);
    }

    if ($skip) {
      $this->idMap->saveMessage($row->getSourceIdValues(), $this->t('Book NID from group not found.'), MigrationInterface::MESSAGE_INFORMATIONAL);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['node_type'] = $this->t('Book item node type');
    $fields['parent_node_type'] = $this->t('Parent node type');
    return $fields;
  }

  private function getGroupBookPage($group_id) {
    $migrate_connection = Database::getConnection('default', 'default');

    $query = $migrate_connection->select('group_content_field_data', 'gp');
    $query->condition('gp.type', '%group_node-book', 'LIKE');
    $query->condition('gp.gid', $group_id);
    $query->join('book', 'b', 'gp.entity_id = b.nid');
    $query->fields('b', ['bid', 'nid']);
    $query->condition('b.pid', 0);
    $query->orderBy('b.weight');
    $query->range(0, 1);
    $results = $query->execute()->fetchAll(\PDO::FETCH_OBJ);
    if (!empty($results)) {
      return $results[0]->nid;
    }

    return NULL;
  }

}
