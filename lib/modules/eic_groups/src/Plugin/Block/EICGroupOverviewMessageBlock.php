<?php

namespace Drupal\eic_groups\Plugin\Block;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\group\GroupMembership;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an EICGroupOverviewMessageBlock block.
 *
 * @Block(
 *   id = "eic_overview_message",
 *   admin_label = @Translation("EIC Overview Messages"),
 *   category = @Translation("European Innovation Council"),
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group")
 *   }
 * )
 */
class EICGroupOverviewMessageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $account;

  /**
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  private $moderationInformation;

  /**
   * EICGroupOverviewMessageBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModerationInformationInterface $moderation_information,
    AccountProxyInterface $account
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moderationInformation = $moderation_information;
    $this->account = $account;
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
      $container->get('current_user')
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

    $required_roles = [EICGroupsHelper::GROUP_OWNER_ROLE];
    $group_membership = $group->getMember($this->account);
    $user_group_roles = $group_membership instanceof GroupMembership
      ? array_keys($group_membership->getRoles())
      : [];

    // This block is only shown to group admins.
    if (empty(array_intersect($user_group_roles, $required_roles))) {
      return $build;
    }

    $has_group_content = FALSE;
    if (!$has_group_content && $group->isPublished()) {
      //TODO return the published without content box
      return $build;
    }

    if ($this->moderationInformation->isModeratedEntity($group) && !$group->isPublished()) {
      $moderation_state = $group->get('moderation_state')->value;
      if ($moderation_state === GroupsModerationHelper::GROUP_PENDING_STATE
        || $moderation_state === GroupsModerationHelper::GROUP_DRAFT_STATE
      ) {
        $build = [
          '#theme' => 'eic_group_moderated_message_box',
          '#group' => $group,
          '#edit_link' => $group->toUrl('edit-form'),
          '#delete_link' => $group->toUrl('delete-form'),
        ];
      }
    }

    $cacheable_metadata->applyTo($build);

    return $build;
  }

}
