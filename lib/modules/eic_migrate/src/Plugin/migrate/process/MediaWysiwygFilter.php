<?php

namespace Drupal\eic_migrate\Plugin\migrate\process;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

/**
 * Processes [[{"type":"media","fid":"1234",...}]] tokens in content.
 *
 * These style tokens come from media_wysiwyg module. The regex it uses to
 * match them for reference is:
 *
 * /\[\[.+?"type":"media".+?\]\]/s
 *
 * @code
 * # From this
 * [[{"type":"media","fid":"1234",...}]]
 *
 * # To this
 * <drupal-entity
 *   data-embed-button="media"
 *   data-entity-embed-display="view_mode:media.full"
 *   data-entity-type="media"
 *   data-entity-id="1234"></drupal-entity>
 * # or to this:
 * <drupal-media
 *   data-entity-type="media"
 *   data-view-mode="full"
 *   data-entity-uuid="12345678-9abc-def0-1234-56789abcdef0"></drupal-media>
 * @endcode
 *
 * Available configuration keys:
 *
 * - view_mode_matching: (optional) Key-value mapping of view modes.
 * - media_migrations: (optional) The file to media migrations to use for
 *   looking up destination Media IDs.
 * - file_migrations: (optional) The file migrations to use for looking up
 *   destination File IDs. Used for updating fid and token in file download
 *   links. (/file/{fid}/download?token={token})
 * - embed_media_destination_filter_plugin: (optional) The embed destination
 *   plugin. (media_embed OR entity_embed) entity_embed requires the
 *   entity_embed module. Default: media_embed
 * - embed_media_destination_reference_method: (optional) The embed destination
 *   reference. (id OR uuid) When filter_plugin is media_embed reference method
 *   is always uuid. Default: uuid
 *
 * Usage:
 *
 * @endcode
 * process:
 *   bar:
 *     plugin: eic_media_wysiwyg_filter
 *     view_mode_matching:
 *       default: full
 *     media_migrations:
 *      - upgrade_d7_file_document_to_media
 *      - upgrade_d7_file_image_to_media
 *     file_migrations:
 *      - upgrade_d7_file
 *     embed_media_destination_filter_plugin: 'media_embed'
 *     embed_media_destination_reference_method: 'uuid'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "eic_media_wysiwyg_filter"
 * )
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class MediaWysiwygFilter extends ProcessPluginBase implements ConfigurableInterface, ContainerFactoryPluginInterface {

  /**
   * Entity embed destination filter.
   *
   * @var string
   */
  protected const MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED = 'entity_embed';

  /**
   * Media embed destination filter.
   *
   * @var string
   */
  protected const MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED = 'media_embed';

  /**
   * Default embed token destination filter plugin ID.
   *
   * @var string
   */
  protected const MEDIA_TOKEN_DESTINATION_FILTER_DEFAULT = self::MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED;

  /**
   * The required modules of the valid destination filter plugins.
   *
   * @var array[]
   */
  protected const MEDIA_TOKEN_DESTINATION_FILTER_REQUIREMENTS = [
    self::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED => [
      'entity_embed',
    ],
    self::MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED => [
      'media',
    ],
  ];

  /**
   * The ID embedded media reference method.
   *
   * @var string
   */
  protected const EMBED_MEDIA_REFERENCE_METHOD_ID = 'id';

  /**
   * The UUID embedded media reference method.
   *
   * @var string
   */
  protected const EMBED_MEDIA_REFERENCE_METHOD_UUID = 'uuid';

  /**
   * Default embedded media reference method.
   *
   * @var string
   */
  protected const EMBED_MEDIA_REFERENCE_METHOD_DEFAULT = self::EMBED_MEDIA_REFERENCE_METHOD_UUID;

  /**
   * Valid embedded media reference methods.
   *
   * @var string[]
   */
  protected const VALID_EMBED_MEDIA_REFERENCE_METHODS = [
    self::EMBED_MEDIA_REFERENCE_METHOD_ID,
    self::EMBED_MEDIA_REFERENCE_METHOD_UUID,
  ];

  /**
   * The migration entity.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The entity embed display plugin manager service, if available.
   *
   * @var \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager|null
   */
  protected $entityEmbedDisplayPluginManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The database connection. (default)
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs a new selection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   * @param \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager|null $entity_embed_display_manager
   *   The entity embed display plugin manager service, if available.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, $entity_embed_display_manager, MigrationPluginManagerInterface $migration_plugin_manager, Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);

    $this->migration = $migration;
    $this->entityEmbedDisplayPluginManager = $entity_embed_display_manager;
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.entity_embed.display', ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $container->get('plugin.manager.migration'),
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'view_mode_matching' => [],
      'media_migrations' => [],
      'file_migrations' => [],
      'embed_media_destination_filter_plugin' => NULL,
      'embed_media_destination_reference_method' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    // Merge in defaults.
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If Media WYSIWYG wasn't enabled on the source site, we don't have to do
    // anything.
    $source_plugin = $this->migration->getSourcePlugin();
    if ($source_plugin instanceof DrupalSqlBase) {
      $system_data = $source_plugin->getSystemData();
      if (!isset($system_data['module']['media_wysiwyg']['status']) || empty($system_data['module']['media_wysiwyg']['status'])) {
        return $value;
      }
    }

    if (!$this->embedTokenDestinationFilterPluginIsValid($this->getEmbedTokenDestinationFilterPlugin())) {
      throw new MigrateException("The embed token's destination filter plugin ID is invalid.");
    }

    $pattern = '/\[\[\s*(?<tag_info>\{.+\})\s*\]\]/sU';
    $decoder = new JsonDecode(TRUE);
    $entity_type_id = explode(':', $this->migration->getDestinationConfiguration()['plugin'])[1];
    $source_identifier = [];
    foreach ($row->getSourceIdValues() as $source_id_key => $source_id_value) {
      $source_identifier[] = "$source_id_key $source_id_value";
    }
    $source_identifier = implode(', ', $source_identifier);

    $value = preg_replace_callback($pattern, function ($matches) use ($decoder, $entity_type_id, $source_identifier) {
      // Replace line breaks with a single space for valid JSON.
      $matches['tag_info'] = preg_replace('/\s+/', ' ', $matches['tag_info']);

      try {
        $tag_info = $decoder->decode($matches['tag_info'], JsonEncoder::FORMAT);

        if (!is_array($tag_info) || !array_key_exists('fid', $tag_info)) {
          return $matches[0];
        }

        // Find matching view mode.
        if ($this->configuration['view_mode_matching']) {
          foreach ($this->configuration['view_mode_matching'] as $key => $match) {
            if ($key == $tag_info['view_mode'] ?? NULL) {
              $tag_info['view_mode'] = $match;
            }
          }
        }

        $embed_metadata = [
          'id' => $tag_info['fid'],
          'view_mode' => $tag_info['view_mode'] ?? 'default',
        ];

        $source_attributes = !empty($tag_info['attributes']) ?
          $tag_info['attributes'] : [];

        // Add alt and title overrides.
        foreach (['alt', 'title'] as $attribute_name) {
          if (!empty($source_attributes[$attribute_name])) {
            $embed_metadata[$attribute_name] = $source_attributes[$attribute_name];
          }
        }

        // Add alignment.
        if (!empty($source_attributes['class']) && is_string($source_attributes['class'])) {
          $alignment_map = [
            'media-wysiwyg-align-center' => 'center',
            'media-wysiwyg-align-left' => 'left',
            'media-wysiwyg-align-right' => 'right',
          ];
          $classes_array = array_unique(explode(' ', preg_replace('/\s{2,}/', ' ', trim($source_attributes['class']))));

          foreach ($alignment_map as $original => $replacement) {
            if (in_array($original, $classes_array, TRUE)) {
              $embed_metadata['data-align'] = $replacement;
              break;
            }
          }
        }

        return $this->getEmbedCode($embed_metadata) ?? $matches[0];
      }
      catch (NotEncodableValueException $e) {
        // There was an error decoding the JSON.
        $this->messenger()
          ->addWarning(sprintf('The following media_wysiwyg token in %s %s does not have valid JSON: %s', $entity_type_id, $source_identifier, $matches[0]));
        return $matches[0];
      }
      catch (\LogicException $e) {
        return $matches[0];
      }
    }, $value);

    // Update fid and token in regex /file/{fid}/download?token={token}.
    if ($this->configuration['file_migrations']) {
      $pattern = '#\/file\/([0-9]*)\/download\?token=([a-zA-Z0-9]*)#';
      $replacement_template = '/file/%s/download?token=%s';
      $value = preg_replace_callback($pattern, function ($matches) use ($replacement_template) {
        $oldId = $matches[1];
        $newId = $this->findDestId($oldId, $this->configuration['file_migrations']);
        $newToken = '';

        try {
          $reflector = new \ReflectionClass('Drupal\file_entity\Entity\FileEntity');
          $file = $this->entityTypeManager->getStorage('file')->load($newId);

          if ($file && $reflector->hasMethod('getDownloadToken')) {
            $newToken = $file->getDownloadToken();
          }
        }
        catch (\ReflectionException $e) {
        }

        return sprintf($replacement_template, $newId, $newToken);
      }, $value);
    }

    return $value;
  }

  /**
   * Returns the destination display plugin ID.
   *
   * @param string $view_mode
   *   The view_mode from the source.
   * @param string $destination_filter_plugin
   *   The transform destination filter plugin ID.
   *
   * @return string
   *   The embed media's display plugin ID or view_mode.
   */
  protected function getDisplayPluginId(string $view_mode, string $destination_filter_plugin): string {
    switch ($destination_filter_plugin) {
      case 'entity_embed':
        $display_plugin_id = "view_mode:media.$view_mode";
        break;

      case 'media_embed':
        return $view_mode;

      default:
        throw new \LogicException();
    }

    // Ensure that the display plugin exists.
    if ($this->entityEmbedDisplayPluginManager instanceof EntityEmbedDisplayManager) {
      $available_plugins = $this->entityEmbedDisplayPluginManager->getDefinitionOptionsForEntityType('media');

      if (empty($available_plugins)) {
        throw new \LogicException("Media Migration cannot replace a media_filter token in a content entity, since there aren't any available entity_embed display plugins.");
      }

      if (!isset($available_plugins[$display_plugin_id])) {
        // If the preselected display plugin does not exist, then we will
        // try to map it to 'view_mode:media.full'.
        if (isset($available_plugins['view_mode:media.full'])) {
          $display_plugin_id = 'view_mode:media.full';
        }
        // If 'view_mode:media.full' is also missing, then we try to pick
        // the first 'view_mode:media.[any]' derivative.
        else {
          $view_mode_plugins = array_reduce(array_keys($available_plugins), function ($carry, $plugin_id) {
            if (strpos($plugin_id, 'view_mode:media.') === 0) {
              $carry[$plugin_id] = $plugin_id;
            }
            return $carry;
          });

          // If we have 'view_mode:media.[any]', we use the first one; if
          // not, then use the first display plugin.
          $display_plugin_id = !empty($view_mode_plugins) ? reset($view_mode_plugins) : array_keys($available_plugins)[0];
        }
      }
    }

    return $display_plugin_id;
  }

  /**
   * Creates the replacement token for the specified embed filter.
   *
   * @param array $embed_metadata
   *   The metadata for the embed code.
   *
   * @return string|null
   *   The embed HTML code.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  protected function getEmbedCode(array $embed_metadata): ?string {
    if (empty($embed_metadata['id']) || empty($embed_metadata['view_mode'])) {
      return NULL;
    }
    $destination_filter_plugin_id = $this->getEmbedTokenDestinationFilterPlugin();
    $embed_media_reference_method = $this->getEmbedMediaReferenceMethod();
    $filter_destination_is_entity_embed = $destination_filter_plugin_id === self::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED;
    $reference_method_is_id = $embed_media_reference_method === self::EMBED_MEDIA_REFERENCE_METHOD_ID;
    $tag = $filter_destination_is_entity_embed ?
      'drupal-entity' :
      'drupal-media';

    // Add the static attributes first.
    $attributes = $filter_destination_is_entity_embed ?
      ['data-embed-button' => 'media'] :
      [];
    $attributes['data-entity-type'] = 'media';

    // Add the attribute that references the embed media.
    $reference_attribute = $reference_method_is_id ?
      'data-entity-id' :
      'data-entity-uuid';
    if ($reference_method_is_id && $this->configuration['media_migrations']) {
      $embed_metadata['id'] = $this->findDestId($embed_metadata['id'], $this->configuration['media_migrations']);
    }
    $attributes[$reference_attribute] = $reference_method_is_id ?
      $embed_metadata['id'] :
      $this->getMediaUuid((int) $embed_metadata['id']);

    // Add attribute that controls how the embed media is displayed.
    $display_mode_property = $destination_filter_plugin_id === self::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED ?
      'data-entity-embed-display' :
      'data-view-mode';
    $attributes[$display_mode_property] = $this->getDisplayPluginId($embed_metadata['view_mode'], $destination_filter_plugin_id);

    // Alt, title, caption and align should be handled conditionally.
    $conditional_attributes = ['alt', 'title', 'data-caption', 'data-align'];
    foreach ($conditional_attributes as $conditional_attribute) {
      if (!empty($embed_metadata[$conditional_attribute])) {
        $attributes[$conditional_attribute] = $embed_metadata[$conditional_attribute];
      }
    }

    $attribute = new Attribute($attributes);
    return "<$tag{$attribute->__toString()}></$tag>";
  }

  /**
   * Find the destination File ID using the migration lookup system.
   *
   * @param int $source_id
   *   The original ID.
   * @param array $migrations
   *   The ID of the migrations to look at.
   *
   * @return int
   *   The new ID.
   */
  protected function findDestId(int $source_id, array $migrations) {
    try {
      $lookup_migrations = $this->migrationPluginManager->createInstances($migrations);
    }
    catch (PluginException $exception) {
      return $source_id;
    }

    foreach ($lookup_migrations as $lookup_migration_id => $lookup_migration) {
      $source_id_values[$lookup_migration_id] = [$source_id];
      try {
        $destination_ids = $lookup_migration->getIdMap()
          ->lookupDestinationIds($source_id_values[$lookup_migration_id]);
      }
      catch (MigrateException $exception) {
        continue;
      }

      if (!empty($destination_ids)) {
        return reset($destination_ids)[0];
      }
    }

    return $source_id;
  }

  /**
   * Returns the new Media UUID for a source File ID.
   *
   * @param int $source_id
   *   The source File ID.
   *
   * @return string|null
   *   The Media UUID.
   */
  protected function getMediaUuid(int $source_id): ?string {
    $destId = $this->findDestId($source_id, $this->configuration['media_migrations']);

    $query = $this->database->select('media', 'm')
      ->fields('m', ['uuid'])
      ->condition('mid', $destId);
    $result = $query->execute()->fetchField();

    if (!$result) {
      // @todo Does the process crash when NULL is returned?
      return NULL;
    }

    return $result;
  }

  /**
   * Returns the embed media token transform's destination filter_plugin.
   *
   * @return string
   *   The embed media token destination plugin.
   */
  protected function getEmbedTokenDestinationFilterPlugin(): string {
    $filterPluginId = $this->configuration['embed_media_destination_filter_plugin'];

    if ($this->embedTokenDestinationFilterPluginIsValid($filterPluginId)) {
      return $filterPluginId;
    }

    return self::MEDIA_TOKEN_DESTINATION_FILTER_DEFAULT;
  }

  /**
   * Returns the method of the embedded media reference.
   *
   * @return string
   *   The reference method. This might be 'id', or 'uuid'.
   */
  protected function getEmbedMediaReferenceMethod(): string {
    $referenceMethod = $this->configuration['embed_media_destination_reference_method'];

    // If destination filter plugin is media_embed, only the uuid reference
    // method is supported.
    if ($this->getEmbedTokenDestinationFilterPlugin() === self::MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED) {
      return self::EMBED_MEDIA_REFERENCE_METHOD_UUID;
    }

    if (!in_array($referenceMethod, self::VALID_EMBED_MEDIA_REFERENCE_METHODS, TRUE)) {
      return $referenceMethod;
    }

    return self::EMBED_MEDIA_REFERENCE_METHOD_DEFAULT;
  }

  /**
   * Whether the transform's destination filter_plugin is valid or not.
   *
   * @param string|null $filter_plugin_id
   *   The filter plugin ID to check.
   *
   * @return bool
   *   TRUE if the plugin is valid, FALSE if not.
   */
  protected function embedTokenDestinationFilterPluginIsValid(?string $filter_plugin_id): bool {
    $valid_filter_plugin_ids = array_keys(self::MEDIA_TOKEN_DESTINATION_FILTER_REQUIREMENTS);

    return in_array($filter_plugin_id, $valid_filter_plugin_ids, TRUE);
  }

}
