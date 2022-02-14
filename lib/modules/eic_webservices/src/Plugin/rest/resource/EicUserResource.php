<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_webservices\Utility\EicWsHelper;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to get, create and update users.
 *
 * @RestResource(
 *   id = "eic_webservices_user",
 *   label = @Translation("EIC User Resource"),
 *   entity_type = "user",
 *   serialization_class = "Drupal\user\Entity\User",
 *   uri_paths = {
 *     "canonical" = "/smed/api/v1/user/{user}",
 *     "create" = "/smed/api/v1/user"
 *   }
 * )
 */
class EicUserResource extends EntityResource {

  /**
   * The EIC Webservices helper class.
   *
   * @var \Drupal\eic_webservices\Utility\EicWsHelper
   */
  protected $wsHelper;

  /**
   * Constructs a EicUserResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $link_relation_type_manager
   *   The link relation type manager.
   * @param \Drupal\eic_webservices\Utility\EicWsHelper $eic_ws_helper
   *   The EIC Webservices helper class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, array $serializer_formats, LoggerInterface $logger, ConfigFactoryInterface $config_factory, PluginManagerInterface $link_relation_type_manager, EicWsHelper $eic_ws_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $serializer_formats, $logger, $config_factory, $link_relation_type_manager);
    $this->wsHelper = $eic_ws_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('config.factory'),
      $container->get('plugin.manager.link_relation_type'),
      $container->get('eic_webservices.ws_helper')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @param \Drupal\user\UserInterface|null $entity
   *   The entity.
   *
   * @return \Drupal\rest\ResourceResponseInterface
   *   The response.
   */
  public function post(EntityInterface $entity = NULL) {
    // Get the field name that contains the SMED ID.
    $smed_id_field = $this->configFactory->get('eic_webservices.settings')->get('smed_id_field');

    // Check if user already exists.
    $user_exists = FALSE;
    if ($user = user_load_by_mail($entity->getEmail())) {
      $user_exists = TRUE;
    }
    elseif ($user = user_load_by_name($entity->getAccountName())) {
      $user_exists = TRUE;
    }
    elseif (!empty($entity->{$smed_id_field}->value) &&
      $user = $this->wsHelper->getUserBySmedId($entity->{$smed_id_field}->value)) {
      $user_exists = TRUE;
    }

    // If user already exists, return a customised response.
    if ($user_exists) {
      // Send custom response.
      $data = [
        'message' => 'Unprocessable Entity: validation failed. User already exists.',
        $smed_id_field => $user->{$smed_id_field}->value,
      ];

      return new ResourceResponse($data, 422);
    }

    parent::post($entity);

    return new ResourceResponse($entity);
  }

}
