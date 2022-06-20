<?php

namespace Drupal\eic_admin\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\eic_content\Plugin\Field\FieldWidget\EntityTreeWidget;
use Drupal\eic_groups\Constants\GroupJoiningMethodType;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupType;
use Drupal\group\GroupRoleSynchronizer;
use Drupal\group_flex\GroupFlexGroupSaver;
use Drupal\group_flex\GroupFlexGroupType;
use Drupal\group_permissions\GroupPermissionsManagerInterface;
use Drupal\oec_group_features\GroupFeatureHelper;
use Drupal\oec_group_features\GroupFeaturePluginManager;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateAllGroupPermissionsForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface $em
   */
  private EntityTypeManagerInterface $em;

  /**
   * The group permissions manager.
   *
   * @var GroupPermissionsManagerInterface $permissionsManager
   */
  private GroupPermissionsManagerInterface $permissionsManager;

  /**
   * The datetime time service.
   *
   * @var TimeInterface $datetime
   */
  private TimeInterface $datetime;

  /**
   * The Group flex saver service.
   *
   * @var \Drupal\group_flex\GroupFlexGroupSaver
   */
  protected $groupFlexSaver;

  /**
   * The OEC group flex helper service.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * The group role synchronizer.
   *
   * @var \Drupal\group\GroupRoleSynchronizer
   */
  protected $groupRoleSynchronizer;

  /**
   * The OEC group feature helper service.
   *
   * @var \Drupal\oec_group_features\GroupFeatureHelper
   */
  protected $groupFeatureHelper;

  /**
   * The Group feature plugin manager.
   *
   * @var \Drupal\oec_group_features\GroupFeaturePluginManager
   */
  protected $groupFeaturePluginManager;

  /**
   * The group type flex service.
   *
   * @var \Drupal\group_flex\GroupFlexGroupType
   */
  protected $groupTypeFlex;

  /**
   * UpdateAllGroupPermissionsForm constructor.
   *
   * @param EntityTypeManagerInterface $em
   *   The entity type manager.
   * @param GroupPermissionsManagerInterface $permissions_manager
   *   The group permission manager.
   * @param TimeInterface $datetime
   *   The datetime time service.
   * @param \Drupal\group_flex\GroupFlexGroupSaver $group_flex_saver
   *   The Group flex saver service.
   * @param \Drupal\oec_group_flex\OECGroupFlexHelper $group_flex_saver
   *   The OEC group flex helper service.
   * @param \Drupal\group\GroupRoleSynchronizer $groupRoleSynchronizer
   *   The group role synchronizer.
   * @param \Drupal\oec_group_features\GroupFeatureHelper $oec_group_features_helper
   *   The OEC group feature helper service.
   * @param \Drupal\oec_group_features\GroupFeaturePluginManager $group_feature_plugin_manager
   *   The Group feature plugin manager.
   * @param \Drupal\group_flex\GroupFlexGroupType $group_type_flex
   *   The group type flex service.
   */
  public function __construct(
    EntityTypeManagerInterface $em,
    GroupPermissionsManagerInterface $permissions_manager,
    TimeInterface $datetime,
    GroupFlexGroupSaver $group_flex_saver,
    OECGroupFlexHelper $oec_group_flex_helper,
    GroupRoleSynchronizer $group_role_synchronizer,
    GroupFeatureHelper $oec_group_features_helper,
    GroupFeaturePluginManager $group_feature_plugin_manager,
    GroupFlexGroupType $group_type_flex
  ) {
    $this->em = $em;
    $this->permissionsManager = $permissions_manager;
    $this->datetime = $datetime;
    $this->groupFlexSaver = $group_flex_saver;
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
    $this->groupRoleSynchronizer = $group_role_synchronizer;
    $this->groupFeatureHelper = $oec_group_features_helper;
    $this->groupFeaturePluginManager = $group_feature_plugin_manager;
    $this->groupTypeFlex = $group_type_flex;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('group_permission.group_permissions_manager'),
      $container->get('datetime.time'),
      $container->get('group_flex.group_saver'),
      $container->get('oec_group_flex.helper'),
      $container->get('group_role.synchronizer'),
      $container->get('oec_group_features.helper'),
      $container->get('plugin.manager.group_feature'),
      $container->get('group_flex.group_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eic_admin_update_all_group_perissions_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [
      'match_top_level_limit' => 0,
      'items_to_load' => 0,
      'auto_select_parents' => 0,
      'disable_top_choices' => 0,
      'load_all' => 1,
      'ignore_current_user' => 0,
      'target_bundles' => ['organisation', 'group', 'event'],
      'is_required' => 0,
    ];

    $form['groups'] = EntityTreeWidget::getEntityTreeFieldStructure(
      [],
      'group',
      '',
      0,
      Url::fromRoute('eic_content.entity_tree')->toString(),
      Url::fromRoute('eic_content.entity_tree_search')->toString(),
      Url::fromRoute('eic_content.entity_tree_children')->toString(),
      $options
    );

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rebuild permissions', [], ['context' => 'eic_admin']),
      '#attributes' => [
        'class' => [
          'ecl-button',
          'ecl-button--search',
          'ecl-search-form__button',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $groups = $form_state->getValue('groups');
    $this->updatePermissionGroupType($groups);
  }

  /**
   * Update all groups depending permissions from their group type.
   *
   * @param \Drupal\group\Entity\GroupInterface[] $groups
   *   The group type id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function updatePermissionGroupType(?array $groups_id) {
    // If empty groups field, update all groups.
    if (empty($groups_id)) {
      $groups = $this->em->getStorage('group')->loadMultiple();
    }
    else {
      $groups = Group::loadMultiple(
        array_map(function ($group_id) {
          return $group_id['target_id'];
        }, $groups_id)
      );
    }

    foreach ($groups as $group) {
      if (!$group instanceof GroupInterface) {
        continue;
      }

      /** @var GroupType $group_type */
      $group_type = $group->getGroupType();
      $type_roles = $group_type->getRoles();
      $groupPermissions = $this->permissionsManager->loadByGroup($group);

      if (!$groupPermissions) {
        continue;
      }

      $tuGroupRoleId = $this->groupRoleSynchronizer->getGroupRoleId($group_type->id(), UserHelper::ROLE_TRUSTED_USER);

      // Default joining method.
      $group_joining_method = [
        GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_OPEN => GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_OPEN
      ];

      $permissions = $groupPermissions->getPermissions();
      foreach ($type_roles as $type_role) {
        // Sets joining method depending on the permissions for Trusted users.
        if ($type_role->id() === $tuGroupRoleId) {
          if (in_array('join group', $permissions[$tuGroupRoleId])) {
            $group_joining_method = [
              GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_OPEN => GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_OPEN
            ];
          }
          elseif (in_array('request group membership', $permissions[$tuGroupRoleId])) {
            $group_joining_method = [
              GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_MEMBERSHIP_REQUEST => GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_MEMBERSHIP_REQUEST
            ];
          }
        }
        $permissions[$type_role->id()] = $type_role->getPermissions();
      }

      // Updates permissions based on the group type permissions.
      $groupPermissions->setPermissions($permissions);

      $violations = $groupPermissions->validate();

      if (count($violations) > 0) {
        $message = '';
        foreach ($violations as $violation) {
          $message .= "\n" . $violation->getMessage();
        }
        $this->messenger()->addMessage('Group permissions are not saved correctly, because:' . $message);
        continue;
      }

      // Saves the GroupPermission object with a new revision.
      $groupPermissions->setNewRevision();
      $groupPermissions->setRevisionUserId(1);
      $groupPermissions->setRevisionCreationTime($this->datetime->getRequestTime());
      $groupPermissions->setRevisionLogMessage('Group features enabled/disabled.');
      $groupPermissions->save();

      // Saves group visibilit + joining methods if flex feature is enabled for
      // the group.
      if ($this->groupTypeFlex->hasFlexEnabled($group_type)) {
        $group_visibility = $this->oecGroupFlexHelper->getGroupVisibilitySettings($group);
        // Saves group visibility.
        $this->groupFlexSaver->saveGroupVisibility(
          $group,
          $group_visibility['plugin_id'],
          !empty($group_visibility['settings']) ? $group_visibility['settings'] : []
        );

        // Saves group joining methods.
        $this->groupFlexSaver->saveGroupJoiningMethods(
          $group,
          $group_joining_method
        );
      }

      // Get all available features for this group type.
      $available_features = [];
      foreach ($this->groupFeatureHelper->getGroupTypeAvailableFeatures($group_type->id()) as $plugin_id => $label) {
        try {
          $available_features[$plugin_id] = $this->groupFeaturePluginManager->createInstance($plugin_id);
        }
        catch (PluginException $e) {
          $logger = $this->getLogger('oec_group_features');
          $logger->error($e->getMessage());
        }
      }

      // Get group enabled features.
      $enabled_features = [];
      foreach ($group->get(GroupFeatureHelper::FEATURES_FIELD_NAME)->getValue() as $feature) {
        $enabled_features[$feature['value']] = $feature['value'];
      }

      // Enable features that are selected for this group.
      foreach (array_intersect_key($available_features, $enabled_features) as $feature_key => $enabled_feature) {
        $available_features[$feature_key]->enable($group);
      }

      // Disable features that are not selected for this group.
      foreach (array_diff_key($available_features, $enabled_features) as $feature_key => $disabled_feature) {
        $available_features[$feature_key]->disable($group);
      }

      $this->messenger()->addMessage(
        $this->t(
          'Group @name permissions has been updated.',
          ['@name' => $group->label()],
          ['context' => 'eic_admin']
        )
      );
    }
  }

}
