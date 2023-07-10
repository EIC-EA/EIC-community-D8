<?php

namespace Drupal\eic_dummy_content\Generator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_default_content\Generator\CoreGenerator;
use Drupal\eic_topics\Constants\Topics;
use Drupal\eic_user\ProfileConst;
use Drupal\profile\Entity\Profile;
use Drupal\user\Entity\User;

/**
 * Generates default users.
 *
 * @package Drupal\eic_dummy_content\Generator
 */
class UserGenerator extends CoreGenerator {

  /**
   * List of countries/cities.
   */
  protected const ADDRESSES = [
    'FR' => [
      'Paris',
      'Strasbourg',
      'Bordeaux',
      'Marseille',
    ],
    'BE' => [
      'Brussels',
      'Gent',
      'Antwerp',
      'Namur',
    ],
    'NL' => [
      'Amsterdam',
      'Eindhoven',
      'Rotterdam',
    ],
    'IT' => [
      'Roma',
      'Bologna',
      'Milan',
    ],
  ];

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
    $trusted_user_data = [
      'pass' => 'secret',
      'roles' => [
        'trusted_user',
      ],
    ];

    $users = [
      [
        'name' => 'trusted_user',
        'pass' => 'secret',
        'roles' => [
          'trusted_user',
        ],
      ],
      [
        'name' => 'sensitive_user',
        'pass' => 'secret',
        'roles' => [
          'sensitive',
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
      [
        'name' => 'web_service',
        'pass' => 'secret',
        'roles' => [
          'service_authentication',
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
          'field_user_status' => 'user_valid',
        ]);

      $user->activate();
      $user->save();
    }

    for ($i = 0; $i <= 10; $i++) {
      // Create user account.
      $user = User::create($trusted_user_data + [
          'name' => 'trusted_user' . $i,
          'init' => 'email',
          'field_first_name' => 'User #' . $i,
          'field_last_name' => 'Trusted User #' . $i,
          'field_media' => $this->getRandomImage('public://'),
          'mail' =>  'trusted_user' . $i . '@eic.local',
          'langcode' => 'en',
          'preferred_langcode' => 'en',
          'field_user_status' => 'user_valid',
        ]);

      $user->activate();
      $user->save();
      $this->createUserMemberProfile($user->id());
    }

    // Also create a profile for our beloved admin.
    $this->createUserMemberProfile(1);
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

  /**
   * Creates a member profile for the given user.
   *
   * @param int $uid
   *   The user ID.
   */
  public function createUserMemberProfile(int $uid) {
    // Create member profile.
    $country = array_rand(self::ADDRESSES);
    $city = self::ADDRESSES[$country][array_rand(self::ADDRESSES[$country])];
    $profile = Profile::create([
      'type' => ProfileConst::MEMBER_PROFILE_TYPE_NAME,
      'uid' => $uid,
      'field_body' => $this->getFormattedText('filtered_html'),
      'field_vocab_topic_expertise' => $this->getRandomEntities('taxonomy_term', ['vid' => Topics::TERM_VOCABULARY_TOPICS_ID], 3),
      'field_vocab_topic_interest' => $this->getRandomEntities('taxonomy_term', ['vid' => Topics::TERM_VOCABULARY_TOPICS_ID], 3),
      'field_vocab_geo' => $this->getRandomEntities('taxonomy_term', ['vid' => 'geo'], 3),
      'field_vocab_language' => $this->getRandomEntities('taxonomy_term', ['vid' => 'languages'], 4),
      'field_vocab_job_title' => $this->getRandomEntities('taxonomy_term', ['vid' => 'job_titles'], 2),
      'field_location_address' => [
        'country_code' => $country,
        'locality' => $city,
      ],
    ]);

    $profile->setDefault(TRUE);
    $profile->save();
  }

}
