<?php

namespace Drupal\eic_webservices\Plugin\rest\resource;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_organisations\OrganisationsHelper;
use Drupal\eic_webservices\Utility\EicWsHelper;
use Drupal\eic_webservices\Utility\SmedTaxonomyHelper;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents EIC Organisation Resource records as resources.
 *
 * @RestResource (
 *   id = "eic_webservices_organisation",
 *   label = @Translation("EIC Organisation Resource"),
 *   entity_type = "group",
 *   serialization_class = "Drupal\group\Entity\Group",
 *   uri_paths = {
 *     "canonical" = "/smed/api/v1/organisation/{group}",
 *     "create" = "/smed/api/v1/organisation"
 *   }
 * )
 */
class OrganisationResource extends EntityResource {

  /**
   * The EIC Webservices helper class.
   *
   * @var \Drupal\eic_webservices\Utility\EicWsHelper
   */
  protected $wsHelper;

  /**
   * The SMED taxonomy helper class.
   *
   * @var \Drupal\eic_webservices\Utility\SmedTaxonomyHelper
   */
  protected $smedTaxonomyHelper;

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
   * @param \Drupal\eic_webservices\Utility\SmedTaxonomyHelper $eic_smec_taxonomy_helper
   *   The SMED taxonomy helper class.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    array $serializer_formats,
    LoggerInterface $logger,
    ConfigFactoryInterface $config_factory,
    PluginManagerInterface $link_relation_type_manager,
    EicWsHelper $eic_ws_helper,
    SmedTaxonomyHelper $eic_smec_taxonomy_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $serializer_formats, $logger, $config_factory, $link_relation_type_manager);
    $this->wsHelper = $eic_ws_helper;
    $this->smedTaxonomyHelper = $eic_smec_taxonomy_helper;
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
      $container->get('eic_webservices.ws_helper'),
      $container->get('eic_webservices.taxonomy_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function post(EntityInterface $entity = NULL) {
    // Get the field name that contains the SMED ID.
    $smed_id_field = $this->configFactory->get('eic_webservices.settings')->get('smed_id_field');

    // Check if organisation already exists.
    if (!empty($entity->{$smed_id_field}->value) &&
      $organisation = $this->wsHelper->getGroupBySmedId($entity->{$smed_id_field}->value, 'organisation')) {

      // Send custom response.
      $data = [
        'message' => 'Unprocessable Entity: validation failed. Organisation already exists.',
        $smed_id_field => $organisation->{$smed_id_field}->value,
      ];

      return new ResourceResponse($data, 422);
    }

    // Process SMED taxonomy fields to convert the SMED ID to Term ID.
    $this->smedTaxonomyHelper->convertEntitySmedTaxonomyIds($entity);

    // Initialise required fields if not provided.
    OrganisationsHelper::setRequiredFieldsDefaultValues($entity);

    return parent::post($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function patch(EntityInterface $original_entity, EntityInterface $entity = NULL) {
    // Process SMED taxonomy fields to convert the SMED ID to Term ID.
    $this->smedTaxonomyHelper->convertEntitySmedTaxonomyIds($entity);
    return parent::patch($original_entity, $entity);
  }

}
