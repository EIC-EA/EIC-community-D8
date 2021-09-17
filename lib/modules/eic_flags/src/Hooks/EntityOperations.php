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
use Drupal\flag\FlagServiceInterface;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EntityOperations.
 *
 * Implementations of entity hooks.
 *
 * @package Drupal\eic_flags\Hooks
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * The EIC flag request handler collector.
   *
   * @var \Drupal\eic_flags\Service\RequestHandlerCollector
   */
  private $collector;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  private $moderationInformation;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  private $currentRequest;

  /**
   * The current user account.
   *
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
   * The Flag sevice.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  private $flagService;

  /**
   * EntityOperations constructor.
   *
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   *   The EIC flag request handler collector.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   * @param \Drupal\flag\FlagCountManagerInterface $flag_count_manager
   *   The Flag count manager.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The Flag sevice.
   */
  public function __construct(
    RequestHandlerCollector $collector,
    ModerationInformationInterface $moderation_information,
    RouteMatchInterface $route_match,
    RequestStack $request_stack,
    AccountProxyInterface $account,
    FlagCountManagerInterface $flag_count_manager,
    FlagServiceInterface $flag_service
  ) {
    $this->collector = $collector;
    $this->moderationInformation = $moderation_information;
    $this->routeMatch = $route_match;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->account = $account;
    $this->flagCountManager = $flag_count_manager;
    $this->flagService = $flag_service;
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
      $container->get('flag.count'),
      $container->get('flag'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Gets flag operation links for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return array
   *   Array of operation links.
   *
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
   * Gets flag admin operation links.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return array
   *   Array of operation links.
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

    if ($this->moderationInformation->isModeratedEntity($entity)) {
      $is_published = $this->moderationInformation->isDefaultRevisionPublished($entity);
    }
    else {
      $is_published = $entity->hasField('status') ? (bool) $entity->get('status')->value : FALSE;
    }

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

  /**
   * Implements hook_entity_insert().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function entityInsert(EntityInterface $entity) {
    $this->followEntityOnCreation($entity);
  }

  /**
   * Implements hook_entity_insert().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function entityUpdate(EntityInterface $entity) {
    switch ($entity->getEntityTypeId()) {
      case 'profile':
        $this->followTopicsOnUserProfileUpdate($entity);
        break;

    }
  }

  /**
   * Triggers "follow" flag on an entity after its creation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function followEntityOnCreation(EntityInterface $entity) {
    $flag_entity = FALSE;
    $flag_type = FALSE;

    switch ($entity->getEntityTypeId()) {
      case 'comment':
        $flag_entity = $entity->getCommentedEntity();

        // Array of node types that don't implement follow flag.
        $excluded_node_types = [];

        // If Commented node does not implement flag, we do nothing.
        if (in_array($flag_entity->bundle(), $excluded_node_types)) {
          break;
        }

        // @todo Add missing node follow flag_type variable after implementing
        // the flag for nodes.
        break;

      case 'group_content':
        // We skip group_content entities that are not group_membership.
        if ($entity->getGroupContentType()->getContentPluginId() !== 'group_membership') {
          break;
        }

        // Get the group entity to be flagged later.
        $flag_entity = $entity->getGroup();
        $flag_type = 'follow_group';
        break;

      case 'node':
        // Array of node types that don't implement follow flag.
        $excluded_node_types = [];

        // If node does not implement flag, we do nothing.
        if (in_array($entity->bundle(), $excluded_node_types)) {
          break;
        }

        $flag_entity = $entity;

        // @todo Add missing node follow flag_type variable after implementing
        // the flag for nodes.
        break;

    }

    if (!$flag_entity || !$flag_type) {
      return;
    }

    $flag = $this->flagService->getFlagById($flag_type);
    $this->flagService->flag($flag, $flag_entity);
  }

  /**
   * Triggers "follow" flag on topic term after user profile update.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile object.
   */
  public function followTopicsOnUserProfileUpdate(ProfileInterface $profile) {
    if ($profile->bundle() !== 'member') {
      return;
    }

    $topics = [];

    $new_topics = $profile->get('field_vocab_topic_interest')->referencedEntities();
    foreach ($new_topics as $topic) {
      $topics[$topic->id()] = $topic;
    }

    $old_topics = $profile->original->get('field_vocab_topic_interest')->referencedEntities();
    foreach ($old_topics as $topic) {
      if (!isset($topics[$topic->id()])) {
        // @todo Unflag topic
        continue;
      }

      // @todo Flag topic
    }

  }

}
