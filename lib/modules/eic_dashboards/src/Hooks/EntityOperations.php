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
   * Constructs a new EntityOperations object.
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

}
