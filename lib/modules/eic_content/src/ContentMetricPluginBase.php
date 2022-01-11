<?php

namespace Drupal\eic_content;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_media_statistics\EntityFileDownloadCount;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for group_metric plugins.
 */
abstract class ContentMetricPluginBase extends PluginBase implements ContentMetricInterface, ContainerFactoryPluginInterface {

  use LoggerChannelTrait;

  use MessengerTrait;

  use StringTranslationTrait;

  /**
   * The EIC Flags helper service.
   *
   * @var \Drupal\eic_flags\FlagHelper
   */
  protected $flagHelper;

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
   * @param \Drupal\eic_media_statistics\EntityFileDownloadCount $entity_file_download_count
   *   The EIC entity file download count service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityFileDownloadCount $entity_file_download_count
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // @todo Move those services to the child classes where needed.
    $this->entityFileDownloadCount = $entity_file_download_count;
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
      $container->get('eic_media_statistics.entity_file_download_count')
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
