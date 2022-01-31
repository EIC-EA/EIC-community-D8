<?php

namespace Drupal\eic_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class GroupMembershipInvitationController
 *
 * @package Drupal\eic_groups\Controller
 */
class GroupMembershipInvitationController extends ControllerBase {

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  private $mailManager;

  /**
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  private $groupContentEnablerManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destionation
   *   If specified, the redirect direction.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $group_content_enabler_manager
   *   The group content enabler service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
    RedirectDestinationInterface $redirect_destionation,
    MessengerInterface $messenger,
    MailManagerInterface $mail_manager,
    GroupContentEnablerManagerInterface $group_content_enabler_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
    $this->redirectDestination = $redirect_destionation;
    $this->messenger = $messenger;
    $this->mailManager = $mail_manager;
    $this->groupContentEnablerManager = $group_content_enabler_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('redirect.destination'),
      $container->get('messenger'),
      $container->get('plugin.manager.mail'),
      $container->get('plugin.manager.group_content_enabler')
    );
  }

  /**
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity.
   * 
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function resendInvite(
    GroupInterface $group,
    GroupContentInterface $group_content
  ) {
    if ($group_content->getContentPlugin()->getPluginId() !== 'group_invitation') {
      throw new \InvalidArgumentException();
    }

    $invitation_counter = $group_content->get('field_invitation_counter')->value ?? 1;
    if ($invitation_counter >= EICGroupsHelper::INVITEE_INVITATION_EMAIL_LIMIT) {
      $this->messenger->addError($this->t('Maximum amount of invitation has been reached for this user.'));
      $response = new RedirectResponse($group->toUrl()->toString());
      if ($this->requestStack->getCurrentRequest()->query->has('destination')) {
        $response = new RedirectResponse($this->redirectDestination->get());
      }

      return $response;
    }

    $from = $group_content->getEntity();
    $langcode = $from->getPreferredLangcode();
    $mail = $group_content->get('invitee_mail')->getString();
    $params = [
      'user' => $from,
      'group' => $group,
      'group_content' => $group_content,
      'existing_user' => FALSE,
    ];

    // Load plugin configuration.
    $group_plugin_collection = $this->groupContentEnablerManager->getInstalled($group->getGroupType());
    $group_invite_config = $group_plugin_collection->getConfiguration()['group_invitation'];
    $users = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['mail' => $mail]);
    if (!empty($users) && $group_invite_config['send_email_existing_users']) {
      // Check if we should send the email to an existing user.
      $params['existing_user'] = TRUE;
    }

    $this->mailManager->mail(
      'ginvite',
      'invite',
      $mail,
      $langcode,
      $params,
      NULL,
      TRUE
    );

    $group_content->set('field_invitation_counter', ++$invitation_counter);
    $group_content->save();

    $this->messenger->addMessage($this->t('Invitation has been sent again'));
    // Default response when destination is not in the URL.
    $response = new RedirectResponse($group->toUrl()->toString());
    if ($this->requestStack->getCurrentRequest()->query->has('destination')) {
      $response = new RedirectResponse($this->redirectDestination->get());
    }

    return $response;
  }

}
