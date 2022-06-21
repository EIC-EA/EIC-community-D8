<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\group\GroupMembership;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an EICGroupOverviewMessageBlock block.
 *
 * @Block(
 *   id = "eic_group_overview_message",
 *   admin_label = @Translation("EIC Group Overview Messages"),
 *   category = @Translation("European Innovation Council"),
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group")
 *   }
 * )
 */
class EICGroupOverviewMessageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $account;

  /**
   * The content moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  private $moderationInformation;

  /**
   * The EIC group helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  private $groupHelper;

  /**
   * Constructs a new EICGroupOverviewMessageBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The content moderation information service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   * @param \Drupal\eic_groups\EICGroupsHelper $group_helper
   *   The EIC group helper service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    ModerationInformationInterface $moderation_information,
    AccountProxyInterface $account,
    EICGroupsHelper $group_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moderationInformation = $moderation_information;
    $this->account = $account;
    $this->groupHelper = $group_helper;
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
      $container->get('content_moderation.moderation_information'),
      $container->get('current_user'),
      $container->get('eic_groups.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    /** @var \Drupal\group\Entity\GroupInterface $group */
    if ((!$group = $this->getContextValue('group')) || !$this->account->isAuthenticated()) {
      return $build;
    }

    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->setCacheContexts([
      'user.group_permissions',
    ]);
    $cacheable_metadata->addCacheTags([
      'group_content_list:group:' . $group->id(),
    ]);

    $required_roles = [$group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_OWNER_ROLE];
    $group_membership = $group->getMember($this->account);
    $user_group_roles = $group_membership instanceof GroupMembership
      ? array_keys($group_membership->getRoles())
      : [];

    // This block is only shown to group owners.
    if (empty(array_intersect($user_group_roles, $required_roles))) {
      return $build;
    }

    $has_group_content = $this->groupHelper->hasContent($group);
    if ($this->moderationInformation->isModeratedEntity($group)) {
      $moderation_state = $group->get('moderation_state')->value;
      $content_operations[] = [
        'label' => $this->t('Post content'),
        'links' => $this->groupHelper->getGroupContentOperationLinks(
          $group,
          ['node'],
          $cacheable_metadata
        ),
      ];

      if (in_array($moderation_state, [
        GroupsModerationHelper::GROUP_DRAFT_STATE,
        GroupsModerationHelper::GROUP_PENDING_STATE,
        GroupsModerationHelper::GROUP_PUBLISHED_STATE,
      ])) {
        $build = [
          '#theme' => 'eic_group_moderated_message_box',
          '#group' => $group,
          '#edit_link' => $group->toUrl('edit-form')->toString(),
          '#delete_link' => $group->toUrl('delete-form')->toString(),
          '#invite_link' => Url::fromRoute('entity.group_content.add_form', [
            'group' => $group->id(),
            'plugin_id' => 'group_invitation',
          ])->toString(),
          '#has_content' => $has_group_content,
          '#has_member' => !empty($group->getMembers([
            EICGroupsHelper::GROUP_ADMINISTRATOR_ROLE,
            EICGroupsHelper::GROUP_MEMBER_ROLE,
          ])),
          '#actions' => $content_operations,
          '#help_link' => Url::fromRoute('contact.site_page')->toString(),
        ];
      }
    }

    $cacheable_metadata->applyTo($build);

    return $build;
  }

}
