<?php

namespace Drupal\eic_projects\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\eic_projects\ExtractionRequestInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the extraction request entity class.
 *
 * @ContentEntityType(
 *   id = "extraction_request",
 *   label = @Translation("Extraction request"),
 *   label_collection = @Translation("Extraction requests"),
 *   label_singular = @Translation("extraction request"),
 *   label_plural = @Translation("extraction requests"),
 *   label_count = @PluralTranslation(
 *     singular = "@count extraction requests",
 *     plural = "@count extraction requests",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\eic_projects\ExtractionRequestListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\eic_projects\ExtractionRequestAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\eic_projects\Form\ExtractionRequestForm",
 *       "edit" = "Drupal\eic_projects\Form\ExtractionRequestForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "extraction_request",
 *   revision_table = "extraction_request_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer extraction request",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
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
 *     "collection" = "/admin/content/extraction-request",
 *     "add-form" = "/extraction-request/add",
 *     "canonical" = "/extraction-request/{extraction_request}",
 *     "edit-form" = "/extraction-request/{extraction_request}/edit",
 *     "delete-form" = "/extraction-request/{extraction_request}/delete",
 *   },
 * )
 */
class ExtractionRequest extends RevisionableContentEntityBase implements ExtractionRequestInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Entity status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['extraction_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('The status of the extraction'))
      ->setDescription(t('The current status of the extraction.'))
      ->setDefaultValue('requested')
      ->setSettings([
        'allowed_values' => [
          'requested' => 'Requested',
          'pending_extraction' => 'Data is being extracted from CORDIS',
          'extracted' => 'Data extracted',
          'pending_migration' => 'Data is extracted and waiting for migration',
          'migrating' => 'Data is being migrated to site',
          'completed' => 'Data has been migrated to site',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'list_default',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['task_id'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setLabel(t('Task ID'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['query'] = BaseFieldDefinition::create('string_long')
      ->setRevisionable(TRUE)
      ->setLabel(t('Query requested to CORDIS Data Extraction API'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['extraction_file'] = BaseFieldDefinition::create('file')
      ->setLabel('Extraction file')
      ->setSettings([
        'uri_scheme' => 'private',
        'file_directory' => 'cordis-xml',
        'file_extensions' => 'zip',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'file',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the extraction request was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the extraction request was last edited.'));

    return $fields;
  }

}
