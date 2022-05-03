<?php

namespace Drupal\eic_flags\Hooks;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\FlaggingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\Token;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_flags\Service\RequestHandlerCollector;

/**
 * Class FlagTokens.
 *
 * Implementations of token hooks.
 *
 * @package Drupal\eic_flags\Hooks
 */
class FlagTokens implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  private $tokenService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private $dateFormatter;

  /**
   * The request collector service.
   *
   * @var \Drupal\eic_flags\Service\RequestHandlerCollector
   */
  protected $requestHandlerCollector;

  /**
   * FlagTokens constructor.
   *
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $request_handler_collector
   *   The request collection service.
   */
  public function __construct(
    Token $token_service,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter,
    RequestHandlerCollector $request_handler_collector
  ) {
    $this->tokenService = $token_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->requestHandlerCollector = $request_handler_collector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('eic_flags.handler_collector')
    );
  }

  /**
   * Implements hook_token_info().
   *
   * @return array
   *   List of supported tokens.
   */
  public function tokenInfo() {
    return [
      'tokens' => [
        'flagging' => [
          'target_entity' => [
            'name' => $this->t('Target Entity'),
            'description' => $this->t('Entity the flagged is applied to'),
          ],
          'flag-type' => [
            'name' => $this->t('Flag type'),
            'description' => $this->t('Type of the flag'),
          ],
          'entity-type' => [
            'name' => $this->t('Entity type'),
            'description' => $this->t('Type of the entity'),
          ],
          'author' => [
            'name' => $this->t('Author of the flag'),
            'type' => 'user',
            'description' => $this->t('User who flagged the entity'),
          ],
          'request_timeout_date' => [
            'name' => $this->t('Request timeout date'),
            'description' => $this->t('Request timeout formatted as date'),
          ],
        ],
      ],
    ];
  }

  /**
   * Implements hook_tokens().
   *
   * @return array
   *   Array of replacements.
   */
  public function tokens(
    $type,
    $tokens,
    array $data,
    array $options,
    BubbleableMetadata $bubbleable_metadata
  ) {
    $replacements = [];
    $flag = NULL;
    if ($type == 'flagging' && isset($data['flagging'])) {
      /** @var \Drupal\flag\FlaggingInterface $flag */
      $flag = $data['flagging'];
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'target_entity':
            $target_entity = $this->entityTypeManager
              ->getStorage($flag->getFlaggableType())
              ->load($flag->getFlaggableId());

            $bubbleable_metadata->addCacheableDependency($target_entity);
            $replacements[$original] = $target_entity->label();
            break;

          case 'date':
            $replacements[$original] = $this->dateFormatter
              ->format($flag->get('created')->value, 'medium');
            break;

          case 'flag-type':
            $replacements[$original] = $flag->get('flag_id')->entity->id();
            break;

          case 'entity-type':
            $replacements[$original] = $flag->get('entity_type')->value;
            break;

          case 'author':
            $account = $flag->getOwner() ? $flag->getOwner() : NULL;
            $bubbleable_metadata->addCacheableDependency($account);
            $replacements[$original] = $account->label();
            break;

          case 'request_timeout_date':
            $handler = $this->requestHandlerCollector->getHandlerByType(RequestTypes::TRANSFER_OWNERSHIP);

            $timeout = $flag->get('field_request_timeout')->value * 86400;
            $timeout += $flag->get('created')->value;
            $timeout_formatted = $this->t('no expiration');

            // If request has expired, we show the expiration date.
            if ($handler->hasExpiration($flag)) {
              $timeout_formatted = $this->dateFormatter->format($timeout, 'eu_short_date_hour');
            }

            $replacements[$original] = $timeout_formatted;
            break;

        }
      }
    }

    $author_tokens = $this->tokenService->findWithPrefix($tokens, 'author');
    if ($flag instanceof FlaggingInterface && $author_tokens) {
      $replacements += $this->tokenService->generate(
        'user',
        $author_tokens,
        ['user' => $flag->getOwner()],
        $options,
        $bubbleable_metadata
      );
    }

    $target_entity_token = $this->tokenService->findWithPrefix(
      $tokens,
      'target_entity'
    );
    if ($flag instanceof FlaggingInterface && $target_entity_token) {
      $target_entity = $this->entityTypeManager
        ->getStorage($flag->getFlaggableType())
        ->load($flag->getFlaggableId());
      $class_name = strtolower(
        (new \ReflectionClass($target_entity))->getShortName()
      );

      $replacements += $this->tokenService->generate(
        $class_name,
        $target_entity_token,
        [$class_name => $target_entity],
        $options,
        $bubbleable_metadata
      );
    }

    return $replacements;
  }

}
