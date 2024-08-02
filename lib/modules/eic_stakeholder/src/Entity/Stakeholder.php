<?php

declare(strict_types=1);

namespace Drupal\eic_stakeholder\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\eic_stakeholder\StakeholderInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the stakeholder entity class.
 *
 * @ContentEntityType(
 *   id = "stakeholder",
 *   label = @Translation("Stakeholder"),
 *   label_collection = @Translation("Stakeholders"),
 *   label_singular = @Translation("stakeholder"),
 *   label_plural = @Translation("stakeholders"),
 *   label_count = @PluralTranslation(
 *     singular = "@count stakeholders",
 *     plural = "@count stakeholders",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\eic_stakeholder\StakeholderListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\eic_stakeholder\StakeholderAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\eic_stakeholder\Form\StakeholderForm",
 *       "edit" = "Drupal\eic_stakeholder\Form\StakeholderForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm", *
 *     },
 *     "route_provider" = {
 *        "html" = "\Drupal\entity\Routing\DefaultHtmlRouteProvider",
 *        "revision" = "\Drupal\entity\Routing\RevisionRouteProvider",
 *     },
 *   },
 *   base_table = "stakeholder",
 *   data_table = "stakeholder_field_data",
 *   revision_table = "stakeholder_revision",
 *   revision_data_table = "stakeholder_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer stakeholder",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/content/stakeholder",
 *     "add-form" = "/stakeholder/add",
 *     "canonical" = "/stakeholder/{stakeholder}",
 *     "edit-form" = "/stakeholder/{stakeholder}/edit",
 *     "delete-form" = "/stakeholder/{stakeholder}/delete",
 *     "delete-multiple-form" = "/admin/content/stakeholder/delete-multiple",
 *     "revision" = "/stakeholder/{stakeholder}/revision/{stakeholder_revision}/view",
 *     "revision-delete-form" = "/stakeholder/{stakeholder}/revision/{stakeholder_revision}/delete",
 *     "revision-revert-form" = "/stakeholder/{stakeholder}/revision/{stakeholder_revision}/revert",
 *     "version-history" = "/stakeholder/{stakeholder}/revisions",
 *   },
 *   field_ui_base_route = "entity.stakeholder.settings",
 * )
 */
final class Stakeholder extends RevisionableContentEntityBase implements StakeholderInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Organisation name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the stakeholder was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the stakeholder was last edited.'));

    return $fields;
  }

}
