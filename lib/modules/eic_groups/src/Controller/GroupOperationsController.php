<?php

namespace Drupal\eic_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\eic_flags\FlagType;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides group operation route controllers.
 */
class GroupOperationsController extends ControllerBase {

  /**
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Constructs a new GroupOperationsController object.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination helper.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    RedirectDestinationInterface $redirect_destination,
    RequestStack $request_stack,
    FlagServiceInterface $flagService
  ) {
    $this->redirectDestination = $redirect_destination;
    $this->requestStack = $request_stack;
    $this->flagService = $flagService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('redirect.destination'),
      $container->get('request_stack'),
      $container->get('flag')
    );
  }

  /**
   * Builds the publish group page title.
   */
  public function publishTitle(GroupInterface $group) {
    return $this->t('Publish @group-type %label',
      [
        '@group-type' => $group->bundle(),
        '%label' => $group->label(),
      ]
    );
  }

  /**
   * Publishes a given group and redirects back to the group homepage.
   */
  public function publish(GroupInterface $group) {
    $group->setPublished();
    $group->set('moderation_state', 'published');
    $group->save();

    // Default response when destination is not in the URL.
    $response = new RedirectResponse($group->toUrl()->toString());

    // Check if destination is in the URL query and if so, we create new
    // redirect response to the destination URL.
    if ($this->requestStack->getCurrentRequest()->query->has('destination')) {
      $response = new RedirectResponse($this->redirectDestination->get());
    }

    $this->messenger()->addStatus($this->t('Group published successfully!'));

    return $response->send();
  }

  /**
   * Adds/removes the flag highlight_content to/from the given group content.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   * @param \Drupal\node\NodeInterface $node
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function highlightContent(GroupInterface $group, NodeInterface $node) {
    $flag = $this->flagService->getFlagById('highlight_content');
    $existing_flag = $this->flagService->getFlagging($flag, $node);

    if ($existing_flag instanceof FlaggingInterface) {
      $action = 'unflag';
      $this->flagService->unflag($flag, $node);
    }
    else {
      $action = 'flag';
      $this->flagService->flag($flag, $node);
    }

    return new JsonResponse(['action' => $action]);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\group\Entity\GroupInterface $group
   * @param string $action
   * @param \Drupal\node\NodeInterface $node
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function likeContent(Request $request, GroupInterface $group, string $action, NodeInterface $node) {
    $flag = $this->flagService->getFlagById(FlagType::LIKE_CONTENT);
    $this->flagService->{$action}($flag, $node);

    return new JsonResponse();
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\group\Entity\GroupInterface $group
   * @param string $action
   * @param \Drupal\node\NodeInterface $node
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function likeContentStatus(Request $request, GroupInterface $group, NodeInterface $node) {
    $flag = $this->flagService->getFlagById(FlagType::LIKE_CONTENT);
    $action = $flag->isFlagged($node) ? 'unflag' : 'flag';

    return new JsonResponse(['action' => $action]);
  }

}
