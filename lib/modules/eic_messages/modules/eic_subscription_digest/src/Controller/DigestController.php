<?php

namespace Drupal\eic_subscription_digest\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_subscription_digest\Constants\DigestTypes;
use Drupal\eic_subscription_digest\Service\DigestManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DigestController
 *
 * @package Drupal\eic_subscription_digest\Controller
 */
class DigestController extends ControllerBase {

  /**
   * @var \Drupal\eic_subscription_digest\Service\DigestManager
   */
  private $manager;

  /**
   * @param \Drupal\eic_subscription_digest\Service\DigestManager $manager
   */
  public function __construct(DigestManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('eic_subscription_digest.manager'));
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateStatus(Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE);
    if (!isset($body['value'])) {
      throw new \InvalidArgumentException('Invalid request');
    }

    $value = filter_var($body['value'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if (!is_bool($value)) {
      throw new \InvalidArgumentException('Invalid request');
    }

    return new JsonResponse([
      'value' => $this->manager->setDigestStatus($value),
    ]);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateFrequency(Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE);
    if (!isset($body['value'])) {
      throw new \InvalidArgumentException('Invalid request');
    }

    $frequency = $body['value'];
    if (!in_array($frequency, DigestTypes::getAll())) {
      throw new \InvalidArgumentException('Invalid request');
    }

    return new JsonResponse([
      'value' => $this->manager->setDigestFrequency($frequency),
    ]);
  }

}
