<?php

namespace Drupal\eic_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\masquerade\Masquerade;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with the current user's full name & profile picture or a "Log in" link.
 *
 * @Block(
 *   id = "eic_account_header_block",
 *   admin_label = @Translation("EIC Account Header Block"),
 *   category = @Translation("European Innovation Council"),
 * )
 */
class AccountHeaderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var AccountProxyInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\masquerade\Masquerade
   */
  private $masquerade;

  /**
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  private $currentRequest;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;


  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   * @param \Drupal\Core\Http\RequestStack $request_stack
   * @param \Drupal\masquerade\Masquerade $masquerade
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $account_proxy,
    RequestStack $request_stack,
    RouteMatchInterface $current_route_match,
    Masquerade $masquerade
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $account_proxy;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->currentRouteMatch = $current_route_match;
    $this->masquerade = $masquerade;
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
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('masquerade')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->currentUser->isAnonymous()) {
      // If there is a defined destination, preserve it, otherwise use the
      // current page for the destination.
      $destination = NULL;
      if (!empty($this->currentRequest->get('destination'))) {
        $destination = $this->currentRequest->get('destination');
      }
      else {
        if ($this->currentRouteMatch->getRouteName() !== 'eic_user_login.member_access') {
          $destination = $this->currentRequest->getRequestUri();
        }
      }

      $url = Url::fromRoute('eic_user_login.member_access');
      if ($destination) {
        $url->setOption('query', ['destination' => $destination]);
      }

      $build['#login']['link'] = [
        'label' => t('Member access'),
        'path' => $url,
      ];
    }
    else {
      $account = User::load($this->currentUser->id());
      $user = eic_community_get_teaser_user_display($account);
      unset($user['path']);

      $user['actions'] = [
        [
          'link' => [
            'label' => t('My profile'),
            'path' => $account->toUrl()->toString(),
          ],
        ],
        [
          'link' => [
            'label' => t('My settings'),
            'path' => Url::fromRoute('eic_user.my_settings', ['user' => $account->id()])->toString(),
          ],
        ],
        [
          'link' => [
            'label' => t('My activity'),
            'path' => Url::fromRoute('eic_user.user.activity', ['user' => $account->id()])->toString(),
          ],
        ],
      ];

      // Adds unmasquerade link if the user is masquerading.
      if ($this->masquerade->isMasquerading()) {
        $user['actions'][] = [
          'link' => [
            'label' => t('Unmasquerade'),
            'path' => Url::fromRoute('masquerade.unmasquerade')->toString(),
          ],
        ];
      }

      // Adds logout link to the end of the dropdown.
      $user['actions'][] = [
        'link' => [
          'label' => t('Log out'),
          'path' => Url::fromRoute('user.logout'),
        ],
      ];

      $build['#user'] = $user;
    }

    return [
        '#theme' => 'account_header_block',
        '#cache' => [
          'contexts' => ['user'],
        ],
      ] + $build;
  }

}
