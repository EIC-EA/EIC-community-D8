<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\group\Entity\Group;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorVisibility
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorVisibility extends DocumentProcessor {

  /**
   * @var OECGroupFlexHelper $OECGroupFlexHelper
   */
  private $OECGroupFlexHelper;

  /**
   * @var \Drupal\eic_groups\EICGroupsHelperInterface $groupsHelper
   */
  private $groupsHelper;

  /**
   * @param \Drupal\oec_group_flex\OECGroupFlexHelper $OECGroupFlexHelper
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $groupsHelper
   */
  public function __construct(OECGroupFlexHelper $OECGroupFlexHelper, EICGroupsHelperInterface $groupsHelper) {
    $this->OECGroupFlexHelper = $OECGroupFlexHelper;
    $this->groupsHelper = $groupsHelper;
  }

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $search_id = array_key_exists('ss_search_api_id', $fields) ?
      $fields['ss_search_api_id'] :
      NULL;

    // By default we will add the visibility to "public" to every entity.
    // Even if it's not linked to group but we need to put it public
    // otherwise solr will not be able to reach content without this property.
    $group_visibility = GroupVisibilityType::GROUP_VISIBILITY_PUBLIC;

    if (!$search_id) {
      $document->addField('ss_group_visibility', $group_visibility);

      $document->addField(
        'ss_group_visibility_label',
        GroupVisibilityType::GROUP_VISIBILITY_PUBLIC
      );
      return;
    }

    /** @var \Drupal\search_api\Item\ItemInterface $item */
    $item = array_key_exists($search_id, $items) ? $items[$search_id] : NULL;
    if (!$item) {
      $document->addField('ss_group_visibility', $group_visibility);

      $document->addField(
        'ss_group_visibility_label',
        GroupVisibilityType::GROUP_VISIBILITY_PUBLIC
      );
      return;
    }

    $original_object = $item->getOriginalObject()->getEntity();

    $group = $this->groupsHelper->getOwnerGroupByEntity($original_object);

    if (!$group) {
      $document->addField('ss_group_visibility', $group_visibility);

      $document->addField(
        'ss_group_visibility_label',
        GroupVisibilityType::GROUP_VISIBILITY_PUBLIC
      );
      return;
    }

    $document->addField(
      'ss_group_visibility_label',
      $this->OECGroupFlexHelper->getGroupVisibilityTagLabel($group)
    );

    /** @var \Drupal\oec_group_flex\GroupVisibilityDatabaseStorage $group_visibility_storage */
    $group_visibility_storage = \Drupal::service('oec_group_flex.group_visibility.storage');
    $group_visibility_entity = $group_visibility_storage->load($group->id());
    $visibility_type = $group_visibility_entity ?
      $group_visibility_entity->getType() :
      NULL;

    switch ($visibility_type) {
      case GroupVisibilityType::GROUP_VISIBILITY_PRIVATE:
      case GroupVisibilityType::GROUP_VISIBILITY_COMMUNITY:
        $group_visibility = $group_visibility_entity->getType();
        break;

      // In this case, when we have a custom restriction, we can have multiple restriction options like email domain, trusted users, organisation, ...
      case GroupVisibilityType::GROUP_VISIBILITY_CUSTOM_RESTRICTED:
        $options = $group_visibility_entity->getOptions();
        foreach ($options as $key => $option) {
          // restricted_email_domains_status can be false so we need to check if enable
          if (GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN === $key && $option[GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN . '_status']) {
            $group_visibility = GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN;

            // When it's a email domain restriction we need to add a new value to solr document so we can filter on that
            $document->addField(
              'ss_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN,
              $option[GroupVisibilityType::GROUP_VISIBILITY_OPTION_EMAIL_DOMAIN . '_conf']
            );
          }

          // @TODO need the trusted users working on group entity before
          if (GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS === $key && $option[GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS . '_status']) {
            $group_visibility = GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS;

            $user_ids = $option[GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS . '_conf'];
            $users = array_map(function ($user_id) {
              $user = User::load(reset($user_id));
              if (!$user) {
                return -1;
              }

              // @todo Make use of user ID only.
              return $user->id() . '|' . $user->getAccountName();
            }, $user_ids);

            $document->addField(
              'ss_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS,
              implode(',', $users)
            );
          }

          if (GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATIONS === $key && $option[GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATIONS . '_status']) {
            $group_visibility = GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATIONS;

            $organisation_ids = $option[GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATIONS . '_conf'];
            $organisations = array_map(function ($organisation_id) {
              $organisation = Group::load(reset($organisation_id));
              if (!$organisation) {
                return -1;
              }

              return $organisation->id();
            }, $organisation_ids);

            $document->addField('itm_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATIONS, $organisations);
          }

          if (GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATION_TYPES === $key && $option[GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATION_TYPES . '_status']) {
            $group_visibility = GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATION_TYPES;

            $term_ids = $option[GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATION_TYPES . '_conf'];
            $terms = array_map(function ($term_id) {
              if (!empty($term_id)) {
                $term = Term::load($term_id);
                if (!$term) {
                  return -1;
                }

                return $term->id();
              }
            }, $term_ids);

            $document->addField('itm_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_ORGANISATION_TYPES, $terms);
          }
        }
        break;

      default:
        $group_visibility = GroupVisibilityType::GROUP_VISIBILITY_PUBLIC;
        break;

    }

    $document->addField('ss_group_visibility', $group_visibility);
    $this->setGroupOwner($document, 'its_group_owner_id', $group);
  }
}
