<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_content_wiki_page\WikiPageBookManager;
use Drupal\eic_groups\Constants\NodeProperty;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\node\NodeInterface;
use Drupal\oec_group_flex\GroupVisibilityRecord;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Drupal\pathauto\PathautoGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations for entity hooks.
 */
class EntityOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  protected $eicGroupsHelper;

  /**
   * The pathauto generator.
   *
   * @var \Drupal\pathauto\PathautoGeneratorInterface
   */
  protected $pathautoGenerator;

  /**
   * The EIC User helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $userHelper;

  /**
   * The OEC Group Flex helper service.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $eic_groups_helper
   *   The EIC Groups helper service.
   * @param \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator
   *   The pathauto generator.
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The EIC User helper service.
   * @param \Drupal\oec_group_flex\OECGroupFlexHelper $oec_group_flex_helper
   *   The OEC Group Flex helper service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    EICGroupsHelperInterface $eic_groups_helper,
    PathautoGeneratorInterface $pathauto_generator,
    UserHelper $user_helper,
    OECGroupFlexHelper $oec_group_flex_helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->eicGroupsHelper = $eic_groups_helper;
    $this->pathautoGenerator = $pathauto_generator;
    $this->userHelper = $user_helper;
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('eic_groups.helper'),
      $container->get('pathauto.generator'),
      $container->get('eic_user.helper'),
      $container->get('oec_group_flex.helper')
    );
  }

  /**
   * Implements hook_group_insert().
   */
  public function groupInsert(EntityInterface $entity) {
    $this->createGroupWikiBook($entity);
    $this->createGroupAboutPageMenuLink($entity);
  }

  /**
   * Implements hook_group_update().
   */
  public function groupUpdate(GroupInterface $entity) {
    // The group status has changed to publish.
    if (!$entity->original->isPublished() && $entity->isPublished()) {
      // Publish group wiki when group is published.
      $this->publishGroupWiki($entity);
      // Invalidates group contents cache when the group has been published.
      $this->invalidateGroupContentCache($entity);
    }

    // The group status has changed to unpublish.
    if ($entity->original->isPublished() && !$entity->isPublished()) {
      // Invalidates group contents cache when the group has been unpublished.
      $this->invalidateGroupContentCache($entity);
    }
  }

  /**
   * Implements hook_group_view().
   */
  public function groupView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    switch ($view_mode) {
      case 'about_page':
        // Provides info for the groups About pages.
        // Initialise variables.
        $variables['owners'] = [];
        $variables['admins'] = [];

        // Get group owners.
        foreach ($entity->getMembers('group-owner') as $item) {
          $variables['owners'][] = $this->userHelper->getUserLink($item->getUser());
        }

        // Get group admins.
        foreach ($entity->getMembers('group-admin') as $item) {
          $variables['admins'][] = $this->userHelper->getUserLink($item->getUser());
        }

        // Get group visibility.
        $variables['visibility'] = $this->oecGroupFlexHelper->getGroupVisibilitySettings($entity);
        if (!empty($variables['visibility']['settings']) && $variables['visibility']['settings'] instanceof GroupVisibilityRecord) {
          $variables['visibility']['settings'] = $this->oecGroupFlexHelper->getGroupVisibilityRecordSettings($variables['visibility']['settings']);
        }

        // Get joining methods.
        $variables['joining_methods'] = $this->oecGroupFlexHelper->getGroupJoiningMethod($entity);

        // Get the title and descriptions for each plugin.
        $variables['visibility']['title'] = $this->eicGroupsHelper->getGroupFlexPluginTitle('visibility', $variables['visibility']['plugin_id']);
        $variables['visibility']['description'] = $this->eicGroupsHelper->getGroupFlexPluginDescription('visibility', $variables['visibility']['plugin_id']);
        foreach ($variables['joining_methods'] as $index => $joining_method) {
          $variables['joining_methods'][$index]['title'] = $this->eicGroupsHelper->getGroupFlexPluginTitle('joining_method', $joining_method['plugin_id']);
          $variables['joining_methods'][$index]['description'] = $this->eicGroupsHelper->getGroupFlexPluginDescription('joining_method', $joining_method['plugin_id']);
        }

        $build += $variables;
        break;

    }
  }

  /**
   * Implements hook_node_view().
   */
  public function nodeView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    switch ($this->routeMatch->getRouteName()) {
      case 'entity.node.canonical':
        if ($entity->bundle() === 'book') {
          if ($group = $this->eicGroupsHelper->getGroupByEntity($entity)) {
            // Add empty wiki section message.
            $build['wiki_section_message'] = [
              '#type' => 'item',
              '#markup' => $this->t('No Wiki pages (yet)'),
            ];
            // Add wiki page create form url to the build array.
            if ($add_wiki_page_urls = $this->getWikiPageAddFormUrls($entity, $group)) {
              if ($add_wiki_page_urls['add_child_wiki_page']->access()) {
                $build['link_add_child_wiki_page_renderable'] = Link::fromTextAndUrl($this->t('Add a new wiki page'), $add_wiki_page_urls['add_child_wiki_page'])->toRenderable();
              }
            }
            // Unsets book navigation since we already have that show in the
            // eic_groups_wiki_book_navigation block plugin.
            unset($build['book_navigation']);
            // Adds user group permissions cache.
            $build['#cache']['contexts'][] = 'user.group_permissions';
          }
        }
        elseif ($entity->bundle() === 'wiki_page') {
          // Add wiki page create form url to the build array.
          if ($add_wiki_page_urls = $this->getWikiPageAddFormUrls($entity)) {
            if ($add_wiki_page_urls['add_current_level_wiki_page']->access()) {
              $build['link_add_current_level_wiki_page_renderable'] = Link::fromTextAndUrl($this->t('Add a new page on the current level'), $add_wiki_page_urls['add_current_level_wiki_page'])->toRenderable();
              $build['link_add_current_level_wiki_page_renderable']['#suffix'] = '<br>';
            }

            if ($add_wiki_page_urls['add_child_wiki_page']->access()) {
              // If the wiki page depth doesn't reach the maximum limit, then we
              // can show the button to add a new child wiki page.
              if (!$entity->book['p' . (WikiPageBookManager::BOOK_MAX_DEPTH + 1)]) {
                $build['link_add_child_wiki_page_renderable'] = Link::fromTextAndUrl($this->t('Add a new wiki page below this page'), $add_wiki_page_urls['add_child_wiki_page'])->toRenderable();
              }
            }
          }
          // Adds user group permissions cache.
          $build['#cache']['contexts'][] = 'user.group_permissions';
        }
        break;

    }
  }

  /**
   * Creates top level book page for group wiki section.
   */
  public function createGroupWikiBook(GroupInterface $entity) {
    $installedContentPlugins = $entity->getGroupType()->getInstalledContentPlugins();
    if ($installedContentPlugins && in_array('group_node:book', $installedContentPlugins->getInstanceIds())) {
      $node_values = [
        'title' => "{$entity->label()} - Wiki",
        'type' => 'book',
        'uid' => $entity->getOwnerId(),
        'status' => $entity->get('status')->value,
        'langcode' => $entity->language()->getId(),
        'book' => [
          'bid' => 'new',
          'plid' => 0,
        ],
      ];
      $node = $this->entityTypeManager->getStorage('node')->create($node_values);
      $node->save();
      $entity->addContent($node, 'group_node:book');
    }
  }

  /**
   * Gets wiki page add form Urls from the current wiki/book page.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity (either a node book or wiki_page).
   * @param bool|\Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   *
   * @return bool|\Drupal\Core\Url
   *   The Url object for the wiki page add form route.
   */
  private function getWikiPageAddFormUrls(EntityInterface $entity, $group = FALSE) {
    if (!($group instanceof GroupInterface)) {
      if (!$group = $this->eicGroupsHelper->getGroupByEntity($entity)) {
        return $group;
      }
    }

    $link_wiki_page_route_parameters = [
      'group' => $group->id(),
      'plugin_id' => 'group_node:wiki_page',
    ];
    $link_options = [
      'add_current_level_wiki_page' => [
        'query' => [
          'parent' => $entity->book['pid'],
        ],
      ],
      'add_child_wiki_page' => [
        'query' => [
          'parent' => $entity->id(),
        ],
      ],
    ];
    $links = [];
    foreach ($link_options as $key => $options) {
      $links[$key] = Url::fromRoute('entity.group_content.create_form', $link_wiki_page_route_parameters, $options);
    }
    return $links;
  }

  /**
   * Publishes group wiki book page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   */
  private function publishGroupWiki(GroupInterface $group) {
    $query = $this->entityTypeManager->getStorage('group_content')->getQuery();
    $query->condition('type', 'group-group_node-book');
    $query->condition('gid', $group->id());
    $query->range(0, 1);
    $results = $query->execute();

    if (!empty($results)) {
      $group_content = GroupContent::load(reset($results));
      if (($node_book = $group_content->getEntity()) && $node_book instanceof NodeInterface) {
        $node_book->setPublished();
        $node_book->save();
      }
    }
  }

  /**
   * Creates a menu item for the About page in the Group main menu.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group for which we create the menu item.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent|false
   *   The saved menu item or FALSE if an error occurred.
   */
  protected function createGroupAboutPageMenuLink(EntityInterface $group) {
    foreach (group_content_menu_get_menus_per_group($group) as $group_menu) {
      if ($group_menu->getGroupContentType()->getContentPlugin()->getPluginId() == 'group_content_menu:group_main_menu') {
        // Create menu item.
        $menu_name = GroupContentMenuInterface::MENU_PREFIX . $group_menu->getEntity()->id();
        $menu_item = $this->entityTypeManager->getStorage('menu_link_content')->create([
          'title' => $this->t('About'),
          'link' => [
            'uri' => 'internal:/group/' . $group->id() . '/about',
          ],
          'menu_name' => $menu_name,
          'weight' => 7,
        ]);

        try {
          $menu_item->save();
          return $menu_item;
        }
        catch (EntityStorageException $e) {
          return FALSE;
        }
      }
    }
  }

  /**
   * Implements hook_entity_field_access().
   */
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    $access = AccessResult::neutral();

    if (!$items) {
      return $access;
    }

    $entity = $items->getEntity();

    if ($entity instanceof GroupInterface && $entity->bundle() === 'group') {
      $group_restricted_fields = [
        'field_related_groups',
        'field_related_news_stories',
      ];

      // If field is non of the restricted ones, we do nothing.
      if (!in_array($field_definition->getName(), $group_restricted_fields)) {
        return $access;
      }

      switch ($operation) {
        case 'edit':
          // Deny access if it's a new group and the user doesn't have
          // "site_admin" or "content_administrator" roles.
          if ($entity->isNew()) {
            $access = AccessResult::forbiddenIf(!UserHelper::isPowerUser($account))
              ->addCacheableDependency($account);
            break;
          }
          break;

      }
    }
    elseif ($entity instanceof NodeInterface) {
      $access = AccessResult::neutral();

      if ($operation !== 'edit') {
        return $access;
      }

      switch ($field_definition->getName()) {
        case NodeProperty::MEMBER_CONTENT_EDIT_ACCESS:

          if ($entity->isNew()) {
            $group = $this->eicGroupsHelper->getGroupFromRoute();

            if (!$group) {
              return AccessResult::forbidden();
            }

            return $access;
          }

          /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $storage */
          $storage = $this->entityTypeManager->getStorage('group_content');
          $group_contents = $storage->loadByEntity($entity);

          // Wiki page is not part of a group, so we always hide the field.
          if (empty($group_contents)) {
            return AccessResult::forbidden();
          }

          // If user is the group author, we allow access.
          if ($entity->getOwnerId() === $account->id()) {
            break;
          }

          // If user is a power user, we allow access.
          if (UserHelper::isPowerUser($account)) {
            break;
          }

          $group_content = reset($group_contents);
          $group = $group_content->getGroup();

          // If user is a group admin, we allow access.
          if (EICGroupsHelper::userIsGroupAdmin($group, $account)) {
            break;
          }

          // At this point it means the user is just a group member and
          // therefore we deny access to edit the field.
          $access = AccessResult::forbidden();
          break;

      }
    }

    return $access;
  }

  /**
   * Invalidates group contents cache of a given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $entity
   *   The group entity.
   */
  public function invalidateGroupContentCache(GroupInterface $entity) {
    $installedContentPluginIds = $entity->getGroupType()->getInstalledContentPlugins()->getInstanceIds();

    $node_plugins = array_filter($installedContentPluginIds, function ($key) {
      // We skip group content plugins that are not nodes.
      if (strpos($key, 'group_node:') === FALSE) {
        return FALSE;
      }

      // Group book pages cannot be flagged.
      if (strpos($key, 'group_node:book') !== FALSE) {
        return FALSE;
      }

      return TRUE;
    }, ARRAY_FILTER_USE_KEY);

    // Loads all group contents of the group and invalidate cache.
    foreach ($node_plugins as $plugin_id) {
      $group_contents = $entity->getContent($plugin_id);

      foreach ($group_contents as $group_content) {
        $node = $group_content->getEntity();
        Cache::invalidateTags($node->getCacheTagsToInvalidate());
      }
    }
  }

  /**
   * Check for each node that has field member_content_edit_access if user
   * has permission to edit it
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $operation
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultNeutral
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function entityAccess(
    EntityInterface $entity,
    string $operation,
    AccountInterface $account
  ) {
    if (
      'update' === $operation &&
      $entity instanceof \Drupal\node\NodeInterface &&
      $entity->hasField(NodeProperty::MEMBER_CONTENT_EDIT_ACCESS)
    ) {
      $access = AccessResult::neutral();

      if ($entity->isNew()) {
        $group = $this->eicGroupsHelper->getGroupFromRoute();

        if (!$group) {
          return AccessResult::forbidden();
        }

        return $access;
      }

      /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('group_content');
      $group_contents = $storage->loadByEntity($entity);

      // Wiki page is not part of a group, so we always hide the field.
      if (empty($group_contents)) {
        return AccessResult::forbidden();
      }

      // If user is the group author, we allow access.
      if ($entity->getOwnerId() === $account->id()) {
        return $access;
      }

      // If user is a power user, we allow access.
      if (UserHelper::isPowerUser($account)) {
        return $access;
      }

      $group_content = reset($group_contents);
      $group = $group_content->getGroup();

      // If user is a group admin, we allow access.
      if (EICGroupsHelper::userIsGroupAdmin($group, $account)) {
        return $access;
      }

      // At this point it means the user is just a group member and
      // therefore we deny access to edit the entity.
      $access = AccessResult::forbidden();

      return $access;
    }
  }

}
