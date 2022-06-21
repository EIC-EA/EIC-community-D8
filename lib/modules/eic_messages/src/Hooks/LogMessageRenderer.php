<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_messages\MessageTemplateTypes;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LogMessageRenderer
 *
 * @package Drupal\eic_messages\Hooks
 */
class LogMessageRenderer implements ContainerInjectionInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * LogMessageRenderer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Render a log message and store the rendered string within
   * the field_rendered_content field. Please note that this field is mandatory
   * for logs as we want them to be static.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function render(EntityInterface $entity) {
    if ($entity->getEntityTypeId() !== 'message') {
      return;
    }

    $type = $entity->getTemplate()
      ->getThirdPartySetting('eic_messages', 'message_template_type');
    if (MessageTemplateTypes::LOG !== $type) {
      return;
    }

    $fields = $this->entityFieldManager
      ->getFieldStorageDefinitions($entity->getEntityTypeId());
    // If the entity doesn't have the rendered content field, we do nothing.
    $supported_field = array_filter($fields, function ($field) {
      return $field === MessageTokens::RENDERED_CONTENT_FIELD;
    }, ARRAY_FILTER_USE_KEY);

    if (
      empty($supported_field) ||
      !$entity->hasField(MessageTokens::RENDERED_CONTENT_FIELD)
    ) {
      return;
    }

    $view_builder = $this->entityTypeManager->getViewBuilder('message');
    $build = $view_builder->view($entity, 'pre_render');

    $entity->set(MessageTokens::RENDERED_CONTENT_FIELD, $build['partial_0']['#markup']);
    $entity->save();
  }

}
