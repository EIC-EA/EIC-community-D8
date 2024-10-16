<?php

namespace Drupal\eic_flags\Plugin\Flag;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\eic_flags\FlagHelper;
use Drupal\eic_flags\FlagType;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_moderation\ModerationHelper;
use Drupal\flag\Plugin\Flag\EntityFlagType as EntityFlagTypeBase;
use Drupal\flag\FlagInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * EntityFlagType custom implementation.
 */
class EntityFlagType extends EntityFlagTypeBase {

  /**
   * The EIC Flags helper service.
   *
   * @var \Drupal\eic_flags\FlagHelper
   */
  protected $flagHelper;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $groupsHelper;

  /**
   * The EIC Moderation helper service.
   *
   * @var \Drupal\eic_moderation\ModerationHelper
   */
  protected $moderationHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    TranslationInterface $string_translation,
    FlagHelper $eic_flag_helper,
    EICGroupsHelper $eic_groups_helper,
    ModerationHelper $moderation_helper
  ) {
    $this->entityType = $plugin_definition['entity_type'];
    $this->entityTypeManager = $entity_type_manager;
    $this->flagHelper = $eic_flag_helper;
    $this->groupsHelper = $eic_groups_helper;
    $this->moderationHelper = $moderation_helper;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $module_handler, $entity_type_manager, $string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('eic_flags.helper'),
      $container->get('eic_groups.helper'),
      $container->get('eic_moderation.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function actionAccess($action, FlagInterface $flag, AccountInterface $account, EntityInterface $flaggable = NULL) {
    // Deny access for unpublished entities.
    if ($flaggable) {
      switch ($flag->id()) {
        case FlagType::LIKE_CONTENT:
          if (!$this->moderationHelper->isPublished($flaggable)) {
            return AccessResult::forbidden()->addCacheContexts(['user'])->addCacheableDependency($flaggable);
          }
          break;

      }
    }

    // Highlight flags should be accessible by GO/GAs only, so we need to apply
    // custom logic here.
    if ($flag->id() == FlagType::HIGHLIGHT_CONTENT) {
      // Get the group from the flaggable, if any.
      $parent_group = NULL;
      if ($flaggable && !$flaggable->isNew()) {
        $parent_group = $this->groupsHelper->getOwnerGroupByEntity($flaggable);
      }
      if (
        $parent_group instanceof GroupInterface &&
        $this->flagHelper->canUserHighlight($account, $parent_group)
      ) {
        return AccessResult::allowed()->addCacheContexts(['user']);
      }
      else {
        return AccessResult::forbidden()->addCacheContexts(['user']);
      }
    }

    return parent::actionAccess($action, $flag, $account, $flaggable);
  }

}
