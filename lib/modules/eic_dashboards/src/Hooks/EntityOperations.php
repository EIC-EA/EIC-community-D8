<?php

namespace Drupal\eic_dashboards\Hooks;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\node\NodeInterface;
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
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cacheBackend;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache_backend,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('cache.default'),
    );
  }

  /**
   * Creates a menu item for the dashboard page in the Group main menu.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group for which we create the menu item.
   */
  public function createGroupDashboardPageMenuLink(EntityInterface $group) {
    if ($group->bundle() !== 'group') {
      return 0;
    }

    foreach (group_content_menu_get_menus_per_group($group) as $group_menu) {
      if (
        $group_menu->getGroupContentType()
          ->getContentPlugin()
          ->getPluginId() == 'group_content_menu:group_main_menu'
      ) {
        // Create menu item.
        $menu_name = GroupContentMenuInterface::MENU_PREFIX . $group_menu->getEntity()
            ->id();
        $menu_item = $this->entityTypeManager->getStorage('menu_link_content')
          ->create([
            'title' => $this->t('Dashboard'),
            'link' => [
              'uri' => 'route:eic_dashboards.group.dashboard;group=' . $group->id(),
            ],
            'menu_name' => $menu_name,
            'weight' => -10,
          ]);

        try {
          $menu_item->save();
        }
        catch (EntityStorageException $e) {
          return FALSE;
        }
      }
    }
  }

  /**
   * Invalidate the individual dashboard cache of a group type group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return void
   */
  public function updateIndividualDashboardCache(EntityInterface $entity) {
    if ($entity instanceof GroupInterface) {
      /** @var GroupInterface $entity */
      if ($entity->bundle() === 'group') {
        $this->cacheBackend->invalidate('dashboard:group:' . $entity->id());
      }
    }
    if ($entity instanceof GroupContentInterface) {
      /** @var GroupContentInterface $entity */
      $group = $entity->getGroup();
      if ($group->bundle() === 'group') {
        $this->cacheBackend->invalidate('dashboard:group:' . $group->id());
      }
    }
    if ($entity instanceof NodeInterface) {
      /** @var GroupContentInterface[] $groupcontent */
      $groupcontent = $this->entityTypeManager
        ->getStorage('group_content')->loadByEntity($entity);
      if ($groupcontent) {
        $groupcontent = reset($groupcontent);
        $group = $groupcontent->getGroup();
        if ($group->bundle() === 'group') {
          $this->cacheBackend->invalidate('dashboard:group:' . $group->id());
        }
      }
    }
  }

}
