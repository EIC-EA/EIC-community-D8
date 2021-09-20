<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\group\GroupMembership;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\user\Entity\User;
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_groups.helper'),
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
    EICGroupsHelper $groups_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupsHelper = $groups_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var NodeInterface|NULL $node */
    $node = \Drupal::routeMatch()->getParameter('node');

    if (!$node instanceof NodeInterface || 'discussion' !== $node->bundle()) {
      return [];
    }

    $current_group_route = $this->groupsHelper->getGroupFromRoute();
    $user_group_roles = [];

    if ($current_group_route) {
      $account = \Drupal::currentUser();
      $membership = $current_group_route->getMember($account);
      $user_group_roles = $membership instanceof GroupMembership ? $membership->getRoles() : [];
    }

    $contributors = $node->get('field_related_contributors')->referencedEntities();
    $contributors = array_filter($contributors, function(ParagraphInterface $paragraph) {
      return !empty($paragraph->get('field_user_ref')->referencedEntities());
    });

    $users = array_map(function(ParagraphInterface $paragraph) {
      return $paragraph->get('field_user_ref')->referencedEntities()[0]->id();
    }, $contributors);

    $contributors_data ['items'] = [];
    $users = User::loadMultiple($users);

    foreach ($users as $user) {
      $contributors_data['items'][] = eic_community_get_teaser_user_display($user);
    }

    $build['#attached']['drupalSettings'] = [
      'is_group_owner' => array_key_exists(EICGroupsHelper::GROUP_OWNER_ROLE, $user_group_roles),
      'translations' => [
        'title' => $this->t('Comments', [], ['context' => 'eic_groups']),
        'no_results' => $this->t('There are currently no comments.', [], ['context' => 'eic_groups']),
        'load_more' => $this->t('Load more', [], ['context' => 'eic_groups']),
        'edit' => $this->t('Edit', [], ['context' => 'eic_groups']),
        'options' => $this->t('Options', [], ['context' => 'eic_groups']),
        'reply_to' => $this->t('Reply to', [], ['context' => 'eic_groups']),
        'in_reply_to' => $this->t('In reply to', [], ['context' => 'eic_groups']),
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

    return $build + [
        '#theme' => 'eic_group_comments_from_discussion',
        '#discussion_id' => $node->id(),
        '#contributors' => $contributors_data,
      ];
  }

}
