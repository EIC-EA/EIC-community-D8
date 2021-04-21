<?php

namespace Drupal\oec_group_feature\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_feature\GroupFeatureInterface;

/**
 * Defines the group feature entity class.
 *
 * @ContentEntityType(
 *   id = "group_feature",
 *   label = @Translation("Group Feature"),
 *   label_collection = @Translation("Group Features"),
 *   handlers = {
 *     "view_builder" = "Drupal\oec_group_feature\GroupFeatureViewBuilder",
 *     "list_builder" = "Drupal\oec_group_feature\GroupFeatureListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\oec_group_feature\Form\GroupFeatureForm",
 *       "edit" = "Drupal\oec_group_feature\Form\GroupFeatureForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "group_feature",
 *   admin_permission = "access group feature overview",
 *   entity_keys = {
 *     "id" = "id",
 *     "gid" = "gid",
 *     "features" = "features",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/group-feature/add",
 *     "canonical" = "/group_feature/{group_feature}",
 *     "edit-form" = "/admin/content/group-feature/{group_feature}/edit",
 *     "delete-form" = "/admin/content/group-feature/{group_feature}/delete",
 *     "collection" = "/admin/content/group-feature"
 *   },
 * )
 */
class GroupFeature extends ContentEntityBase implements GroupFeatureInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->gid->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup(GroupInterface $group) {
    return $this->gid = $group->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getFeatures() {
    return !empty($this->features->first()) ? $this->features->first()->getValue() : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setFeatures(array $features) {
    $this->features = $features;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['gid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group'))
      ->setDescription(t('The group entity.'))
      ->setSetting('target_type', 'group')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->addConstraint('UniqueReferenceField');

    $fields['features'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Features'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDescription(t('Enabled features.'))
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the group feature was created.'))
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
      ->setDescription(t('The time that the group feature was last edited.'));

    return $fields;
  }

}
