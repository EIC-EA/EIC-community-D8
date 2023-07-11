<?php

namespace Drupal\eic_groups\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\ginvite\GroupInvitation;
use Drupal\ginvite\GroupInvitationLoader;
use Drupal\ginvite\EventSubscriber\GinviteSubscriber as GinviteSubscriberBase;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Decorates Ginvite module event subscriber.
 *
 * @package Drupal\eic_groups\EventSubscriber
 */
class GinviteSubscriber extends GinviteSubscriberBase {

  /**
   * The group invite event subscriber.
   *
   * @var \Drupal\ginvite\EventSubscriber\GinviteSubscriber
   */
  protected $ginviteSubscriber;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    GinviteSubscriberBase $ginvite_subscriber_inner_service,
    GroupInvitationLoader $invitation_loader,
    AccountInterface $current_user,
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    parent::__construct(
      $invitation_loader,
      $current_user,
      $messenger,
      $logger_factory
    );
    $this->ginviteSubscriber = $ginvite_subscriber_inner_service;
  }

  /**
   * {@inheritdoc}
   */
  public function notifyAboutPendingInvitations(GetResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    // Exclude routes where this info is redundant or will generate a
    // misleading extra message on the next request.
    $route_exclusions = [
      'view.my_invitations.page_1',
      'ginvite.invitation.accept',
      'ginvite.invitation.decline',
      'image.style_private',
    ];
    $route = $event->getRequest()->get('_route');

    /** @var \Drupal\ginvite\GroupInvitation[] $invitations */
    $invitations = $this->groupInvitationLoader->loadByUser();

    //Do not system notify when the group is archived.
    $disallowed_states = [DefaultContentModerationStates::ARCHIVED_STATE];
    $invitations = array_filter(
      $invitations,
      fn(GroupInvitation $groupInvitation) => !in_array(
        $groupInvitation->getGroup()->get('moderation_state')->value,
        $disallowed_states
      )
    );

    // @todo Doing this should already improve some performance however, we
    // should create a function to query the invitations and limit the results
    // to 1. This will avoid querying the whole table when we just want to know
    // if the user has at least 1 pending invitation. Also, it would probably
    // worth to create a separated block to show the information about pending
    // invitations, to avoid strange behavior such as opening a new tab and
    // navigate through the platform before clicking on accept invitation in
    // the original tab.
    if (
      !empty($route) &&
      !in_array($route, $route_exclusions) &&
      $invitations
    ) {
      $destination = Url::fromRoute(
        'view.my_invitations.page_1',
        ['user' => $this->currentUser->id()]
      )->toString();
      $replace = ['@url' => $destination];
      $message = $this->t(
        'You have pending group invitations. <a href="@url">Visit your profile</a> to see them.',
        $replace
      );
      $this->messenger->addMessage($message, 'warning', FALSE);
    }
  }

  /**
   * Magic method to return any method call inside the inner service.
   */
  public function __call($method, $args) {
    return call_user_func_array(
      [$this->ginviteSubscriber, $method],
      $args
    );
  }

}
