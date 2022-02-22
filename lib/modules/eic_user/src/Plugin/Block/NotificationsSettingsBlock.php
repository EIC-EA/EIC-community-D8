<?php

namespace Drupal\eic_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\eic_user\UserHelper;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NotificationsSettingsBlock
 * @package Drupal\eic_user\Plugin\Block
 *
 * @Block(
 *   id = "eic_user_notifications_settings",
 *   admin_label = @Translation("EIC User Notifications Settings"),
 *   category = @Translation("European Innovation Council"),
 * )
 */
class NotificationsSettingsBlock extends BlockBase implements ContainerFactoryPluginInterface
{
  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var UserHelper
   */
  protected $userHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account_proxy, UserHelper $user_helper)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $account_proxy;
    $this->userHelper = $user_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('eic_user.helper')
    );
  }

  public function build()
  {
    $currentUser = User::load($this->currentUser->id());
    $member_profile = $this->userHelper->getUserMemberProfile($currentUser);
    $menu_items = [
      [
        'link' => [
          'label' => $this->t('My informations'),
          'path' => $currentUser->toUrl()->toString()
        ]
      ],
      [
        'link' => [
          'label' => $this->t('Email notifications'),
          'path' => Url::fromRoute('eic_user.my_settings')->toString()
        ],
        'is_active' => TRUE,
      ]
    ];

    return [
      '#theme' => 'user_notifications_settings',
      '#menu_items' => ['items' => $menu_items],
      '#items' => [
        'interest' => $this->getInterestsTab($member_profile),
        'groups' => [
          'title' => $this->t('Your group notifications'),
          'content' => 'TBD'
        ],
        'events' => [
          'title' => $this->t('Your events notifications'),
          'content' => 'TBD'
        ],
        'comments' => [
          'title' => $this->t('Your comments notifications'),
          'content' => 'TBD'
        ],
      ],
    ];
  }

  /**
   * @param ProfileInterface|null $profile
   * @return array
   */
  private function getInterestsTab(?ProfileInterface $profile): array
  {
    $topics = [];
    $regions = [];
    if ($profile instanceof ProfileInterface) {
      foreach ($profile->get('field_vocab_topic_interest')->referencedEntities() as $topic) {
        $topics[]['tag'] = [
          'type' => 'link',
          'path' => $topic->toUrl(),
          'label' => $topic->label(),
        ];
      }

      foreach ($profile->get('field_vocab_geo')->referencedEntities() as $region) {
        $regions[]['tag'] = [
          'type' => 'link',
          'path' => $region->toUrl(),
          'label' => $region->label(),
        ];
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
            'state' => true
          ]
        ]
      ]
    ];
  }

  /**
   * @return array[]
   */
  private function getEditProfileLink(?ProfileInterface $profile): array
  {
    static $link;
    if (empty($link)) {
      $link = [
        'link' => [
          'label' => $this->t('Edit interests'),
          'path' => $profile instanceof ProfileInterface ?
            Url::fromRoute('entity.profile.edit_form', ['profile' => $profile->id()]) :
            Url::fromRoute('profile.user_page.single', ['user' => $this->currentUser->id(), 'profile_type' => 'member'])
        ]
      ];
    }

    return $link;
  }
}
