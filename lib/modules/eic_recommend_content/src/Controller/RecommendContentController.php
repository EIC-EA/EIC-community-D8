<?php

namespace Drupal\eic_recommend_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_recommend_content\Services\RecommendContentManager;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for EIC Recommend Content routes.
 */
class RecommendContentController extends ControllerBase {

  /**
   * The OEC Group flex helper service.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * The Recommend content manager service.
   *
   * @var \Drupal\eic_recommend_content\Services\RecommendContentManager
   */
  protected $recommendContentManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  private $currentRequest;

  /**
   * Constructs a new RecommendGroupAccessCheck object.
   *
   * @param \Drupal\oec_group_flex\OECGroupFlexHelper $oec_group_flex_helper
   *   The oec_group_flex.helper service.
   * @param \Drupal\eic_recommend_content\Services\RecommendContentManager $recommend_content_manager
   *   The Recommend content manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(
    OECGroupFlexHelper $oec_group_flex_helper,
    RecommendContentManager $recommend_content_manager,
    RequestStack $request_stack
  ) {
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
    $this->recommendContentManager = $recommend_content_manager;
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oec_group_flex.helper'),
      $container->get('eic_recommend_content.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * Recommends a content.
   *
   * @param string $entity_type
   *   The entity type machine name.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function recommend(string $entity_type, int $entity_id) {
    $entity = $this->entityTypeManager()->getStorage($entity_type)
      ->load($entity_id);

    // If entity doesn't exist, we do nothing.
    if (!$entity) {
      return new JsonResponse([
        'status' => FALSE,
        'message' => $this->t('The content does not exist or has been deleted. Therefore, it cannot be recommended.'),
      ]);
    }

    $content = json_decode($this->currentRequest->getContent(), TRUE);

    // Working example.
    // @todo Remove after react implementation.
    $content['users'] = [2, 3, 4, 5];
    $content['external_emails'] = "user1@example.com\r\nuser2@example.com\r\nuser3@example.com";
    $content['message'] = 'Test recommendation for users.';

    if (
      !isset($content['users']) &&
      !isset($content['external_emails'])
    ) {
      throw new \InvalidArgumentException();
    }

    if (!isset($content['message'])) {
      throw new \InvalidArgumentException();
    }

    $users = [];
    if (
      isset($content['users']) &&
      is_array($content['users'])
    ) {
      foreach ($content['users'] as $user) {
        if (!is_int($user)) {
          continue;
        }

        /** @var \Drupal\user\UserInterface $loaded_user */
        $loaded_user = $this->entityTypeManager()->getStorage('user')
          ->load($user);
        if (!$loaded_user || !$loaded_user->getEmail()) {
          continue;
        }

        $users[] = $loaded_user;
      }
    }

    $external_emails = [];
    if (
      isset($content['external_emails']) &&
      is_string($content['external_emails'])
    ) {
      $external_emails = array_map('trim', array_unique(explode("\r\n", trim($content['external_emails']))));
    }

    try {
      $this->recommendContentManager->recommend(
        $entity,
        $users,
        $external_emails,
        $content['message']
      );
    }
    catch (\Exception $exception) {
      return new JsonResponse([
        'status' => FALSE,
        'message' => $exception->getMessage(),
      ]);
    }

    return new JsonResponse([
      'status' => TRUE,
      'message' => $this->t('The content has been recommended.'),
    ]);
  }

}
