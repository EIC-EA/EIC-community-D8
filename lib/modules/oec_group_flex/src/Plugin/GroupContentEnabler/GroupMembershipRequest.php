<?php

namespace Drupal\oec_group_flex\Plugin\GroupContentEnabler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest as GroupMembershipRequestBase;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides plugin 'group_membership_request'.
 */
class GroupMembershipRequest extends GroupMembershipRequestBase implements ContainerFactoryPluginInterface {

  /**
   * The OEC Group Flex Helper.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    OECGroupFlexHelper $oec_group_flex_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('oec_group_flex.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $operations = [];
    $url = $group->toUrl('group-request-membership');
    if(
      $url->access($account) &&
      !$this->oecGroupFlexHelper->getMembershipRequest($account, $group)
    ){
      $operations['group-request-membership'] = [
        'title' => $this->t('Request group membership'),
        'url' => $url,
        'weight' => 99,
      ];
    }

    return $operations;
  }

}
