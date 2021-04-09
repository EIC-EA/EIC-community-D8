<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\book\Plugin\Block\BookNavigationBlock;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Provides a 'Book navigation' block.
 *
 * @Block(
 *   id = "eic_groups_wiki_book_navigation",
 *   admin_label = @Translation("EIC Group Wiki Book navigation"),
 *   category = @Translation("European Innovation Council")
 * )
 */
class GroupWikiBookNavigationBlock extends BookNavigationBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_bid = 0;

    if ($group = $this->getGroupFromRoute()) {
      $results = $this->getGroupTopLevelWikiPages($group);

      if ($node = $this->requestStack->getCurrentRequest()->get('node')) {
        $current_bid = empty($node->book['bid']) ? 0 : $node->book['bid'];
      }

      $book_menus = [];
      $pseudo_tree = [0 => ['below' => FALSE]];
      foreach ($results as $row) {
        $book = $this->bookManager->loadBookLink($row->bid);
        // Check whether user can access the book link.
        $book_node = Node::load($book['nid']);
        $book['access'] = $book_node->access('view');

        if ($row->bid == $current_bid) {
          // If the current page is a node associated with a book, the whole
          // parent tree needs to be retrieved.
          $data = $this->bookManager->bookTreeAllData($node->book['bid'], $node->book);
          $book_menus[$row->bid] = $this->bookManager->bookTreeOutput($data);
        }
        else {
          // Since we know we will only display a link to the top node, there
          // is no reason to run an additional menu tree query for each book.
          $book['in_active_trail'] = FALSE;
          $pseudo_tree[0]['link'] = $book;
          $book_menus[$row->bid] = $this->bookManager->bookTreeOutput($pseudo_tree);
        }
      }
      if ($book_menus) {
        return [
          '#theme' => 'book_all_books_block',
        ] + $book_menus;
      }
    }

    return [];
  }

  /**
   * Get the group from the current route match.
   *
   * @return bool|\Drupal\group\Entity\GroupInterface
   *   The Group entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getGroupFromRoute() {
    $entity = FALSE;
    $parameters = \Drupal::routeMatch()->getParameters()->all();
    if (!empty($parameters['group']) && is_numeric($parameters['group'])) {
      $group = Group::load($parameters['group']);
      return $group;
    }
    if (!empty($parameters)) {
      foreach ($parameters as $parameter) {
        if ($parameter instanceof EntityInterface) {
          $entity = $parameter;
          break;
        }
      }
    }
    if ($entity) {
      return $this->getGroupByEntity($entity);
    }
    return FALSE;
  }

  /**
   * Get Group of a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity.
   *
   * @return bool|\Drupal\group\Entity\GroupInterface
   *   The Group entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getGroupByEntity(EntityInterface $entity) {
    $group = FALSE;
    if ($entity instanceof GroupInterface) {
      return $entity;
    }
    elseif ($entity instanceof NodeInterface) {
      // Load all the group content for this entity.
      $group_content = GroupContent::loadByEntity($entity);
      // Assuming that the content can be related only to 1 group.
      $group_content = reset($group_content);
      if (!empty($group_content)) {
        $group = $group_content->getGroup();
      }
    }
    return $group;
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
    $query = \Drupal::database()->select('group_content_field_data', 'gp');
    $query->fields('gp', ['entity_id']);
    $query->condition('gp.type', 'group-group_node-wiki_page');
    $query->condition('gp.gid', $group->id());
    $query->join('book', 'b', 'gp.entity_id = b.nid');
    $query->fields('b', ['bid']);
    $query->condition('b.pid', 0);
    return $query->execute()->fetchAll(\PDO::FETCH_OBJ);
  }

}
