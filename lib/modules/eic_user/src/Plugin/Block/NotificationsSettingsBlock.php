<?php

namespace Drupal\eic_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\eic_user\NotificationTypes;
use Drupal\eic_user\Service\NotificationSettingsManager;
use Drupal\eic_user\UserHelper;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NotificationsSettingsBlock
 *
 * @package Drupal\eic_user\Plugin\Block
 *
 * @Block(
 *   id = "eic_user_notifications_settings",
 *   admin_label = @Translation("EIC User Notifications Settings"),
 *   category = @Translation("European Innovation Council"),
 * )
 */
class NotificationsSettingsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var UserHelper
   */
  protected $userHelper;

  /**
   * @var \Drupal\eic_user\Service\NotificationSettingsManager
   */
  protected $notificationSettingsManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $account_proxy,
    UserHelper $user_helper,
    NotificationSettingsManager $notification_settings_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $account_proxy;
    $this->userHelper = $user_helper;
    $this->notificationSettingsManager = $notification_settings_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('eic_user.helper'),
      $container->get('eic_user.notification_settings_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_user = User::load($this->currentUser->id());
    $member_profile = $this->userHelper->getUserMemberProfile($current_user);
    $menu_items = [
      [
        'link' => [
          'label' => $this->t('My information'),
          'path' => $current_user->toUrl()->toString(),
        ],
      ],
      [
        'link' => [
          'label' => $this->t('Email notifications'),
          'path' => Url::fromRoute('eic_user.my_settings', ['user' => $current_user->id()])->toString(),
        ],
        'is_active' => TRUE,
      ],
    ];

    return [
      '#theme' => 'user_notifications_settings',
      '#menu_items' => ['items' => $menu_items],
      '#items' => [
        'interest' => $this->getInterestsTab($member_profile),
        'groups' => $this->getGroupsTab($member_profile),
        'events' => $this->getEventsTab($member_profile),
        'comments' => $this->getCommentsTab($member_profile),
      ],
      '#cache' => [
        'contexts' => ['user'],
      ],
      '#attached' => [
        'drupalSettings' => [
          'translations' => [
            'unsubscribe_modal_close' => $this->t('Close'),
            'unsubscribe_modal_title' => $this->t('Unsubscribe'),
            'unsubscribe_modal_desc' => $this->t('You are about to unsubscribe from this notification type.'),
            'unsubscribe_modal_confirm' => $this->t('Confirm'),
            'unsubscribe_modal_cancel' => $this->t('Cancel'),
            'search_placeholder' => $this->t('Search'),
            'notification_name' => $this->t('Name'),
            'notification_status' => $this->t('Status'),
            'notification_unsubscribe' => $this->t('Unsubscribe'),
            'toggle_on' => $this->t('On'),
            'toggle_off' => $this->t('Off'),
            'no_results_title' => $this->t('No results'),
            'no_results_body' => $this->t('Could not find any results.'),
          ],
        ],
      ],
    ];
  }

  /**
   * @param ProfileInterface|null $profile
   *
   * @return array
   */
  private function getInterestsTab(?ProfileInterface $profile): array {
    $topics = [];
    $regions = [];
    if ($profile instanceof ProfileInterface) {
      foreach ($profile->get('field_vocab_topic_interest')->referencedEntities() as $topic) {
        $topics[]['tag'] = [
          'type' => 'link',
          'path' => $topic->toUrl(),
          'label' => $topic->label(),
        ];

        usort($topics, function ($topicA, $topicB) {
          return strcmp($topicA['tag']['label'], $topicB['tag']['label']);
        });
      }

      foreach ($profile->get('field_vocab_geo')->referencedEntities() as $region) {
        $regions[]['tag'] = [
          'type' => 'link',
          'path' => $region->toUrl(),
          'label' => $region->label(),
        ];

        usort($regions, function ($topicA, $topicB) {
          return strcmp($topicA['tag']['label'], $topicB['tag']['label']);
        });
      }
    }

    return [
      'title' => $this->t('Your interest notifications'),
      'content' => [
        '#theme' => 'notification_settings',
        '#data' => [
          'title' => $this->t('Your interest notifications'),
          'body' => $this->t('By indication thematic of geographic interests, you are automatically subscribed to a periodic notification email bringing together the latest highlighted items.'),
          'action' => $this->getEditProfileLink($profile),
          'interests' => [
            [
              'title' => $this->t('Topics'),
              'type' => 'tags',
              'is_collapsible' => FALSE,
              'grid' => TRUE,
              'items' => $topics,
            ],
            [
              'title' => $this->t('Region'),
              'type' => 'tags',
              'grid' => TRUE,
              'is_collapsible' => FALSE,
              'items' => $regions,
            ],
          ],
          'global_action' => [
            'title' => 'Interest email notifications',
            'state' => $profile instanceof ProfileInterface ? $profile->get('field_interest_notifications')->value : FALSE,
            'url' => Url::fromRoute('eic_user.toggle_notification_settings', [
              'notification_type' => NotificationTypes::INTEREST_NOTIFICATION_TYPE,
            ]),
          ],
        ],
      ],
    ];
  }

  /**
   * @return array[]
   */
  private function getEditProfileLink(?ProfileInterface $profile): array {
    static $link;
    if (empty($link)) {
      $link = [
        'link' => [
          'label' => $this->t('Edit interests'),
          'path' => $profile instanceof ProfileInterface ?
            Url::fromRoute('entity.profile.edit_form',
              ['profile' => $profile->id()]) :
            Url::fromRoute('profile.user_page.single', [
              'user' => $this->currentUser->id(),
              'profile_type' => 'member',
            ]),
        ],
      ];
    }

    return $link;
  }

  /**
   * @param \Drupal\profile\Entity\ProfileInterface|null $profile
   *
   * @return array
   */
  private function getGroupsTab(?ProfileInterface $profile): array {
    return [
      'title' => $this->t('Your group notifications'),
      'content' => [
        '#theme' => 'notification_settings',
        '#data' => [
          'title' => $this->t('Your group notifications'),
          'body' => $this->t('You receive a periodic notification email for these groups because you\'re following them.'),
          'table' => [
            'title' => $this->t('Groups'),
            'unsubscribe' => TRUE,
            'url' => Url::fromRoute('eic_user.get_notification_settings', [
              'notification_type' => NotificationTypes::GROUPS_NOTIFICATION_TYPE,
            ]),
          ],
        ],
      ],
    ];
  }

  /**
   * @param \Drupal\profile\Entity\ProfileInterface|null $profile
   *
   * @return array
   */
  private function getEventsTab(?ProfileInterface $profile): array {
    return [
      'title' => $this->t('Your event notifications'),
      'content' => [
        '#theme' => 'notification_settings',
        '#data' => [
          'title' => $this->t('Your event notifications'),
          'body' => $this->t('You receive a periodic notification email for these events because you\'re following them.'),
          'table' => [
            'title' => $this->t('Events'),
            'unsubscribe' => TRUE,
            'url' => Url::fromRoute('eic_user.get_notification_settings', [
              'notification_type' => NotificationTypes::EVENTS_NOTIFICATION_TYPE,
            ]),
          ],
        ],
      ],
    ];
  }

  /**
   * @param ProfileInterface|null $profile
   *
   * @return array
   */
  private function getCommentsTab(?ProfileInterface $profile): array {
    return [
      'title' => $this->t('Your comments notifications'),
      'content' => [
        '#theme' => 'notification_settings',
        '#data' => [
          'title' => $this->t('Your comments notifications'),
          'body' => $this->t('By indication thematic of geographic interests, you are automatically subscribed to a periodic notification email bringing together the latest highlighted items.'),
          'global_action' => [
            'title' => $this->t('Comments email notifications'),
            'state' => $profile instanceof ProfileInterface ? $profile->get('field_comments_notifications')->value : FALSE,
            'url' => Url::fromRoute('eic_user.toggle_notification_settings', [
              'notification_type' => NotificationTypes::COMMENTS_NOTIFICATION_TYPE,
            ]),
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if (!$this->currentUser->isAuthenticated()) {
      return parent::getCacheTags();
    }

    $additional_tags = [];
    $current_user = User::load($this->currentUser->id());
    $member_profile = $this->userHelper->getUserMemberProfile($current_user);
    if ($member_profile instanceof ProfileInterface) {
      $additional_tags = array_merge($member_profile->getCacheTags(), $current_user->getCacheTags());
    }

    return Cache::mergeTags(parent::getCacheTags(), $additional_tags);
  }

}
