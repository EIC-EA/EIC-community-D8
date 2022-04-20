<?php

namespace Drupal\eic_groups;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Token;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\pathauto\AliasCleanerInterface;
use Drupal\pathauto\AliasStorageHelperInterface;
use Drupal\pathauto\AliasTypeManager;
use Drupal\pathauto\AliasUniquifierInterface;
use Drupal\pathauto\MessengerInterface;
use Drupal\pathauto\PathautoGenerator as PathautoGeneratorBase;
use Drupal\pathauto\PathautoGeneratorInterface;

/**
 * Provides methods for generating path aliases.
 */
class PathautoGenerator extends PathautoGeneratorBase {

  /**
   * The pathauto generator inner service.
   *
   * @var \Drupal\pathauto\PathautoGenerator
   */
  protected $pathautoGenerator;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Creates a new Pathauto manager.
   *
   * @param \Drupal\pathauto\PathautoGenerator $pathauto_generator_inner_service
   *   The pathauto generator inner service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route
   *   The current route match service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility.
   * @param \Drupal\pathauto\AliasCleanerInterface $alias_cleaner
   *   The alias cleaner.
   * @param \Drupal\pathauto\AliasStorageHelperInterface $alias_storage_helper
   *   The alias storage helper.
   * @param \Drupal\pathauto\AliasUniquifierInterface $alias_uniquifier
   *   The alias uniquifier.
   * @param \Drupal\pathauto\MessengerInterface $pathauto_messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\token\TokenEntityMapperInterface $token_entity_mapper
   *   The token entity mapper.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\pathauto\AliasTypeManager $alias_type_manager
   *   Manages pathauto alias type plugins.
   */
  public function __construct(
    PathautoGeneratorBase $pathauto_generator_inner_service,
    RouteMatchInterface $current_route,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    Token $token,
    AliasCleanerInterface $alias_cleaner,
    AliasStorageHelperInterface $alias_storage_helper,
    AliasUniquifierInterface $alias_uniquifier,
    MessengerInterface $pathauto_messenger,
    TranslationInterface $string_translation,
    TokenEntityMapperInterface $token_entity_mapper,
    EntityTypeManagerInterface $entity_type_manager,
    AliasTypeManager $alias_type_manager = NULL
  ) {
    parent::__construct(
      $config_factory,
      $module_handler,
      $token,
      $alias_cleaner,
      $alias_storage_helper,
      $alias_uniquifier,
      $pathauto_messenger,
      $string_translation,
      $token_entity_mapper,
      $entity_type_manager,
      $alias_type_manager
    );
    $this->pathautoGenerator = $pathauto_generator_inner_service;
    $this->routeMatch = $current_route;
  }

  /**
   * {@inheritdoc}
   */
  public function createEntityAlias(EntityInterface $entity, $op) {
    $pathauto_config = $this->configFactory->get('pathauto.settings');

    // When a group content node is created, at that point there is no
    // relationship with a group until the group content entity is also
    // created. For that reason, the drupal status message shows a wrong URL to
    // the node page and therefore, we need to skip the URL generation since it
    // will be re-generated when the group content entity is created.
    if (
      $this->routeMatch->getRouteName() === 'entity.group_content.create_form' &&
      $op === 'insert' &&
      $pathauto_config->get('update_action') === PathautoGeneratorInterface::UPDATE_ACTION_NO_NEW &&
      $entity instanceof NodeInterface &&
      $entity->hasLinkTemplate('canonical') &&
      $entity->hasField('path') &&
      $entity->getFieldDefinition('path')->getType() == 'path'
    ) {
      return NULL;
    }

    // If we are generating an URL for a group content node, we need to reset
    // the operation string so the parent class can continue the process.
    if ($op === 'insert_group_content_node') {
      $op = 'insert';
    }

    return parent::createEntityAlias($entity, $op);
  }

  /**
   * Magic method to return any method call inside the inner service.
   */
  public function __call($method, $args) {
    return call_user_func_array(
      [$this->pathautoGenerator, $method],
      $args
    );
  }

}
