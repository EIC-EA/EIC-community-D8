<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Generates menu items.
 *
 * @package Drupal\eic_default_content\Generator
 */
class MenuGenerator extends CoreGenerator {

  /**
   * The menu links to generate.
   *
   * @var array
   */
  protected $menuLinks = [
    'main' => [
      [
        'title' => 'Home',
        'link' => ['uri' => 'internal:/'],
        'weight' => 0,
      ],
      [
        'title' => 'Stories',
        'link' => ['uri' => 'internal:/articles'],
        'weight' => 1,
      ],
      [
        'title' => 'Topics',
        'link' => ['uri' => 'internal:/topics'],
        'weight' => 2,
      ],
      [
        'title' => 'Groups',
        'link' => ['uri' => 'internal:/groups'],
        'weight' => 3,
      ],
      [
        'title' => 'Events',
        'link' => ['uri' => 'internal:/events'],
        'weight' => 4,
      ],
      [
        'title' => 'Members',
        'link' => ['uri' => 'internal:/people'],
        'weight' => 5,
      ],
      [
        'title' => 'Organisations',
        'link' => ['uri' => 'internal:/organisations'],
        'weight' => 6,
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function load() {
    foreach ($this->menuLinks as $menu_name => $items) {
      foreach ($items as $item) {
        $menu_item = [
          'title' => $item['title'],
          'link' => $item['link'],
          'menu_name' => $menu_name,
          'expanded' => $item['expanded'] ?? FALSE,
          'weight' => $item['weight'] ?? 0,
        ];
        $menu_link = MenuLinkContent::create($menu_item);
        $menu_link->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    // Make sure we only delete the items created by this generator.
    foreach ($this->menuLinks as $menu_name => $items) {
      foreach ($items as $item) {
        $conditions = [
          'menu_name' => $menu_name,
          'link' => $item['link'],
        ];
        $this->unloadEntities('menu_link_content', $conditions);
      }
    }
  }

}
