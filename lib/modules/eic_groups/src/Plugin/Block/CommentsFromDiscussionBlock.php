<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_search\Search\Sources\UserTaggingCommentsSourceType;
use Drupal\eic_user\UserHelper;
use Drupal\file\Entity\File;
use Drupal\flag\FlagService;
use Drupal\group\Entity\GroupContent;
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
  protected $groupsHelper;

  /**
   * The group permission checker.
   *
   * @var \Drupal\oec_group_comments\GroupPermissionChecker
   */
  protected $groupPermissionChecker;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flagService;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
      $container->get('flag'),
      $container->get('database')
    );
  }

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
   *   The plugin definition.
   * @param \Drupal\eic_groups\EICGroupsHelper $groups_helper
   *   The EIC groups helper service.
   * @param \Drupal\oec_group_comments\GroupPermissionChecker $group_permission_checker
   *   The group permission checker.
   * @param \Drupal\flag\FlagService $flag_service
   *   The flag service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EICGroupsHelper $groups_helper,
    GroupPermissionChecker $group_permission_checker,
    FlagService $flag_service,
    Connection $database
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupsHelper = $groups_helper;
    $this->groupPermissionChecker = $group_permission_checker;
    $this->flagService = $flag_service;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\node\NodeInterface|NULL $node */
    $node = \Drupal::routeMatch()->getParameter('node');

    if (!$node instanceof NodeInterface) {
      return [];
    }

    $is_comment_closed = FALSE;

    // If the comment system is closed or hidden, do not output the block.
    if (
      $node->hasField('field_comments') &&
      isset($node->get('field_comments')->getValue()[0])
    ) {
      $comment_status = (int) $node->get('field_comments')->getValue(
      )[0]['status'];
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

    if ($current_group_route) {
      $membership = $current_group_route->getMember($account);
      $user_group_roles = $membership instanceof GroupMembership ? $membership->getRoles(
      ) : [];
    }

    $user_group_roles = array_merge(
      $user_group_roles,
      $account->getRoles(TRUE)
    );

    $contributors_data = [];
    $current_user = User::load(\Drupal::currentUser()->id());

    if ('story' === $node->getType()) {
      $contributors = $node->get('field_story_paragraphs')->referencedEntities(
      );
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
        $node->getOwner()
      );
    }

    $users = User::loadMultiple($users);

    foreach ($users as $user) {
      $contributors_data['items'][] = eic_community_get_teaser_user_display(
        $user
      );
    }

    $group_contents = GroupContent::loadByEntity($node);

    /** @var \Drupal\media\MediaInterface|null $media_picture */
    $media_picture = $current_user->get('field_media')->referencedEntities();
    /** @var \Drupal\file\Entity\File|NULL $file */
    $file = $media_picture ? File::load(
      $media_picture[0]->get('oe_media_image')->target_id
    ) : NULL;
    $file_url = $file ? file_url_transform_relative(
      file_create_url($file->get('uri')->value)
    ) : NULL;

    $group_id = $current_group_route ? $current_group_route->id() : 0;
    $user_url = Url::fromRoute('eic_search.solr_search', [
      'datasource' => json_encode(['user']),
      'source_class' => UserTaggingCommentsSourceType::class,
      'page' => 1,
      'current_group' => $group_id,
    ])->toString();

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
        'post_comment' => $this->hasGroupOrGlobalPermission(
          $group_contents,
          $current_user,
          'post comments'
        ),
        'edit_all_comments' => $this->hasGroupOrGlobalPermission(
          $group_contents,
          $current_user,
          'edit all comments'
        ),
        'delete_all_comments' => UserHelper::isPowerUser($current_user),
        'edit_own_comments' => $this->hasGroupOrGlobalPermission(
          $group_contents,
          $current_user,
          'edit own comments'
        ),
      ],
      'translations' => [
        'title' => $this->t('Replies', [], ['context' => 'eic_groups']),
        'no_results_title' => $this->t(
          "We haven't found any comments",
          [],
          ['context' => 'eic_group']
        ),
        'no_results_body' => $this->t(
          'Please try again with another keyword',
          [],
          ['context' => 'eic_group']
        ),
        'load_more' => $this->t('Load more', [], ['context' => 'eic_groups']),
        'edit' => $this->t('Edit', [], ['context' => 'eic_groups']),
        'options' => $this->t('Options', [], ['context' => 'eic_groups']),
        'reply_to' => $this->t('Reply', [], ['context' => 'eic_groups']),
        'in_reply_to' => $this->t('in reply to', [], ['context' => 'eic_groups']
        ),
        'reply' => $this->t('Reply', [], ['context' => 'eic_groups']),
        'submit' => $this->t('Submit', [], ['context' => 'eic_groups']),
        'reason' => $this->t('Reason', [], ['context' => 'eic_groups']),
        'comment_placeholder' => $this->t(
          'Type your message here...',
          [],
          ['context' => 'eic_groups']
        ),
        'action_edit_comment' => $this->t(
          'Edit comment',
          [],
          ['context' => 'eic_groups']
        ),
        'action_delete_comment' => $this->t(
          'Delete comment',
          [],
          ['context' => 'eic_groups']
        ),
        'action_request_delete' => $this->t(
          'Request deletion',
          [],
          ['context' => 'eic_groups']
        ),
        'action_request_archival' => $this->t(
          'Request archival',
          [],
          ['context' => 'eic_groups']
        ),
        'select_value' => $this->t(
          'Select a value',
          [],
          ['context' => 'eic_search']
        ),
        'match_limit' => $this->t(
          'You can select only <b>@match_limit</b> top-level items.',
          ['@match_limit' => 0],
          ['context' => 'eic_search']
        ),
        'search' => $this->t('Search', [], ['context' => 'eic_search']),
        'your_values' => $this->t(
          'Your selected values',
          [],
          ['context' => 'eic_search']
        ),
        'required_field' => $this->t(
          'This field is required',
          [],
          ['context' => 'eic_content']
        ),
        'select_users' => $this->t(
          'Select users',
          [],
          ['context' => 'eic_content']
        ),
        'modal_invite_users_title' => $this->t(
          'Invite user(s)',
          [],
          ['context' => 'eic_content']
        ),
      ],
    ];

    $group_id = $current_group_route ? $current_group_route->id() : 0;

    $cache_context = [
      'url.path',
      'url.query_args',
      'user.group_permissions',
      'session',
    ];

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

    return $build + [
      '#cache' => [
        'contexts' => $cache_context,
        'tags' => $node->getCacheTags(),
      ],
      '#theme' => 'eic_group_comments_from_discussion',
      '#discussion_id' => $node->id(),
      '#contributors' => $contributors_data,
      '#is_anonymous' => $current_user->isAnonymous(),
      '#can_view_comments' => $can_view_comments,
    ];
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
