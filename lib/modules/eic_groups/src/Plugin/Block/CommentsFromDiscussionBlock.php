<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\eic_groups\EICGroupsHelper;
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
   * @var \Drupal\eic_groups\EICGroupsHelper $groupsHelper
   */
  private $groupsHelper;

  /**
   * The group permission checker
   *
   * @var GroupPermissionChecker
   */
  private $groupPermissionChecker;

  /**
   * The flag service
   *
   * @var \Drupal\flag\FlagService $flagService
   */
  private $flagService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_groups.helper'),
      $container->get('oec_group_comments.group_permission_checker'),
      $container->get('flag')
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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EICGroupsHelper $groups_helper,
    GroupPermissionChecker $group_permission_checker,
    FlagService $flag_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupsHelper = $groups_helper;
    $this->groupPermissionChecker = $group_permission_checker;
    $this->flagService = $flag_service;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var NodeInterface|NULL $node */
    $node = \Drupal::routeMatch()->getParameter('node');

    if (!$node instanceof NodeInterface) {
      return [];
    }

    // If the comment system is closed or hidden, do not output the block.
    if (
      $node->hasField('field_comments') &&
      isset($node->get('field_comments')->getValue()[0]) &&
      CommentItemInterface::OPEN !== (int) $node->get('field_comments')
        ->getValue()[0]['status']
    ) {
      return [
        '#cache' => [
          'tags' => $node->getCacheTags(),
        ],
      ];
    }

    $current_group_route = $this->groupsHelper->getGroupFromRoute();
    $user_group_roles = [];
    $account = \Drupal::currentUser();

    if ($current_group_route) {
      $membership = $current_group_route->getMember($account);
      $user_group_roles = $membership instanceof GroupMembership ? $membership->getRoles() : [];
    }

    $user_group_roles = array_merge(
      $user_group_roles,
      $account->getRoles(TRUE)
    );

    $contributors = $node->get('field_related_contributors')
      ->referencedEntities();
    $contributors = array_filter($contributors, function (ParagraphInterface $paragraph) {
      return !empty($paragraph->get('field_user_ref')->referencedEntities());
    });

    $current_user = User::load(\Drupal::currentUser()->id());

    $users = array_map(function (ParagraphInterface $paragraph) {
      return $paragraph->get('field_user_ref')->referencedEntities()[0]->id();
    }, $contributors);

    $users = array_unique(array_values($users), SORT_NUMERIC);

    $contributors_data ['items'] = [];

    if (
      $node->getOwner() instanceof UserInterface &&
      (int) $node->getOwnerId() !== 0 &&
      !in_array($node->getOwnerId(), $users)
    ) {
      $contributors_data['items'][] = eic_community_get_teaser_user_display($node->getOwner());
    }

    $users = User::loadMultiple($users);

    foreach ($users as $user) {
      $contributors_data['items'][] = eic_community_get_teaser_user_display($user);
    }

    $group_contents = GroupContent::loadByEntity($node);

    /** @var \Drupal\media\MediaInterface|null $media_picture */
    $media_picture = $current_user->get('field_media')->referencedEntities();
    /** @var File|NULL $file */
    $file = $media_picture ? File::load($media_picture[0]->get('oe_media_image')->target_id) : NULL;
    $file_url = $file ? file_url_transform_relative(file_create_url($file->get('uri')->value)) : NULL;

    $group_id = $current_group_route ? $current_group_route->id() : 0;

    $build['#attached']['drupalSettings']['overview'] = [
      'is_group_owner' => array_key_exists(EICGroupsHelper::GROUP_OWNER_ROLE, $user_group_roles),
      'user' => [
        'avatar' => $file_url,
        'fullname' => $current_user instanceof UserInterface ?
          $current_user->get('field_first_name')->value . ' ' . $current_user->get('field_last_name')->value :
          '',
        'url' => $current_user instanceof UserInterface ?
          $current_user->toUrl()->toString() :
          '#',
      ],
      'group_roles' => $user_group_roles,
      'group_id' => $group_id,
      'permissions' => [
        'post_comment' =>
          $this->groupPermissionChecker->getPermissionInGroups(
            'post comments',
            $current_user,
            $group_contents
          )->isAllowed(),
        'edit_all_comments' => $this->groupPermissionChecker->getPermissionInGroups(
          'edit all comments',
          $current_user,
          $group_contents
        )->isAllowed(),
        'delete_all_comments' => UserHelper::isPowerUser($current_user),
        'edit_own_comments' => $this->groupPermissionChecker->getPermissionInGroups(
          'edit own comments',
          $current_user,
          $group_contents
        )->isAllowed(),
      ],
      'translations' => [
        'title' => $this->t('Comments', [], ['context' => 'eic_groups']),
        'no_results_title' => $this->t('We haven’t found any search results', [], ['context' => 'eic_group']),
        'no_results_body' => $this->t('Please try again with another keyword', [], ['context' => 'eic_group']),
        'load_more' => $this->t('Load more', [], ['context' => 'eic_groups']),
        'edit' => $this->t('Edit', [], ['context' => 'eic_groups']),
        'options' => $this->t('Options', [], ['context' => 'eic_groups']),
        'reply_to' => $this->t('Reply to', [], ['context' => 'eic_groups']),
        'in_reply_to' => $this->t('in reply to', [], ['context' => 'eic_groups']),
        'reply' => $this->t('Reply', [], ['context' => 'eic_groups']),
        'submit' => $this->t('Submit', [], ['context' => 'eic_groups']),
        'reason' => $this->t('Reason', [], ['context' => 'eic_groups']),
        'comment_placeholder' => $this->t('Type your message here...', [], ['context' => 'eic_groups']),
        'action_edit_comment' => $this->t('Edit comment', [], ['context' => 'eic_groups']),
        'action_delete_comment' => $this->t('Delete comment', [], ['context' => 'eic_groups']),
        'action_request_delete' => $this->t('Request deletion', [], ['context' => 'eic_groups']),
        'action_request_archival' => $this->t('Request archival', [], ['context' => 'eic_groups']),
      ],
    ];

    $group_id = $current_group_route ? $current_group_route->id() : 0;

    if (!$group_id) {
      \Drupal::logger('eic_groups')
        ->warning('No group found for comments block');

      return [];
    }

    return $build + [
        '#cache' => [
          'contexts' => [
            'url.path',
            'url.query_args',
            "user.is_group_member:$group_id",
            'user.group_permissions',
          ],
          'tags' => $node->getCacheTags(),
        ],
        '#theme' => 'eic_group_comments_from_discussion',
        '#discussion_id' => $node->id(),
        '#contributors' => $contributors_data,
        '#is_anonymous' => $current_user->isAnonymous(),
      ];
  }

}
