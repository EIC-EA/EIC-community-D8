<?php

namespace Drupal\eic_groups\Commands;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\eic_groups\Constants\NodeProperty;
use Drush\Commands\DrushCommands;

/**
 * Class CreateFieldEditAccess
 *
 * @package Drupal\eic_groups\Routing
 */
class CreateFieldEditAccess extends DrushCommands {

  /**
   * Drush command that displays the given text.
   *
   * @command eg:create-field-access
   * @aliases eg:cfa
   *
   * @usage eg:create-field-access
   */
  public function createField() {
    // Creates new node base field.
    $field_storage_definition = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Editable by members'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(FALSE)
      ->setDescription(t('When checked, group members are able to edit the content of this page.'))
      ->setDisplayOptions('view', ['weight' => 1])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    \Drupal::entityDefinitionUpdateManager()
      ->installFieldStorageDefinition(NodeProperty::MEMBER_CONTENT_EDIT_ACCESS, 'node', 'node', $field_storage_definition);

    $this->output()->writeln("Field correctly createdss");
  }

}
