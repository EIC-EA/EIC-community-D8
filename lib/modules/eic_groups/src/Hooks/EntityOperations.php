<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\group_permissions\GroupPermissionsManagerInterface;
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
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManagerInterface
   */
  protected $groupPermissionsManager;

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
   * @param \Drupal\group_permissions\GroupPermissionsManagerInterface $group_permissions_manager
   *   The group permissions manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    EICGroupsHelperInterface $eic_groups_helper,
    PathautoGeneratorInterface $pathauto_generator,
    UserHelper $user_helper,
    OECGroupFlexHelper $oec_group_flex_helper,
    GroupPermissionsManagerInterface $group_permissions_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->eicGroupsHelper = $eic_groups_helper;
    $this->pathautoGenerator = $pathauto_generator;
    $this->userHelper = $user_helper;
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
    $this->groupPermissionsManager = $group_permissions_manager;
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
      $container->get('oec_group_flex.helper'),
      $container->get('group_permission.group_permissions_manager')
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
    // Publish group wiki when group is published.
    if (!$entity->original->isPublished() && $entity->isPublished()) {
      $this->publishGroupWiki($entity);
    }
    // Updates group owner permissions.
    $this->updateGroupOwnerPermissions($entity);
  }

  /**
   * Implements hook_group_permission_insert().
   */
  public function groupPermissionInsert(GroupPermissionInterface $group_permissions) {
    $group = $group_permissions->getGroup();
    // Adds or removes "delete group" permission from group owner based on the
    // group moderation state.
    if ($group->get('moderation_state')->value === 'pending') {
      $this->eicGroupsHelper->addRolePermissionsToGroup($group_permissions, EICGroupsHelper::GROUP_OWNER_ROLE, ['delete group']);
    }
    else {
      $this->eicGroupsHelper->removeRolePermissionsFromGroup($group_permissions, EICGroupsHelper::GROUP_OWNER_ROLE, ['delete group']);
    }
    // Save group permissions.
    $this->eicGroupsHelper->saveGroupPermissions($group_permissions);
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

        // Get the descriptions for each plugin.
        $variables['visibility']['description'] = $this->eicGroupsHelper->getGroupFlexPluginDescription('visibility', $variables['visibility']['plugin_id']);
        foreach ($variables['joining_methods'] as $index => $joining_method) {
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
              $build['link_add_child_wiki_page'] = $add_wiki_page_urls['add_child_wiki_page']->toString();
              $build['link_add_child_wiki_page_renderable'] = Link::fromTextAndUrl($this->t('Add a new wiki page'), $add_wiki_page_urls['add_child_wiki_page'])->toRenderable();
            }
            // Unsets book navigation since we already have that show in the
            // eic_groups_wiki_book_navigation block plugin.
            unset($build['book_navigation']);
          }
        }
        elseif ($entity->bundle() === 'wiki_page') {
          // Add wiki page create form url to the build array.
          if ($add_wiki_page_urls = $this->getWikiPageAddFormUrls($entity)) {
            $build['link_add_current_level_wiki_page'] = $add_wiki_page_urls['add_current_level_wiki_page']->toString();
            $build['link_add_current_level_wiki_page_renderable'] = Link::fromTextAndUrl($this->t('Add a new page on the current level'), $add_wiki_page_urls['add_current_level_wiki_page'])->toRenderable();
            $build['link_add_current_level_wiki_page_renderable']['#suffix'] = '<br>';
            $build['link_add_child_wiki_page'] = $add_wiki_page_urls['add_child_wiki_page']->toString();
            $build['link_add_child_wiki_page_renderable'] = Link::fromTextAndUrl($this->t('Add a new wiki page below this page'), $add_wiki_page_urls['add_child_wiki_page'])->toRenderable();
          }
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
          'weight' => 1,
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
   * Updates group owner permissions based on moderation state.
   *
   * @param \Drupal\group\Entity\GroupInterface $entity
   *   The Group entity.
   */
  protected function updateGroupOwnerPermissions(GroupInterface $entity) {
    /** @var \Drupal\group_permissions\Entity\GroupPermissionInterface $group_permissions */
    $group_permissions = $this->groupPermissionsManager->loadByGroup($entity);

    // Get moderation states.
    $old_moderation_state = $entity->original->get('moderation_state')->value;
    $new_moderation_state = $entity->get('moderation_state')->value;

    // If group moderation state hasn't changed, we do nothing.
    if ($old_moderation_state === $new_moderation_state) {
      return;
    }

    // We add or remove "delete group" permission from the group owner based on
    // the new group moderation state.
    if ($new_moderation_state === 'pending') {
      $this->eicGroupsHelper->addRolePermissionsToGroup($group_permissions, EICGroupsHelper::GROUP_OWNER_ROLE, ['delete group']);
    }
    else {
      $this->eicGroupsHelper->removeRolePermissionsFromGroup($group_permissions, EICGroupsHelper::GROUP_OWNER_ROLE, ['delete group']);
    }

    $this->eicGroupsHelper->saveGroupPermissions($group_permissions);
  }

}
