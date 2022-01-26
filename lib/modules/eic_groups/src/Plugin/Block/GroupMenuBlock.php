<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\eic_overviews\GroupOverviewPages;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_content_menu\Plugin\Block\GroupMenuBlock as GroupMenuBlockBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom group content menu block.
 *
 * @Block(
 *   id = "eic_group_content_menu",
 *   admin_label = @Translation("EIC Group Menu"),
 *   category = @Translation("European Innovation Council"),
 *   deriver = "Drupal\group_content_menu\Plugin\Derivative\GroupMenuBlock",
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group", required = FALSE)
 *   }
 * )
 */
class GroupMenuBlock extends GroupMenuBlockBase implements ContainerFactoryPluginInterface {

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  protected $eicGroupsHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $block_plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $block_plugin->eicGroupsHelper = $container->get('eic_groups.helper');
    return $block_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    $menu_name = $this->getMenuName();
    if (!$menu_name) {
      return [];
    }

    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);

    // If there is no menu link in the active trail, we try to set the active
    // trail based on the current node.
    if (count($parameters->activeTrail) === 1 &&
      empty($parameters->activeTrail[0]) &&
      !empty($build['#items'])
    ) {
      $this->setMenuTreeActiveTrail($build['#items']);
    }

    return $build;
  }

  /**
   * Sets active trail for the menu tree based on the current page.
   *
   * For group content nodes that don't have a menu link, we need to set the
   * active trail on the first level of the menu based on the node bundle.
   *
   * @param array $tree
   *   Array of menu links.
   */
  private function setMenuTreeActiveTrail(array &$tree) {
    // Do nothing if no group was found in the context or in the current route.
    if ((!$group = $this->getContextValue('group')) || !$group->id()) {
      $group = $this->eicGroupsHelper->getGroupFromRoute();
    }

    if (!$group || !$group instanceof GroupInterface) {
      return;
    }

    if (\Drupal::routeMatch()->getRouteName() !== 'entity.node.canonical') {
      return;
    }

    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node instanceof NodeInterface) {
      return;
    }

    $group_contents = GroupContent::loadByEntity($node);

    if (empty($group_contents)) {
      return;
    }

    $group_content = reset($group_contents);

    if ($group_content->getGroup()->id() !== $group->id()) {
      return;
    }

    $url = FALSE;

    switch ($node->bundle()) {
      case 'discussion':
        // Gets group discussions overview page url.
        $url = GroupOverviewPages::getGroupOverviewPageUrl(
          'discussions',
          $group
        );
        break;

      case 'document':
      case 'gallery':
      case 'video':
        // Gets group files overview page url.
        $url = GroupOverviewPages::getGroupOverviewPageUrl(
          'files',
          $group
        );
        break;

      case 'wiki_page':
        // Gets group wiki url.
        $group_book_page_nid = $this->eicGroupsHelper->getGroupBookPage($group);
        $group_book_page_node = $this->entityTypeManager->getStorage('node')->load($group_book_page_nid);
        $url = $group_book_page_node->toUrl();
        break;

      case 'event':
        // Gets group files overview page url.
        $url = GroupOverviewPages::getGroupOverviewPageUrl(
          'events',
          $group
        );
        break;

      default:
        return;
    }

    foreach ($tree as $key => $value) {
      if ($value['url']->toString() === $url->toString()) {
        $tree[$key]['in_active_trail'] = TRUE;
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Vary by url path context.
    $contexts = ['url.path'];
    return Cache::mergeContexts(parent::getCacheContexts(), $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuInstance() {
    $entity = $this->getContext('group')->getContextData()->getValue();
    $has_context = $entity ? TRUE : FALSE;

    // If entity is not in the context or cannot be grabbed from the current
    // route, we don't load the menu.
    if ((!$entity) && !$entity = $this->eicGroupsHelper->getGroupFromRoute()) {
      return NULL;
    }

    // Don't load menu for group entities that are new/unsaved.
    if ($entity->isNew()) {
      return NULL;
    }

    if (!$has_context) {
      $this->setContextValue('group', $entity);
    }

    // Group menu plugin ID that will be used to load the group content entity.
    $group_menu_plugin_id = 'group_content_menu:group_main_menu';

    /** @var \Drupal\group\Entity\Storage\GroupContentStorage $groupStorage */
    $groupStorage = $this->entityTypeManager->getStorage('group_content');
    $contentPluginId = $groupStorage->loadByContentPluginId($group_menu_plugin_id);

    if (empty($contentPluginId)) {
      return NULL;
    }

    $instances = $groupStorage->loadByGroup($entity, $group_menu_plugin_id);
    if ($instances) {
      return array_pop($instances)->getEntity();
    }
    return NULL;
  }

}
