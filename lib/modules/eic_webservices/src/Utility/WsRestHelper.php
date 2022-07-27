<?php

namespace Drupal\eic_webservices\Utility;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides helper functions for EIC Webservices module.
 */
class WsRestHelper {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Determines if the current request is a SMED rest request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return bool
   *   TRUE if this is a SMED rest request.
   */
  public static function isSmedRestRequest(Request $request) {
    $path_info = $request->getPathInfo();
    return (strpos($path_info, '/smed/api/v1') === 0);
  }

  /**
   * Formats field values to an acceptable format.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to format.
   */
  public function formatEntityFields(EntityInterface &$entity) {
    // Get entity field definitions.
    $field_definitions = $this->entityFieldManager->getFieldDefinitions(
      $entity->getEntityTypeId(),
      $entity->bundle()
    );

    // Cycle through all fields.
    foreach ($field_definitions as $field_definition) {
      $field_name = $field_definition->getName();

      switch ($field_definition->getType()) {
        case 'link':
          if (!$entity->{$field_name}->isEmpty()) {
            $new_values = [];
            /** @var \Drupal\Core\Field\FieldItemBase $value */
            foreach ($entity->{$field_name} as $value) {
              $new_values[] = self::handleLinkFields($value->getValue());
            }
            // Replace old values with the new ones.
            $entity->{$field_name} = $new_values;
          }
          break;

      }
    }
  }

  /**
   * Make sure we have a proper protocol for link fields.
   *
   * @param array $value
   *   The value for the link field.
   *
   * @return array
   *   The final link to be provided in the link field.
   */
  public static function handleLinkFields(array $value): array {
    if (!empty($value['uri'])) {
      // If we have a proper protocol, just return the value as-is.
      $protocols = [
        'http://',
        'https://',
      ];
      $is_valid_protocol = FALSE;
      foreach ($protocols as $protocol) {
        if (strpos($value, $protocol) === 0) {
          $is_valid_protocol = TRUE;
          break;
        }
      }

      if (!$is_valid_protocol) {
        // Otherwise return the value with a default http protocol.
        $value['uri'] = 'http://' . preg_replace('#^.*://#', '', $value['uri']);
      }
    }

    return $value;
  }

}
