<?php

namespace Drupal\eic_content\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_moderation\Constants\EICContentModeration;
use Drupal\statistics\NodeStatisticsDatabaseStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations of entity hooks.
 */
class EntityOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The Entity file download count service.
   *
   * @var \Drupal\statistics\NodeStatisticsDatabaseStorage
   */
  protected $nodeStatisticsDatabaseStorage;

  protected MessengerInterface $messenger;

  /**
   * Constructs a EntityOperation object.
   *
   * @param \Drupal\statistics\NodeStatisticsDatabaseStorage $node_statistics_db_storage
   *   The Entity file download count service.
   */
  public function __construct(NodeStatisticsDatabaseStorage $node_statistics_db_storage, MessengerInterface $messenger) {
    $this->nodeStatisticsDatabaseStorage = $node_statistics_db_storage;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('statistics.storage.node'),
      $container->get('messenger')
    );
  }

  /**
   * Acts on hook_node_view() for node entities.
   *
   * @param array $build
   *   The renderable array representing the entity content.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node entity object.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display holding the display options.
   * @param string $view_mode
   *   The view mode the entity is rendered in.
   */
  public function nodeView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    $page_views = 0;
    if ($node_views = $this->nodeStatisticsDatabaseStorage->fetchView($entity->id())) {
      $page_views = $node_views->getTotalCount();
    }
    $state_map = [
      EICContentModeration::STATE_DRAFT => $this->t('Draft', [], ['context' => 'eic_content'])->render(),
      EICContentModeration::STATE_WAITING_APPROVAL => $this->t('Waiting for approval', [], ['context' => 'eic_content'])->render(),
      EICContentModeration::STATE_NEEDS_REVIEW => $this->t('Needs review', [], ['context' => 'eic_content'])->render(),
      EICContentModeration::STATE_PUBLISHED => $this->t('Published', [], ['context' => 'eic_content'])->render(),
    ];
    if ($view_mode === 'full' && in_array($entity->get('moderation_state')->value, array_keys($state_map))) {
      if ($entity->get('moderation_state')->value === EICContentModeration::STATE_PUBLISHED ) {
        $this->messenger->addStatus($this->t('Node state is: @state', ['@state' => $state_map[$entity->get('moderation_state')->value]]), TRUE);
      }
      else {
        $this->messenger->addWarning($this->t('Node state is: @state', ['@state' => $state_map[$entity->get('moderation_state')->value]]), TRUE);
      }

    }
    $build['page_views'] = [
      '#markup' => '',
      '#value' => $page_views,
    ];
  }

}
