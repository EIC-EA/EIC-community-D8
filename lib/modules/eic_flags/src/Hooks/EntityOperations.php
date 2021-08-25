<?php

namespace Drupal\eic_flags\Hooks;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\eic_flags\FlaggedEntitiesListBuilder;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\flag\FlagCountManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EntityOperations
 *
 * @package Drupal\eic_flags\Hooks
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * @var \Drupal\eic_flags\Service\RequestHandlerCollector
   */
  private $collector;

  /**
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  private $moderationInformation;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  private $currentRequest;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $account;

  /**
   * The Flag count manager.
   *
   * @var \Drupal\flag\FlagCountManagerInterface
   */
  private $flagCountManager;

  /**
   * EntityOperations constructor.
   *
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   * @param \Drupal\flag\FlagCountManagerInterface $flag_count_manager
   *   The Flag count manager.
   */
  public function __construct(
    RequestHandlerCollector $collector,
    ModerationInformationInterface $moderation_information,
    RouteMatchInterface $route_match,
    RequestStack $request_stack,
    AccountProxyInterface $account,
    FlagCountManagerInterface $flag_count_manager
  ) {
    $this->collector = $collector;
    $this->moderationInformation = $moderation_information;
    $this->routeMatch = $route_match;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->account = $account;
    $this->flagCountManager = $flag_count_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_flags.handler_collector'),
      $container->get('content_moderation.moderation_information'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('flag.count')
    );
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return array
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getOperations(EntityInterface $entity) {
    $is_admin_route = \Drupal::service('router.admin_context')->isAdminRoute();
    if ($is_admin_route) {
      return $this->getAdminOperations($entity);
    }

    $operations = [];
    $handlers = $this->collector->getHandlers();
    foreach ($handlers as $handler) {
      if (!$entity->access('request-' . $handler->getType())
        || !$handler->supports($entity)) {
        continue;
      }

      $type = $handler->getType();
      $operations['request_' . $type] = [
        'title' => t('Request ' . $type),
        'url' => $entity->toUrl('new-request')
          ->setRouteParameter(
            'destination',
            \Drupal::request()->getRequestUri()
          )
          ->setRouteParameter('request_type', $type),
      ];
    }

    return $operations;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return array
   */
  public function getAdminOperations(EntityInterface $entity) {
    if ($request_type = $this->routeMatch->getParameter('request_type')) {
      $handler = $this->collector->getHandlerByType($request_type);

      return $handler->getActions($entity);
    }

    $route_name = $this->routeMatch->getRouteName();
    $flag_id = $this->currentRequest->query->get('flag_id');
    $url = Url::fromRoute('eic_flags.publish_archived_content', [
      'entity_type_id' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'destination' => \Drupal::request()->getRequestUri(),
    ]);

    $is_published = $this->moderationInformation->isModeratedEntity($entity)
      ? $this->moderationInformation->isDefaultRevisionPublished($entity)
      : (bool) $entity->get('status')->value;

    if (!$is_published
      && FlaggedEntitiesListBuilder::CLOSED_REQUEST_VIEW === $route_name
      && (int) $flag_id === FlaggedEntitiesListBuilder::VIEW_ARCHIVE_FLAG_ID
      && $url->access($this->account)
    ) {
      return [
        'publish' => [
          'title' => t('Publish'),
          'url' => $url,
        ],
      ];
    }
  }

  /**
   * Provides flags count for the given entity.
   *
   * @param array $build
   *   The renderable array representing the entity content.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display holding the display options.
   * @param string $view_mode
   *   The view mode the entity is rendered in.
   */
  public function entityView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    $build['flag_counts'] = [
      '#markup' => '',
      '#items' => $this->flagCountManager->getEntityFlagCounts($entity),
    ];
  }

}
