<?php

namespace Drupal\eic_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for EIC Groups routes.
 */
class GroupWikiOverviewController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Builds the response.
   */
  public function build(GroupInterface $group) {
    $group_top_level_wiki_pages = $this->getGroupTopLevelWikiPages($group);
    $build['content'] = [];

    if (empty($group_top_level_wiki_pages)) {
      $build['content'] = [
        '#type' => 'item',
        '#markup' => $this->t('No Wiki pages (yet)'),
      ];
    }
    else {
      $wiki_page = $this->entityTypeManager()->getStorage('node')->load($group_top_level_wiki_pages[0]->nid);
      $response = new RedirectResponse($wiki_page->toUrl()->toString());
      return $response->send();
    }
  }

  /**
   * Get top level wiki pages of a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   *
   * @return array
   *   An array of top level wiki page books.
   */
  private function getGroupTopLevelWikiPages(GroupInterface $group) {
    $query = $this->database->select('group_content_field_data', 'gp');
    $query->condition('gp.type', 'group-group_node-wiki_page');
    $query->condition('gp.gid', $group->id());
    $query->join('book', 'b', 'gp.entity_id = b.nid');
    $query->fields('b', ['bid', 'nid']);
    $query->condition('b.pid', 0);
    $query->orderBy('b.weight');
    return $query->execute()->fetchAll(\PDO::FETCH_OBJ);
  }

}
