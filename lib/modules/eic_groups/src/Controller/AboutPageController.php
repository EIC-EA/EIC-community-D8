<?php

namespace Drupal\eic_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\GroupVisibilityRecord;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route response for the About page.
 */
class AboutPageController extends ControllerBase {

  /**
   * The EIC User helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $userHelper;

  /**
   * The OEC Group Flex helper service.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * Constructs a new AboutPageController object.
   *
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The EIC User helper service.
   * @param \Drupal\oec_group_flex\OECGroupFlexHelper $oec_group_flex_helper
   *   The OEC Group Flex helper service.
   */
  public function __construct(UserHelper $user_helper, OECGroupFlexHelper $oec_group_flex_helper) {
    $this->userHelper = $user_helper;
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_user.helper'),
      $container->get('oec_group_flex.helper')
    );
  }

  /**
   * Builds the homepage title.
   */
  public function title(GroupInterface $group) {
    return $this->t('@group_name - About', ['@group_name' => $group->label()]);
  }

  /**
   * Returns the About page for a given group.
   *
   * @return array
   *   A simple renderable array.
   */
  public function build(GroupInterface $group) {
    // Initialise variables.
    $variables['owners'] = [];
    $variables['admins'] = [];
    $variables['regions_countries'] = [];
    $variables['description'] = '';

    // Get group owners.
    foreach ($group->getMembers('group-owner') as $item) {
      $variables['owners'][] = $this->userHelper->getUserLink($item->getUser());
    }
    // Get group admins.
    foreach ($group->getMembers('group-admin') as $item) {
      $variables['admins'][] = $this->userHelper->getUserLink($item->getUser());
    }
    // Get group topics.
    foreach ($group->get('field_vocab_topics')->referencedEntities() as $item) {
      $variables['topics'][] = $item->label();
    }
    // Get group regions and countries.
    foreach ($group->get('field_vocab_geo')->referencedEntities() as $item) {
      $variables['regions_countries'][] = $item->label();
    }
    $variables['description'] = $group->get('field_body')->value;
    // Get group visibility.
    $variables['visibility'] = $this->oecGroupFlexHelper->getGroupVisibilitySettings($group);
    if (!empty($variables['visibility']['settings']) && $variables['visibility']['settings'] instanceof GroupVisibilityRecord) {
      $variables['visibility']['settings'] = $this->oecGroupFlexHelper->getGroupVisibilityRecordSettings($variables['visibility']['settings']);
    }
    // Get joining methods.
    $variables['joining_methods'] = $this->oecGroupFlexHelper->getGroupJoiningMethod($group);

    // Get the descriptions for each plugin.
    $variables['visibility']['description'] = $this->getPluginDescription('visibility', $variables['visibility']['plugin_id']);
    foreach ($variables['joining_methods'] as $index => $joining_method) {
      $variables['joining_methods'][$index]['description'] = $this->getPluginDescription('joining_method', $joining_method['plugin_id']);
    }

    return $variables;
  }

  /**
   * Returns a custom description for the given plugin type and plugin ID.
   *
   * @param string $plugin_type
   *   The plugin type set by the build() method of this class.
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The description for the given plugin.
   */
  public function getPluginDescription(string $plugin_type, string $plugin_id) {
    $key = "$plugin_type-$plugin_id";

    switch ($key) {
      case 'visibility-public':
        return $this->t("This group is visible to everyone visiting the group. You're welcome to scroll through the group's content. If you want to participate, please become a group member.");

      case 'visibility-restricted_community_members':
        return $this->t("This group is visible to every person that is a member of the EIC Community and has joined this platform. You're welcome to scroll through the group's content. If you want to participate, please become a group member.");

      case 'visibility-custom_restricted':
        return $this->t('This group is visible to every person that has joined the EIC community that also complies with the following restrictions. You can see this group because the organisation you work for is allowed to see this content or the group owners and administrators have chosen to specifically grant you access to this group. If you want to participate, please become a group member.');

      case 'visibility-private':
        return $this->t('A private group is only visible to people who received an invitation via email and accepted it. No one else can see this group.');

      case 'joining_method-tu_open_method':
        return $this->t('This means that EIC Community members can join this group immediately by clicking "join group".');

      case 'joining_method-tu_group_membership_request':
        return $this->t('This means that EIC Community members can request to join this group. This request needs to be validated by the group owner or administrator.');

      default:
        return '';
    }
  }

}
