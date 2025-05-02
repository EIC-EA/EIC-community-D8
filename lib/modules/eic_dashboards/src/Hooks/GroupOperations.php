<?php

namespace Drupal\eic_dashboards\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GroupOperations.
 *
 * Implementations for Group entity hooks.
 */
class GroupOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Implements hook_group_insert().
   */
  public function groupInsert(EntityInterface $entity) {
    $this->createGroupDashboardPageMenuLink($entity);
  }

  /**
   * Creates a menu item for the dashboard page in the Group main menu.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group for which we create the menu item.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent|false
   *   The saved menu item or FALSE if an error occurred.
   */
  protected function createGroupDashboardPageMenuLink(EntityInterface $group) {
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
          return $menu_item;
        }
        catch (EntityStorageException $e) {
          return FALSE;
        }
      }
    }
  }

}
