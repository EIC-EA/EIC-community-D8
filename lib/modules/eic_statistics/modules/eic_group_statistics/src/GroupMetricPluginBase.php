<?php

namespace Drupal\eic_group_statistics;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\FlagHelper;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_media_statistics\EntityFileDownloadCount;
use Drupal\group\Plugin\GroupContentEnablerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for group_metric plugins.
 */
abstract class GroupMetricPluginBase extends PluginBase implements GroupMetricInterface, ContainerFactoryPluginInterface {

  use LoggerChannelTrait;

  use MessengerTrait;

  use StringTranslationTrait;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $groupsHelper;

  /**
   * The EIC Flags helper service.
   *
   * @var \Drupal\eic_flags\FlagHelper
   */
  protected $flagHelper;

  /**
   * The EIC group statistics helper service.
   *
   * @var \Drupal\eic_group_statistics\GroupStatisticsHelperInterface
   */
  protected $groupStatisticsHelper;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The EIC entity file download count service.
   *
   * @var \Drupal\eic_media_statistics\EntityFileDownloadCount
   */
  protected $entityFileDownloadCount;

  /**
   * The group content enabler manager service.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManager
   */
  protected $groupContentEnablerManager;

  /**
   * Constructs a new GroupTokens object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   *   The EIC Groups helper service.
   * @param \Drupal\eic_flags\FlagHelper $eic_flag_helper
   *   The EIC Flags helper service.
   * @param \Drupal\eic_group_statistics\GroupStatisticsHelperInterface $group_statistics_helper
   *   The EIC group statistics helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\eic_media_statistics\EntityFileDownloadCount $entity_file_download_count
   *   The EIC entity file download count service.
   * @param \Drupal\group\Plugin\GroupContentEnablerManager $group_content_enabler_manager
   *   The group content enabler manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EICGroupsHelper $eic_groups_helper,
    FlagHelper $eic_flag_helper,
    GroupStatisticsHelperInterface $group_statistics_helper,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFileDownloadCount $entity_file_download_count,
    GroupContentEnablerManager $group_content_enabler_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // @todo Move those services to the child classes where needed.
    $this->groupsHelper = $eic_groups_helper;
    $this->flagHelper = $eic_flag_helper;
    $this->groupStatisticsHelper = $group_statistics_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFileDownloadCount = $entity_file_download_count;
    $this->groupContentEnablerManager = $group_content_enabler_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_groups.helper'),
      $container->get('eic_flags.helper'),
      $container->get('eic_group_statistics.helper'),
      $container->get('entity_type.manager'),
      $container->get('eic_media_statistics.entity_file_download_count'),
      $container->get('plugin.manager.group_content_enabler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDefinition(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(array $values = []): array {
    return [];
  }

  /**
   * Returns the selected options based on the form element submitted values.
   *
   * @param array $selection
   *   The submitted values returned by the form element.
   *
   * @return array
   *   An array of selected options.
   */
  protected function getSelectedOptions(array $selection) {
    $selected_options = [];
    foreach ($selection as $key => $value) {
      if ($key === $value) {
        $selected_options[] = $key;
      }
    }
    return $selected_options;
  }

}
