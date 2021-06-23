<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\Component\Utility\Xss;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\token\TokenEntityMapperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MessageTokens
 *
 * @package Drupal\eic_messages\Hooks
 */
class MessageTokens implements ContainerInjectionInterface {

  use StringTranslationTrait;

  const RENDERED_CONTENT_FIELD = 'field_rendered_content';

  /**
   * @var \Drupal\Core\Utility\Token
   */
  private $tokenService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * @var \Drupal\token\TokenEntityMapperInterface
   */
  private $tokenEntityMapper;

  /**
   * MessageTokens constructor.
   *
   * @param \Drupal\Core\Utility\Token $token_service
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   */
  public function __construct(
    Token $token_service,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    TokenEntityMapperInterface $token_entity_mapper
  ) {
    $this->tokenService = $token_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->tokenEntityMapper = $token_entity_mapper;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('token.entity_mapper')
    );
  }

  /**
   * Implements hook_token_info_alter().
   */
  public function tokenInfoAlter(&$info) {
    $entity_definitions = $this->entityTypeManager->getDefinitions();
    foreach ($entity_definitions as $entity_type_id => $entity_type) {
      if (!$entity_type->entityClassImplements(ContentEntityInterface::class)) {
        continue;
      }

      $fields = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
      // Filter fields to keep only those we support
      $supported_field = array_filter($fields, function ($field) {
        return $field === self::RENDERED_CONTENT_FIELD;
      }, ARRAY_FILTER_USE_KEY);

      if (empty($supported_field)) {
        continue;
      }

      $token_type = $this->tokenEntityMapper->getTokenTypeForEntityType($entity_type_id);
      if (empty($token_type) || !isset($info['types'][$token_type])) {
        continue;
      }

      $info['tokens'][$token_type]['raw-value'] = [
        'name' => $this->t('Raw value'),
        'description' => $this->t('Returns a safe markup for text fields which will not be escaped. Usage: [raw-value:FIELD_NAME]'),
        'module' => 'eic_messages',
        'type' => 'entity',
      ];
    }
  }

  /**
   * Implements hook_tokens().
   */
  public function tokens(
    $type,
    $tokens,
    array $data,
    array $options,
    BubbleableMetadata $bubbleable_metadata
  ) {
    $replacements = [];
    if ('entity' !== $type || empty($data['entity'] || empty($data['token_type']))) {
      return $replacements;
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $data['entity'];
    foreach ($tokens as $name => $original) {
      if (!preg_match('/^raw-value:([a-z_]+)/', $name, $matches)) {
        continue;
      }

      $field = $matches[1];
      if ($field !== self::RENDERED_CONTENT_FIELD) {
        continue;
      }

      $replacements[$original] = Markup::create(Xss::filter($entity->get($field)->value));
    }

    return $replacements;
  }

}
