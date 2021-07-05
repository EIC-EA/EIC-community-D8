<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an EICGroupHeaderBlock block.
 *
 * @Block(
 *   id = "eic_group_header",
 *   admin_label = @Translation("EIC Group header"),
 *   category = @Translation("European Innovation Council"),
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group", required = FALSE)
 *   }
 * )
 */
class EICGroupHeaderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The EIC groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelperInterface
   */
  protected $eicGroupsHelper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OEC group flex helper service.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new EICGroupHeaderBlock instance.
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
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $eic_groups_helper
   *   The EIC groups helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\oec_group_flex\OECGroupFlexHelper $oec_group_flex_helper
   *   The OEC group flex helper service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match,
    EICGroupsHelperInterface $eic_groups_helper,
    EntityTypeManagerInterface $entity_type_manager,
    OECGroupFlexHelper $oec_group_flex_helper,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->eicGroupsHelper = $eic_groups_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
    $this->currentUser = $current_user;
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
      $container->get('current_route_match'),
      $container->get('eic_groups.helper'),
      $container->get('entity_type.manager'),
      $container->get('oec_group_flex.helper'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Do nothing if no group was found in the context or in the current route.
    if ((!$group = $this->getContextValue('group')) || !$group->id()) {
      if (!$group = $this->eicGroupsHelper->getGroupFromRoute()) {
        return $build;
      }
    }

    /** @var \Drupal\group\Entity\GroupInterface $group */

    // The content of this is block is shown depending on the current user's
    // permissions. It obviously also varies per group, but we cannot know for
    // sure how we got that group as it is up to the context provider to
    // implement that. This block will then inherit the appropriate cacheable
    // metadata from the context, as set by the context provider.
    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->setCacheContexts([
      'user.group_permissions',
      'url.path',
    ]);

    // Get group operation links.
    $group_operation_links = $this->entityTypeManager->getListBuilder($group->getEntityTypeId())->getOperations($group);

    // Get group content operation links.
    $node_operation_links = $this->eicGroupsHelper->getGroupContentOperationLinks($group, ['node'], $cacheable_metadata);
    $user_operation_links = $this->eicGroupsHelper->getGroupContentOperationLinks($group, ['user'], $cacheable_metadata);

    $operation_links = [];
    // Get login link for anonymous users.
    if ($login_link = $this->getAnonymousLoginLink($group)) {
      $operation_links['anonymous_user_link'] = $login_link;
    }

    // Moves group joining methods operations to the operation_links array.
    foreach ($user_operation_links as $key => $action) {
      if (in_array($action['url']->getRouteName(),
        [
          'entity.group.group_request_membership',
          'entity.group.join',
        ]
      )) {
        unset($user_operation_links[$key]);
        $operation_links[$key] = $action;
      }
    }

    // Gather all the group content creation links to create a two dimensional
    // array.
    $create_operations = [];
    foreach ($node_operation_links as $key => $link) {
      if (strpos($key, 'create') !== FALSE) {
        $create_operations[$key] = $link;
        unset($node_operation_links[$key]);
      }
    }

    if (count($create_operations) > 0) {
      $operation_links[] = [
        'label' => $this->t('Post content'),
        'links' => $create_operations,
      ];
    }

    // We extract only the group edit/delete operation links into a new array.
    $visible_group_operation_links = array_filter($group_operation_links, function ($key) {
      return in_array($key, ['edit', 'delete']);
    }, ARRAY_FILTER_USE_KEY);

    // Sorts group operation links by key. "Delete" operation needs to show
    // first.
    ksort($visible_group_operation_links);

    $membership_links = [];

    if (isset($group->flags)) {
      $membership_links = $group->flags;
    }

    $build['content'] = [
      '#theme' => 'eic_group_header_block',
      '#group' => $group,
      '#group_values' => [
        'id' => $group->id(),
        'bundle' => $group->bundle(),
        'title' => $group->label(),
        'description' => Markup::create($group->get('field_body')->value),
        'operation_links' => array_merge($operation_links, $node_operation_links, $visible_group_operation_links),
        'membership_links' => array_merge($membership_links, $user_operation_links),
      ],
    ];

    // Apply cacheable metadata to the renderable array.
    $cacheable_metadata->applyTo($build);

    return $build;
  }

  /**
   * Get login link for anonymous users.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return bool|array
   *   A key-value array containing the following key-value pairs:
   *   - title: The localized title of the link.
   *   - url: An instance of \Drupal\Core\Url for the login URL.
   */
  private function getAnonymousLoginLink(GroupInterface $group) {
    $link = FALSE;
    if ($this->currentUser->isAnonymous()) {
      if ($joining_methods = $this->oecGroupFlexHelper->getGroupJoiningMethod($group)) {
        $login_link_options = [
          'query' => [
            'destination' => $group->toUrl()->toString(),
          ],
        ];
        $link['url'] = Url::fromRoute('user.login', [], $login_link_options);
        switch ($joining_methods[0]['plugin_id']) {
          case 'tu_open_method':
            $link['title'] = $this->t('Log in to join group');
            break;

          case 'tu_group_membership_request':
            $link['title'] = $this->t('Log in to request membership');
            break;

        }
      }
    }
    return $link;
  }

}
