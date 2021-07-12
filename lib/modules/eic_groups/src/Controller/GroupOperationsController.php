<?php

namespace Drupal\eic_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
   * Constructs a new GroupOperationsController object.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination helper.
   */
  public function __construct(RedirectDestinationInterface $redirect_destination) {
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('redirect.destination')
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

    // Create new reponse when destination is in the URL.
    if ($destination = $this->redirectDestination->get()) {
      $response = new RedirectResponse($destination);
    }

    $this->messenger()->addStatus($this->t('Group published successfully!'));

    return $response->send();
  }

}
