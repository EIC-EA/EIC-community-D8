<?php

namespace Drupal\eic_content\Services;

use Drupal\eic_content\TreeWidget\TreeWidgetGroupProperty;
use Drupal\eic_content\TreeWidget\TreeWidgetProperties;
use Drupal\eic_content\TreeWidget\TreeWidgetUserProperty;
use Drupal\taxonomy\Entity\Term;

/**
 * Class EntityTreeManager
 *
 * @package Drupal\eic_content\Services
 */
class EntityTreeManager {

  /**
   * @param $target_entity
   * @param $target_bundle
   * @param false $load_all
   * @param int $offset
   * @param int $length
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function generateTree($target_entity, $target_bundle, $load_all = FALSE, $offset = 0, $length = 50) {
    $terms = \Drupal::entityTypeManager()->getStorage($target_entity)
      ->loadTree($target_bundle, 0, 1);

    if (!$load_all) {
      $terms = array_slice($terms, $offset, $length);
    }

    $tree = [];

    foreach ($terms as $tree_object) {
      $this->buildTree($tree, $tree_object, $target_bundle, 1, 0, $target_entity);
    }

    return $tree;
  }

  /**
   * @param $target_entity
   * @param $target_bundle
   * @param $parent
   * @param $level
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadChildrenLevel($target_entity, $target_bundle, $parent, $level) {
    $children = \Drupal::entityTypeManager()->getStorage($target_entity)
      ->loadTree($target_bundle, $parent, 1);

    foreach ($children as &$child) {
      $child->children = \Drupal::entityTypeManager()
        ->getStorage($target_entity)
        ->loadTree($target_bundle, $child->tid, 1);

      $child->level = $level + 1;
    }

    return $children;
  }

  /**
   * @param $target_entity
   * @param $target_bundle
   * @param $text
   * @param $ignored_values
   * @param bool $disable_top_selection
   *
   * @return array|array[]
   */
  public function search($target_entity, $target_bundle, $text, $ignored_values, bool $disable_top_selection = FALSE) {
    //We need to ignore values in suggestions that are already selected by the user
    $ignored_tids = array_map(function ($selected_value) {
      $selected_value = json_decode($selected_value, TRUE);

      return $selected_value['tid'];
    }, $ignored_values);

    $tree_property = $this->getTreeWidgetProperty($target_entity);

    if ($target_entity !== 'taxonomy_term') {
      return $tree_property->generateSearchQueryResults($text);
    }
    else {
      $query = \Drupal::entityQuery($target_entity)
        ->condition('vid', $target_bundle)
        ->condition('name', $text, 'CONTAINS')
        ->range(0, 20);

      if ($disable_top_selection) {
        $query->condition('parent', 0, '<>');
      }

      if (!empty($ignored_tids)) {
        $query->condition('tid', $ignored_tids, 'NOT IN');
      }

      $entities = $query->execute();
      $entities = Term::loadMultiple($entities);

      return array_map(function (Term $term) {
        $parent = $term->get('parent')->getValue();

        return [
          'name' => $term->getName(),
          'tid' => $term->id(),
          'parent' => (int) reset($parent)['target_id'],
        ];
      }, $entities);
    }
  }

  /**
   * @param string $entity_type
   *   The entity type.
   *
   * @return \Drupal\eic_content\TreeWidget\TreeWidgetProperties|null
   *   Return the class link to the entity type.
   */
  public function getTreeWidgetProperty(string $entity_type): ?TreeWidgetProperties {
    switch ($entity_type) {
      case 'group':
        return \Drupal::classResolver(TreeWidgetGroupProperty::class);
        break;
      case 'user':
        return \Drupal::classResolver(TreeWidgetUserProperty::class);
        break;
    }

    return NULL;
  }

  /**
   * Return all required translation keys for the Entity Tree Widget.
   *
   * @return array
   */
  public static function getTranslationsWidget(): array {
    return [
      'title' => t('Replies', [], ['context' => 'eic_groups'])->render(),
      'no_results_title' => t(
        "We haven't found any comments",
        [],
        ['context' => 'eic_group']
      )->render(),
      'no_results_body' => t(
        'Please try again with another keyword',
        [],
        ['context' => 'eic_group']
      )->render(),
      'load_more' => t('Load more', [], ['context' => 'eic_groups'])->render(),
      'edit' => t('Edit', [], ['context' => 'eic_groups'])->render(),
      'options' => t('Options', [], ['context' => 'eic_groups'])->render(),
      'reply_to' => t('Reply', [], ['context' => 'eic_groups'])->render(),
      'in_reply_to' => t('in reply to', [], ['context' => 'eic_groups']
      )->render(),
      'reply' => t('Reply', [], ['context' => 'eic_groups'])->render(),
      'submit' => t('Submit', [], ['context' => 'eic_groups'])->render(),
      'reason' => t('Reason', [], ['context' => 'eic_groups'])->render(),
      'comment_placeholder' => t(
        'Type your message here...',
        [],
        ['context' => 'eic_groups']
      )->render(),
      'comment_added' => t(
        'Comment added',
        [],
        ['context' => 'eic_groups']
      )->render(),
      'comment_deleted' => t(
        'Comment deleted',
        [],
        ['context' => 'eic_groups']
      )->render(),
      'comment_edited' => t(
        'Comment edited',
        [],
        ['context' => 'eic_groups']
      )->render(),
      'comment_liked' => t(
        'Comment liked',
        [],
        ['context' => 'eic_groups']
      )->render(),
      'comment_unliked' => t(
        'Comment unliked',
        [],
        ['context' => 'eic_groups']
      )->render(),
      'comment_request_archived' => t(
        'Your archived request has been sent',
        [],
        ['context' => 'eic_groups']
      )->render(),
      'comment_request_deleted' => t(
        'Your delete request has been sent',
        [],
        ['context' => 'eic_groups']
      )->render(),
      'action_request_default' => t(
        'Your request has been sent',
        [],
        ['context' => 'eic_groups']
      )->render(),
      'action_edit_comment' => t(
        'Edit comment',
        [],
        ['context' => 'eic_groups']
      )->render(),
      'action_delete_comment' => t(
        'Delete comment',
        [],
        ['context' => 'eic_groups']
      )->render(),
      'action_request_delete' => t(
        'Request deletion',
        [],
        ['context' => 'eic_groups']
      )->render(),
      'action_request_archival' => t(
        'Request archival',
        [],
        ['context' => 'eic_groups']
      )->render(),
      'select_value' => t(
        'Select a value',
        [],
        ['context' => 'eic_search']
      )->render(),
      'match_limit' => t(
        'You can select only <b>@match_limit</b> top-level items.',
        ['@match_limit' => 0],
        ['context' => 'eic_search']
      )->render(),
      'search' => t('Search', [], ['context' => 'eic_search'])->render(),
      'your_values' => t(
        'Your selected values',
        [],
        ['context' => 'eic_search']
      )->render(),
      'required_field' => t(
        'This field is required',
        [],
        ['context' => 'eic_content']
      )->render(),
      'select_users' => t(
        'Select users',
        [],
        ['context' => 'eic_content']
      )->render(),
      'modal_invite_users_title' => t(
        'Invite user(s)',
        [],
        ['context' => 'eic_content']
      )->render(),
    ];
  }

  /**
   * @param $tree
   * @param $object
   * @param $vocabulary
   * @param $depth
   * @param $level
   * @param $target_entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function buildTree(&$tree, $object, $vocabulary, $depth, $level, $target_entity) {
    if ($object->depth !== 0) {
      return;
    }
    $tree[$object->tid] = $object;
    $tree[$object->tid]->children = [];
    $tree[$object->tid]->level = $level;
    $object_children = &$tree[$object->tid]->children;

    $children = \Drupal::entityTypeManager()->getStorage($target_entity)
      ->loadChildren($object->tid);

    if (!$children) {
      return;
    }

    $level += 1;

    $child_tree_objects = \Drupal::entityTypeManager()
      ->getStorage($target_entity)
      ->loadTree($vocabulary, $object->tid);

    foreach ($children as $child) {
      foreach ($child_tree_objects as $child_tree_object) {
        if ($child_tree_object->tid == $child->id()) {
          $this->buildTree($object_children, $child_tree_object, $vocabulary, $depth, $level, $target_entity);
        }
      }
    }
  }

}
