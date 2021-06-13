<?php

namespace Drupal\eic_flags\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\FlaggingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\Token;

/**
 * Class FlagTokens
 *
 * @package Drupal\eic_flags\Hooks
 */
class FlagTokens implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  private $tokenService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * FlagTokens constructor.
   *
   * @param \Drupal\Core\Utility\Token $token_service
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(Token $token_service, EntityTypeManagerInterface $entity_type_manager) {
    $this->tokenService = $token_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Implements hook_token_info().
   */
  public function tokenInfo() {
    return [
      'tokens' => [
        'flagging' => [
          'target_entity' => [
            'name' => $this->t('Target Entity'),
            'description' => $this->t('Label of the flagged entity'),
          ],
          'author' => [
            'name' => $this->t('Author of the flag'),
            'type' => 'user',
            'description' => $this->t('User who flagged the entity'),
          ],
        ],
      ],
    ];
  }

  /**
   * Implements hook_tokens().
   */
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $replacements = [];
    $flag = NULL;
    if ($type == 'flagging' && isset($data['flagging'])) {
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
          case 'author':
            $account = $flag->getOwner() ? $flag->getOwner() : NULL;
            $bubbleable_metadata->addCacheableDependency($account);
            $replacements[$original] = $account->label();
            break;
        }
      }
    }

    $author_tokens = $this->tokenService->findWithPrefix($tokens, 'author');
    if ($flag instanceof FlaggingInterface && $author_tokens) {
      $replacements += $this->tokenService->generate(
        'user', $author_tokens,
        ['user' => $flag->getOwner()],
        $options,
        $bubbleable_metadata
      );
    }

    $target_entity_token = $this->tokenService->findWithPrefix($tokens, 'target_entity');
    if ($flag instanceof FlaggingInterface && $target_entity_token) {
      $target_entity = $this->entityTypeManager
        ->getStorage($flag->getFlaggableType())
        ->load($flag->getFlaggableId());
      $class_name = strtolower((new \ReflectionClass($target_entity))->getShortName());

      $replacements += $this->tokenService->generate(
        $class_name, $target_entity_token,
        [$class_name => $target_entity],
        $options,
        $bubbleable_metadata
      );
    }

    return $replacements;
  }

}
