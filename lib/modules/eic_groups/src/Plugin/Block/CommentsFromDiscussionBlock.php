<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Plugin\EditorManager;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_content\Services\EntityTreeManager;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_groups\Entity\Group;
use Drupal\eic_moderation\ModerationHelper;
use Drupal\eic_search\Search\Sources\UserTaggingCommentsSourceType;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Drupal\node\NodeInterface;
use Drupal\oec_group_comments\GroupPermissionChecker;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a CommentsFromDiscussionBlock block.
 *
 * @Block(
 *   id = "eic_groups_comments_from_discussion",
 *   admin_label = @Translation("EIC comments from discussion"),
 *   category = @Translation("European Innovation Council"),
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group", required = FALSE)
 *   }
 * )
 */
class CommentsFromDiscussionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The EIC groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  private $groupsHelper;

  /**
   * The group permission checker.
   *
   * @var \Drupal\oec_group_comments\GroupPermissionChecker
   */
  private $groupPermissionChecker;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * The current request.
   *
   * @var \Drupal\Core\Http\RequestStack
   */
  private $request;

  /**
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  private $fileUrlGenerator;

  /**
   * @var \Drupal\editor\Plugin\EditorManager
   */
  private EditorManager $editorManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The EIC Content Moderation Helper.
   *
   * @var \Drupal\eic_moderation\ModerationHelper
   */
  private $eicModerationHelper;

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
      $container->get('oec_group_comments.group_permission_checker'),
      $container->get('database'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('file_url_generator'),
      $container->get('plugin.manager.editor'),
      $container->get('entity_type.manager'),
      $container->get('eic_moderation.helper')
    );
  }

  /**
   * CommentsFromDiscussionBlock constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\eic_groups\EICGroupsHelper $groups_helper
   *   The EIC groups helper service.
   * @param \Drupal\oec_group_comments\GroupPermissionChecker $group_permission_checker
   *   The group permission checker.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Http\RequestStack $request
   *   The current request.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file url generator service.
   * @param \Drupal\editor\Plugin\EditorManager $editor_manager
   *   The editor manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager.
   * @param \Drupal\eic_moderation\ModerationHelper $eic_moderation_helper
   *   The EIC Content Moderation Helper.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EICGroupsHelper $groups_helper,
    GroupPermissionChecker $group_permission_checker,
    Connection $database,
    RouteMatchInterface $route_match,
    RequestStack $request,
    FileUrlGeneratorInterface $file_url_generator,
    EditorManager $editor_manager,
    EntityTypeManagerInterface $entity_type_manager,
    ModerationHelper $eic_moderation_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupsHelper = $groups_helper;
    $this->groupPermissionChecker = $group_permission_checker;
    $this->database = $database;
    $this->routeMatch = $route_match;
    $this->request = $request;
    $this->fileUrlGenerator = $file_url_generator;
    $this->editorManager = $editor_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->eicModerationHelper = $eic_moderation_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\node\NodeInterface|NULL $node */
    $node = $this->routeMatch->getParameter('node');
    $highlighted_comment = $this->request->getCurrentRequest()->query->get('highlighted-comment', 0);
    $highlighted_comment = Comment::load($highlighted_comment);
    $editor = Editor::load('filtered_html');
    $ckeditor_js_settings = $this->editorManager->createInstance('ckeditor')->getJSSettings($editor);

    if (!$node instanceof NodeInterface) {
      return [];
    }

    $cache_context = [
      'url.path',
      'url.query_args',
      'session',
      'route',
    ];

    $routes_to_ignore = [
      'entity.node.delete_form',
      'entity.node.edit_form',
      'entity.node.new_request',
    ];

    // Do not show comments block in delete/edit/request content.
    if (in_array($this->routeMatch->getRouteName(), $routes_to_ignore)) {
      return [
        '#cache' => [
          'contexts' => $cache_context,
        ],
      ];
    }

    // We need to highlight the top level.
    if (
      $highlighted_comment instanceof CommentInterface &&
      $highlighted_comment->getCommentedEntityId() === $node->id()
    ) {
      while ($highlighted_comment->hasParentComment()) {
        $highlighted_comment = $highlighted_comment->getParentComment();
      }
    }

    $is_comment_closed = FALSE;

    // If the comment system is closed or hidden, do not output the block.
    if (
      $node->hasField('field_comments') &&
      isset($node->get('field_comments')->getValue()[0])
    ) {
      $comment_status = (int) $node->get('field_comments')->getValue()[0]['status'];
      if (CommentItemInterface::HIDDEN === $comment_status) {
        return [
          '#cache' => [
            'tags' => $node->getCacheTags(),
          ],
        ];
      }

      $is_comment_closed = CommentItemInterface::CLOSED === $comment_status;
    }

    $current_group_route = $this->groupsHelper->getGroupFromRoute();
    $user_group_roles = [];
    $account = \Drupal::currentUser();
    $current_user = User::load(\Drupal::currentUser()->id());

    if ($current_group_route) {
      $membership = $current_group_route->getMember($account);
      $user_group_roles = $membership instanceof GroupMembership ? $membership->getRoles() : [];
    }

    $user_group_roles = array_merge(
      $user_group_roles,
      $account->getRoles(TRUE)
    );

    $disable_contributor_nodes = ['news', 'wiki_page'];
    $contributors_data = [];
    if (!in_array($node->bundle(), $disable_contributor_nodes)) {
      $contributors_data = $this->getContributors($node);
    }

    $group_contents = GroupContent::loadByEntity($node);

    /** @var \Drupal\media\MediaInterface|null $media_picture */
    $media_picture = $current_user->get('field_media')->referencedEntities();
    /** @var \Drupal\file\FileInterface|null $file */
    $file = $media_picture ?
      $this->entityTypeManager->getStorage('file')->load($media_picture[0]->get('oe_media_image')->target_id) :
      NULL;
    /** @var \Drupal\image\ImageStyleInterface $style */
    $style = $this->entityTypeManager->getStorage('image_style')->load('crop_36x36');
    $file_url = $file ? $this->fileUrlGenerator->transformRelative($style->buildUrl($file->get('uri')->getString())) : NULL;

    $group_id = $current_group_route ? $current_group_route->id() : 0;
    $user_url = Url::fromRoute('eic_search.solr_search', [
      'datasource' => json_encode(['user']),
      'source_class' => UserTaggingCommentsSourceType::class,
      'page' => 1,
      'current_group' => $group_id,
    ])->toString();

    $group = Group::load($group_id);
    $is_group_archived =
      $group instanceof GroupInterface &&
      !UserHelper::isPowerUser($account) &&
      $group->get('moderation_state')->value === DefaultContentModerationStates::ARCHIVED_STATE;

    $is_content_archived =
      !UserHelper::isPowerUser($account) &&
      $node->get('moderation_state')->value === DefaultContentModerationStates::ARCHIVED_STATE;

    $is_content_draft = $this->eicModerationHelper->isDraft($node);
    $is_content_unpublished = $this->eicModerationHelper->isUnpublished($node);

    $build['#attached']['drupalSettings']['overview'] = [
      'is_group_owner' => array_key_exists(
        EICGroupsHelper::GROUP_OWNER_ROLE,
        $user_group_roles
      ),
      'user' => [
        'avatar' => $file_url,
        'fullname' => (
        $current_user instanceof UserInterface ?
          $current_user->get(
            'field_first_name'
          )->value . ' ' . $current_user->get('field_last_name')->value :
          ''
        ),
        'url' => (
        $current_user instanceof UserInterface ?
          $current_user->toUrl()->toString() :
          '#'
        ),
      ],
      'is_comment_closed' => $is_comment_closed,
      'group_roles' => $user_group_roles,
      'group_id' => $group_id,
      'users_url' => $user_url,
      'users_url_search' => $user_url,
      'permissions' => [
        'post_comment' => !$is_group_archived && !$is_content_archived && !$is_content_draft && !$is_content_unpublished && $this->hasGroupOrGlobalPermission(
          $group_contents,
          $current_user,
          'post comments'
        ),
        'edit_all_comments' => !$is_group_archived && !$is_content_archived && !$is_content_draft && !$is_content_unpublished && $this->hasGroupOrGlobalPermission(
            $group_contents,
            $current_user,
            'edit all comments'
          ),
        'delete_all_comments' => !$is_group_archived && !$is_content_archived && !$is_content_draft && !$is_content_unpublished && UserHelper::isPowerUser($current_user),
        'edit_own_comments' => !$is_group_archived && !$is_content_archived && !$is_content_draft && !$is_content_unpublished && $this->hasGroupOrGlobalPermission(
          $group_contents,
          $current_user,
          'edit own comments'
        ),
      ],
      'translations' => EntityTreeManager::getTranslationsWidget(),
    ];

    $group_id = $current_group_route ? $current_group_route->id() : 0;

    if ($group_id) {
      $cache_context[] = "user.is_group_member:$group_id";
    }

    // We get the user access to view comments in the group. Note that power
    // users can always view comments.
    $can_view_comments = UserHelper::isPowerUser($account);
    if (!$can_view_comments) {
      $can_view_comments = $this->hasGroupOrGlobalPermission(
        $group_contents,
        $current_user,
        $group_id ? 'view comments' : 'access comments'
      );
    }

    $cache_tags = $node->getCacheTags();

    if ($group instanceof GroupInterface) {
      $cache_tags = array_merge($cache_tags, $group->getCacheTags());
    }

    $no_container_nodes = ['wiki_page'];
    return $build + [
        '#cache' => [
          'contexts' => $cache_context,
          'tags' => $cache_tags,
        ],
        '#highlighted_comment' => $highlighted_comment instanceof CommentInterface ?
          $highlighted_comment->id() :
          0,
        '#theme' => 'eic_group_comments_from_discussion',
        '#discussion_id' => $node->id(),
        '#contributors' => $contributors_data,
        '#is_anonymous' => $current_user->isAnonymous(),
        '#can_view_comments' => $can_view_comments,
        '#no_container' => in_array($node->bundle(), $no_container_nodes) ? TRUE : FALSE,
        '#ckeditor_js_settings' => $ckeditor_js_settings,
      ];
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function getContributors(NodeInterface $node): array {
    $contributors_data = [];
    if ('story' === $node->getType()) {
      $contributors = $node->get('field_story_paragraphs')->referencedEntities();
    }
    else {
      $contributors = $node->hasField('field_related_contributors') ?
        $node->get('field_related_contributors')->referencedEntities() :
        [];
    }

    $contributors = array_filter(
      $contributors,
      function (ParagraphInterface $paragraph) {
        return !empty($paragraph->get('field_user_ref')->referencedEntities());
      }
    );

    $users = array_map(function (ParagraphInterface $paragraph) {
      return $paragraph->get('field_user_ref')->referencedEntities()[0]->id();
    }, $contributors);

    // Grab users who commented the node to the list of contributors.
    if ($comment_contributorIds = $this->getNodeCommentContributorIds($node)) {
      foreach ($comment_contributorIds as $comment_contributorId) {
        if (!in_array($comment_contributorId['uid'], $users)) {
          $users[] = intval($comment_contributorId['uid']);
        }
      }
    }

    $users = array_unique(array_values($users), SORT_NUMERIC);
    $contributors_data['items'] = [];

    if (
      $node->getOwner() instanceof UserInterface &&
      (int) $node->getOwnerId() !== 0 &&
      !in_array($node->getOwnerId(), $users)
    ) {
      $contributors_data['items'][] = eic_community_get_teaser_user_display(
        $node->getOwner(),
        'crop_80x80'
      );
    }

    $users = User::loadMultiple($users);

    foreach ($users as $user) {
      $contributors_data['items'][] = eic_community_get_teaser_user_display(
        $user,
        'crop_80x80'
      );
    }

    return $contributors_data;
  }

  /**
   * Check if we are in "group" context, otherwise check global permissions.
   *
   * @param array|null $group_contents
   *   The group content entities.
   * @param \Drupal\user\UserInterface|null $user
   *   The user entity.
   * @param string $permission
   *   The user permission.
   *
   * @return bool
   *   TRUE if the user has permission.
   */
  private function hasGroupOrGlobalPermission(
    ?array $group_contents,
    ?UserInterface $user,
    string $permission
  ) {
    // If empty groups, that means we are not in group context.
    if (empty($group_contents)) {
      return $user instanceof UserInterface && $user->hasPermission(
          $permission
        );
    }

    return $this->groupPermissionChecker->getPermissionInGroups(
      $permission,
      $user,
      $group_contents
    )->isAllowed();
  }

  /**
   * Helper function to get contributor IDs from node comments.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object in which we want to retrieve the contributors
   *   that left a comment.
   *
   * @return array|bool
   *   Array of user IDs or FALSE if no contributors have been found.
   */
  private function getNodeCommentContributorIds(NodeInterface $node) {
    $query = $this->database->select('comment_field_data', 'c')
      ->fields('c', ['uid']);
    $query->condition('c.entity_id', $node->id());
    $query->condition('c.entity_type', 'node');
    // Skip anonymous users.
    $query->condition('c.uid', 0, '<>');
    // Skip contributors with deleted comments.
    $query->join('comment__field_comment_is_soft_deleted', 'csf', 'c.cid = csf.entity_id');
    $query->condition('csf.field_comment_is_soft_deleted_value', FALSE);
    // We group by uid to avoid duplicated results.
    $query->groupBy('c.uid');
    $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    return $results ?: FALSE;
  }

}
