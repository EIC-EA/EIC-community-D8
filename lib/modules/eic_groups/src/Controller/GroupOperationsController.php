<?php

namespace Drupal\eic_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
   * Constructs a new GroupOperationsController object.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination helper.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    RedirectDestinationInterface $redirect_destination,
    RequestStack $request_stack
  ) {
    $this->redirectDestination = $redirect_destination;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('redirect.destination'),
      $container->get('request_stack')
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
    $group->save(TRUE);

    // Default response when destination is not in the URL.
    $response = new RedirectResponse($group->toUrl()->toString());

    // Check if destination is in the URL query and if so, we create new
    // redirect reponse to the destination URL.
    if ($this->requestStack->getCurrentRequest()->query->has('destination')) {
      $response = new RedirectResponse($this->redirectDestination->get());
    }

    $this->messenger()->addStatus($this->t('Group published successfully!'));

    return $response->send();
  }

}
