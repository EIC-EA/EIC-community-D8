<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\comment\CommentInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\eic_comments\Constants\Comments;
use Drupal\eic_private_message\PrivateMessageHelper;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupRole;
use Drupal\group\GroupMembership;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Provides a processor for user entity.
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorUser extends DocumentProcessor {

  /**
   * The Group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  private $groupMembershipLoader;

  /**
   * The EIC User helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  private $userHelper;

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * The private message helper service.
   *
   * @var \Drupal\eic_private_message\PrivateMessageHelper
   */
  private $privateMessageHelper;

  /**
   * The URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  private $urlGenerator;

  /**
   * Constructs a new ProcessorUser object.
   *
   * @param \Drupal\group\GroupMembershipLoaderInterface $groupMembershipLoader
   *   The Group membership loader.
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The EIC User helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Database connection service.
   * @param \Drupal\eic_private_message\PrivateMessageHelper $private_message_helper
   *   The private message helper service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $url_generator
   *   The URL generator service.
   */
  public function __construct(
    GroupMembershipLoaderInterface $groupMembershipLoader,
    UserHelper $user_helper,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection,
    PrivateMessageHelper $private_message_helper,
    FileUrlGeneratorInterface $url_generator
  ) {
    $this->groupMembershipLoader = $groupMembershipLoader;
    $this->userHelper = $user_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->privateMessageHelper = $private_message_helper;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $user = $this->entityTypeManager->getStorage('user')
      ->load($fields['its_user_id']);

    if (!$user instanceof UserInterface) {
      return;
    }

    $this->calculateMostActive($user, $document, $fields);

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
      (int) $this->privateMessageHelper->userHasPrivateMessageEnabled($user)
    );

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

        $document->setField('itm_user__group_content__uid_gid', $grp_ids);
      }
    }

    $user_picture_uri = array_key_exists('ss_user_profile_image_uri', $fields) ?
      $fields['ss_user_profile_image_uri'] :
      NULL;

    $teaser_relative = '';

    // Generates image style for the user picture.
    if ($user_picture_uri) {
      /** @var \Drupal\image\Entity\ImageStyle $image_style */
      $image_style = $this->entityTypeManager->getStorage('image_style')
        ->load('crop_80x80');
      $teaser_relative = $this->urlGenerator->transformRelative($image_style->buildUrl($user_picture_uri));
    }

    $this->addOrUpdateDocumentField(
      $document,
      'ss_user_formatted_image',
      $fields,
      $teaser_relative
    );
  }

  /**
   * {@inheritdoc}
   */
  public function supports(array $fields): bool {
    return $fields['ss_search_api_datasource'] === 'entity:user';
  }

  /**
   * Calculate the most active score.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param \Solarium\QueryType\Update\Query\Document $document
   *   The SOLR document.
   * @param array $fields
   *   Array of SOLR document fields.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function calculateMostActive(UserInterface $user, Document $document, array $fields) {
    $total_groups = 0;
    $total_events = 0;

    $comment_storage = $this->entityTypeManager->getStorage('comment');
    $query = $comment_storage->getQuery();
    $query->condition('comment_type', Comments::DEFAULT_NODE_COMMENTS_TYPE);
    $query->condition('status', CommentInterface::PUBLISHED);
    $query->condition('uid', $user->id());
    $total_comments = (int) $query->count()->execute();

    $total_followers = $this->userHelper->getUserFollowers($user);

    // Query to count number of members.
    $query_content = $this->connection->select('group_content_field_data', 'gc_fd')
      ->fields('gc_fd', ['uid'])
      ->condition('gc_fd.uid', $user->id())
      ->condition('gc_fd.type', '%group_node%', 'LIKE');
    $query_content->addExpression('COUNT(gc_fd.entity_id)', 'count');
    $total_content = (int) $query_content->execute()->fetchAssoc()['count'];

    $memberships = $this->groupMembershipLoader->loadByUser($user);
    $group_ids = array_unique(array_map(function (GroupMembership $membership) {
      return $membership->getGroup()->id();
    }, $memberships));

    $this->addOrUpdateDocumentField($document, 'itm_user_group_ids', $fields, $group_ids);

    foreach ($memberships as $membership) {
      $group = $membership->getGroup();

      $query_content = $this->connection->select('group_content_field_data', 'gc_fd')
        ->fields('gc_fd', ['uid'])
        ->condition('gc_fd.uid', $user->id())
        ->condition('gc_fd.gid', $group->id())
        ->condition('gc_fd.type', '%group_node%', 'LIKE');
      $query_content->addExpression('COUNT(gc_fd.entity_id)', 'count');
      $total_group_content = (int) $query_content->execute()->fetchAssoc()['count'];

      $query_comments = $this->connection->select('comment_field_data', 'cfd')
        ->fields('gcfd', ['entity_id'])
        ->condition('gcfd.type', '%group_node%', 'LIKE')
        ->condition('gcfd.gid', $group->id());
      $query_comments->join('group_content_field_data', 'gcfd', 'cfd.entity_id = gcfd.entity_id');
      $total_group_comments = (int) $query_comments->countQuery()->execute()->fetchAssoc()['expression'];

      $most_active_total = 3 * $total_followers + 2 * $total_group_content + 2 * $total_group_comments + $total_groups + $total_events;
      $this->addOrUpdateDocumentField($document, self::SOLR_MOST_ACTIVE_ID_GROUP . $group->id(), $fields, $most_active_total);
      $roles = array_map(function (GroupRole $group_role) {
        return $group_role->label();
      }, $membership->getRoles());

      $this->addOrUpdateDocumentField($document, self::SOLR_GROUP_ROLES . $group->id(), $fields, array_values($roles));

      switch ($group->bundle()) {
        case 'event':
          $total_events += 1;
          break;

        case 'group':
          $total_groups += 1;
          break;
      }
    }

    $most_active_total = 3 * $total_followers + 2 * $total_content + 2 * $total_comments + $total_groups + $total_events;
    $this->addOrUpdateDocumentField($document, self::SOLR_MOST_ACTIVE_ID, $fields, $most_active_total);
  }

}
