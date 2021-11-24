<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Entity\User;

/**
 * Class UserGenerator
 *
 * @package Drupal\eic_default_content\Generator
 */
class UserGenerator extends CoreGenerator {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct();

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $users = [
      [
        'name' => 'trusted_user',
        'pass' => 'secret',
        'roles' => [
          'trusted_user',
        ],
      ],
      [
        'name' => 'ungrouped_user',
        'pass' => 'secret',
        'roles' => [
          'trusted_user',
        ],
      ],
      [
        'name' => 'content_admin',
        'pass' => 'secret',
        'roles' => [
          'content_administrator',
        ],
      ],
      [
        'name' => 'site_admin',
        'pass' => 'secret',
        'roles' => [
          'site_admin',
        ],
      ],
    ];

    foreach ($users as $key => $user) {
      $user = User::create($user + [
          'init' => 'email',
          'field_first_name' => 'User #' . $key,
          'field_last_name' => 'Role ' . reset($user['roles']),
          'field_media' => $this->getRandomImage('public://'),
          'mail' => $user['name'] . '@eic.local',
          'langcode' => 'en',
          'preferred_langcode' => 'en',
        ]);

      $user->activate();
      $user->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $query = $user_storage->getQuery('u');

    $user_ids = $query
      ->condition('uid', 1, '>')
      ->execute();

    if (empty($user_ids)) {
      return;
    }

    $user_storage->delete($user_storage->loadMultiple($user_ids));
  }

}
