<?php

namespace Drupal\eic_statistics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_statistics\StatisticsHelper;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class EntityStatisticsController
 *
 * @package Drupal\eic_statistics\Controller
 */
class EntityStatisticsController extends ControllerBase {

  private StatisticsHelper $statisticsHelper;

  /**
   * @param \Drupal\eic_statistics\StatisticsHelper $statistics_helper
   */
  public function __construct(StatisticsHelper $statistics_helper) {
    $this->statisticsHelper = $statistics_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_statistics.helper')
    );
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatistics(Request $request): JsonResponse {
    if (!$request->query->has('bundle') || !$request->query->has('entityId')) {
      throw new NotFoundHttpException();
    }

    $ids = $this->entityTypeManager()->getStorage('node')
      ->getQuery()
      ->condition('type', $request->query->get('bundle'))
      ->condition('nid', (int) $request->query->get('entityId'))
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return new JsonResponse([]);
    }

    $node = Node::load(reset($ids));
    if (!$node instanceof NodeInterface) {
      return new JsonResponse([]);
    }

    $statistics = $this->statisticsHelper->getEntityStatistics($node);
    return new JsonResponse($statistics);
  }

}
