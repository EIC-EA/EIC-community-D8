<?php

namespace Drupal\eic_search\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_search\Search\Sources\ActivityStreamSourceType;
use Drupal\eic_topics\TopicsManager;
use Drupal\file\Entity\File;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ActivityStreamBlock block.
 *
 * @Block(
 *   id = "eic_search_activity_stream",
 *   admin_label = @Translation("EIC activity stream"),
 *   category = @Translation("European Innovation Council"),
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group", required = FALSE, label = @Translation("Group")),
 *     "taxonomy_term" = @ContextDefinition("entity:taxonomy_term", required = FALSE, label = @Translation("Taxonomy
 *   term"))
 *   }
 * )
 */
class ActivityStreamBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  private $entityTypeManager;

  /**
   * The Form builder service.
   *
   * @var ActivityStreamSourceType $activityStreamSourceType
   */
  private $activityStreamSourceType;

  /**
   * The Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter $dateFormatter
   */
  private $dateFormatter;

  /**
   * The current user account.
   *
   * @var AccountInterface $currentUser
   */
  private $currentUser;

  /**
   * The File url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  private $fileUrlGenerator;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  private $eicGroupsHelper;

  /**
   * LastGroupMembersBlock constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The Form builder service.
   * @param ActivityStreamSourceType $activity_stream_source_type
   *   The Activity Stream Source type.
   * @param DateFormatter $date_formatter
   *   The Date formatter.
   * @param AccountInterface $current_user
   *   The current user account.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The File url generator service.
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   *   The EIC Groups helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ActivityStreamSourceType $activity_stream_source_type,
    DateFormatter $date_formatter,
    AccountInterface $current_user,
    FileUrlGeneratorInterface $file_url_generator,
    EICGroupsHelper $eic_groups_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->activityStreamSourceType = $activity_stream_source_type;
    $this->dateFormatter = $date_formatter;
    $this->currentUser = $current_user;
    $this->fileUrlGenerator = $file_url_generator;
    $this->eicGroupsHelper = $eic_groups_helper;
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
      $container->get('entity_type.manager'),
      $container->get('eic_search.activity_stream_library'),
      $container->get('date.formatter'),
      $container->get('current_user'),
      $container->get('file_url_generator'),
      $container->get('eic_groups.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['show_members'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show related members'),
      '#description' => $this->t('Show related members depending on the selected context.'),
      '#default_value' => $config['show_members'] ?? FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['show_members'] = $values['show_members'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $url_options = [];

    /** @var \Drupal\group\Entity\GroupInterface|null $group */
    if ($group = $this->getContextValue('group')) {
      $url_options['query']['current_group'] = $group->id();
    }

    /** @var \Drupal\taxonomy\TermInterface|null $term */
    if ($term = $this->getContextValue('taxonomy_term')) {
      $url_options['query']['topics'] = $term->id();
    }

    $show_members = $config['show_members'];

    $has_delete_permission = FALSE;
    if ($group) {
      $has_delete_permission = EICGroupsHelper::userIsGroupAdmin($group, $this->currentUser);
    }

    $build['#attached']['drupalSettings'] = [
      'overview' => [
        'has_permission_delete' => $has_delete_permission,
      ],
      'node_statistics_url' => Url::fromRoute('eic_statistics.get_node_statistics')->toString(),
    ];

    $cache = [
      'contexts' => [
        'url.path',
        'url.query_args',
        'user.roles',
      ],
    ];

    // Send members data and cache tags.
    $members = [];
    if ($show_members && !$this->currentUser->isAnonymous()) {
      $members = $this->getMembersData($group);
      $cache['tags'] = [];
      foreach ($members as $member) {
        $cache['tags'] = !empty($member['cache_tags']) ?
          Cache::mergeTags($cache['tags'], $member['cache_tags']) :
          $cache['tags'];
      }
      $build['#members'] = $members;
    }

    return $build += [
      '#theme' => 'eic_group_last_activities_members',
      '#cache' => $cache,
      '#url' => Url::fromRoute('eic_search.solr_search', [], $url_options)->toString(),
      '#translations' => [
        'no_results_title' => $this->t('We haven’t found any search results', [], ['context' => 'eic_group']),
        'no_results_body' => $this->t('Please try again with another keyword', [], ['context' => 'eic_group']),
        'load_more' => $this->t('Load more', [], ['context' => 'eic_group']),
        'block_title' => $this->t('Latest activity', [], ['context' => 'eic_group']),
        'commented_on' => $this->t('commented on', [], ['context' => 'eic_group']),
        'delete_modal_title' => $this->t('Delete activity from activity stream', [], ['context' => 'eic_group']),
        'delete_modal_desc' => $this->t('Are you sure you want to delete this activity from the activity stream? Important: this action cannot be undone.',
          [], ['context' => 'eic_group']),
        'delete_modal_confirm' => $this->t('Yes, delete activity', [], ['context' => 'eic_group']),
        'delete_modal_cancel' => $this->t('Cancel', [], ['context' => 'eic_group']),
        'delete_modal_close' => $this->t('Close', [], ['context' => 'eic_group']),
      ],
      '#is_taxonomy_term_page' => TopicsManager::isTopicPage(),
      '#datasource' => $this->activityStreamSourceType->getSourcesId(),
      '#source_class' => ActivityStreamSourceType::class,
      '#group_id' => $group ? $group->id() : NULL,
      '#is_anonymous' => $this->currentUser->isAnonymous(),
    ];
  }

  /**
   * Returns the data for related members.
   *
   * @param \Drupal\group\Entity\GroupInterface|null $group
   *   If provided, the group for which we return members data.
   * @param int $limit
   *   The maximum number of results to return. Default to 5.
   *
   * @return array|array[]
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function getMembersData(GroupInterface $group = NULL, $limit = 5) {
    $query = \Drupal::entityQuery('group_content')
      ->condition('type', "{$group->bundle()}-group_membership")
      ->sort('created', 'DESC')
      ->range(0, $limit);

    if ($group) {
      $query->condition('gid', $group->id());
    }

    $members_ids = $query->execute();

    $members = GroupContent::loadMultiple($members_ids);

    return array_map(function (GroupContent $groupContent) {
      $profiles = $this->entityTypeManager->getStorage('profile')->loadByProperties([
        'uid' => $groupContent->getEntity()->id(),
        'type' => 'member',
      ]);

      if (empty($profiles)) {
        return [];
      }

      /** @var \Drupal\profile\Entity\ProfileInterface $profile */
      $profile = reset($profiles);
      $user = $profile->getOwner();

      if (!$user) {
        return [];
      }

      /** @var \Drupal\media\MediaInterface|null $media_picture */
      $media_picture = $user->get('field_media')->referencedEntities();
      /** @var File|NULL $file */
      $file = $media_picture ? File::load($media_picture[0]->get('oe_media_image')->target_id) : NULL;
      /** @var \Drupal\image\Entity\ImageStyle $style */
      $style = $this->entityTypeManager->getStorage('image_style')->load('crop_80x80');
      $file_url = $file ?
        $this->fileUrlGenerator->transformRelative($style->buildUrl($file->get('uri')->getString())) :
        NULL;
      $user_profile_url = $this->currentUser->hasPermission('access user profiles') ? $user->toUrl()->toString() : NULL;

      // Gets the list of user organisations as Markup.
      $organisations = [];
      if ($user_organisations = $this->eicGroupsHelper->getUserOrganisations($user)) {
        foreach ($user_organisations as $organisation) {
          if ($this->currentUser->isAnonymous()) {
            $organisations[] = $organisation->label();
            continue;
          }
          $organisations[] = $organisation->toLink()->toString();
        }
        $organisations = Markup::create(implode(', ', $organisations));
      }

      return [
        'joined_date' => $this->dateFormatter->format($groupContent->getCreatedTime(), 'eu_short_date'),
        'full_name' => $user->getDisplayName(),
        'email' => $user->getEmail(),
        'picture' => $file_url,
        'url' => $user_profile_url,
        'organisations' => $organisations,
        'uid' => $user->id(),
        'cache_tags' => $user->getCacheTags(),
      ];
    }, $members);
  }

}
