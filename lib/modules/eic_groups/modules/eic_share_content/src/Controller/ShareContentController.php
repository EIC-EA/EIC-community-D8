<?php

namespace Drupal\eic_share_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_share_content\Service\ShareManager;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ShareContentController
 *
 * @package Drupal\eic_share_content\Controller
 */
class ShareContentController extends ControllerBase {

  /**
   * @var \Drupal\eic_share_content\Service\ShareManager
   */
  private $shareManager;

  /**
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  private $currentRequest;

  /**
   * @param \Drupal\eic_share_content\Service\ShareManager $share_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   */
  public function __construct(
    ShareManager $share_manager,
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
    MessengerInterface $messenger,
    AccountProxyInterface $account
  ) {
    $this->shareManager = $share_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->messenger = $messenger;
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_share_content.share_manager'),
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('messenger'),
      $container->get('current_user')
    );
  }

  /**
   * @param \Drupal\group\Entity\GroupInterface $group
   * @param \Drupal\node\NodeInterface $node
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function share(
    GroupInterface $group,
    NodeInterface $node
  ) {
    $content = json_decode($this->currentRequest->getContent(), TRUE);
    if (!isset($content['group']) || !isset($content['message'])) {
      throw new \InvalidArgumentException();
    }

    $target_group = $this->entityTypeManager
      ->getStorage('group')
      ->load($content['group']);
    if (!$target_group instanceof GroupInterface) {
      throw new \InvalidArgumentException();
    }

    try {
      $this->shareManager->share(
        $group,
        $target_group,
        $node,
        $content['message']
      );
    } catch (\Exception $exception) {
      return new JsonResponse([
        'status' => FALSE,
        'message' => $exception->getMessage(),
      ]);
    }

    return new JsonResponse([
      'status' => TRUE,
      'message' => $this->t('The content has been shared'),
    ]);
  }

  /**
   * @param \Drupal\group\Entity\GroupInterface $group
   * @param \Drupal\user\UserInterface $user
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getGroups(GroupInterface $group, NodeInterface $node, UserInterface $user): JsonResponse {
    if ($user->id() !== $this->currentUser->id()) {
      throw new InvalidArgumentException();
    }

    $groups = $this->shareManager->getShareableTargetGroupsForUser($this->currentUser, $group, $node);

    // Sort groups alphabetically.
    $sorted_groups = [];
    foreach ($groups as $group) {
      $sorted_groups[$group->label()] = $group;
    }
    ksort($sorted_groups);

    $formatted_groups = [];
    foreach ($sorted_groups as $group) {
      $formatted_groups[$group->getGroupType()->label()][] = [
        'id' => $group->id(),
        'label' => $group->label(),
      ];
    }

    // Also sort on group type.
    ksort($formatted_groups);

    return new JsonResponse($formatted_groups);
  }

}
