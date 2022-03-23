<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Core\Url;
use Drupal\eic_private_message\Constants\PrivateMessage;
use Drupal\group\GroupMembership;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorUser
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorUser extends DocumentProcessor {

  /**
   * @var GroupMembershipLoaderInterface $groupMembershipLoader
   */
  private $groupMembershipLoader;

  /**
   * @param GroupMembershipLoaderInterface $groupMembershipLoader
   */
  public function __construct(GroupMembershipLoaderInterface $groupMembershipLoader) {
    $this->groupMembershipLoader = $groupMembershipLoader;
  }

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $user = User::load($fields['its_user_id']);

    if (!$user instanceof UserInterface) {
      return;
    }

    $url_contact = Url::fromRoute(
      'eic_private_message.user_private_message',
      ['user' => $user->id()]
    )->toString();

    $this->addOrUpdateDocumentField(
      $document,
      'ss_user_link_contact',
      $fields,
      $url_contact
    );

    $this->addOrUpdateDocumentField(
      $document,
      'ss_user_allow_contact',
      $fields,
      $user->get(PrivateMessage::PRIVATE_MESSAGE_USER_ALLOW_CONTACT_ID)->value
    );

    $grp_ids = [];

    if (array_key_exists('its_user_profile', $fields)) {
      $profile = Profile::load($fields['its_user_profile']);
      if ($profile instanceof ProfileInterface) {
        $socials = $profile->get('field_social_links')->getValue();
        $document->addField('ss_profile_socials', json_encode($socials));
        $user = $profile->getOwner();

        /** @var \Drupal\group\GroupMembershipLoader $grp_membership_service */
        $grps = $this->groupMembershipLoader->loadByUser($user);

        $grp_ids = array_map(function (GroupMembership $grp_membership) {
          return $grp_membership->getGroup()->id();
        }, $grps);
      }
    }

    $document->setField('itm_user__group_content__uid_gid', $grp_ids);
  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    return $fields['ss_search_api_datasource'] === 'entity:user';
  }

}
