<?php

namespace Drupal\eic_non_required_on_draft\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_moderation\ModerationHelper;
use Drupal\field\FieldConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the NonRequiredOnDraft constraint.
 */
class NonRequiredOnDraftValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The EIC Content Moderation Helper.
   *
   * @var \Drupal\eic_moderation\ModerationHelper
   */
  private $eicModerationHelper;

  /**
   * Constructs a RequireOnPublishValidator object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\eic_moderation\ModerationHelper $eic_moderation_helper
   *    The EIC Content Moderation Helper.
   */
  public function __construct(MessengerInterface $messenger, ModerationHelper $eic_moderation_helper) {
    $this->messenger = $messenger;
    $this->eicModerationHelper = $eic_moderation_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('eic_moderation.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }

    $is_draft = $this->eicModerationHelper->isDraft($entity);

    /** @var \Drupal\Core\Field\FieldItemListInterface $field */
    foreach ($entity->getFields() as $field) {
      /** @var \Drupal\Core\Field\FieldConfigInterface $field_config */
      $field_config = $field->getFieldDefinition();
      if (!($field_config instanceof FieldConfigInterface)) {
        continue;
      }

      if ($field_config->getType() === 'boolean') {
        $field_name = $field->getName();
        $value = $entity->get($field_name)->value;
        if ($value) {
          continue;
        }
      }
      elseif (!$field->isEmpty()) {
        continue;
      }

      if (!$field_config->getThirdPartySetting('eic_non_required_on_draft', 'non_required_on_draft', FALSE)) {
        continue;
      }

      if (!$is_draft) {
        $message = $this->getStringTranslation()->translate($constraint->message, ['@field_label' => $field_config->getLabel()]);
        $this->context->buildViolation($message)->atPath($field->getName())->addViolation();
      }
    }
  }

}
