<?php

namespace Drupal\eic_search\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManager;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Url;
use Drupal\eic_comments\CommentsHelper;
use Drupal\eic_events\Constants\Event;
use Drupal\eic_flags\FlagType;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_media_statistics\EntityFileDownloadCount;
use Drupal\eic_private_message\Constants\PrivateMessage;
use Drupal\eic_search\Search\Sources\GroupEventSourceType;
use Drupal\eic_search\SolrIndexes;
use Drupal\eic_user\UserHelper;
use Drupal\file\Entity\File;
use Drupal\flag\FlagCountManager;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Drupal\group\GroupMembership;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\PostRequestIndexing;
use Drupal\search_api\Utility\Utility;
use Drupal\statistics\NodeStatisticsDatabaseStorage;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Solarium\Core\Query\DocumentInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class SolrDocumentProcessor
 *
 * @package Drupal\eic_search\Service
 *
 * @TODO Split this long class.
 */
class SolrDocumentProcessor {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * The flag count manager.
   *
   * @var \Drupal\flag\FlagCountManager
   */
  private $flagCountManager;

  /**
   * The Search API Post request indexing service.
   *
   * @var \Drupal\search_api\Utility\PostRequestIndexing
   */
  private $postRequestIndexing;

  /**
   * The Entity file download count service.
   *
   * @var \Drupal\statistics\NodeStatisticsDatabaseStorage
   */
  private $nodeStatisticsDatabaseStorage;

  /**
   * @var \Drupal\eic_comments\CommentsHelper $commentsHelper
   */
  private $commentsHelper;

  /**
   * @var EntityFileDownloadCount $entityDownloadHelper
   */
  private $entityDownloadHelper;

  /**
   * @var OECGroupFlexHelper $OECGroupFlexHelper
   */
  private $OECGroupFlexHelper;

  /**
   * The Queue Factory service.
   *
   * @var QueueFactory $queueFactory
   */
  private $queueFactory;

  /**
   * The Queue Worker Manager service.
   *
   * @var QueueWorkerManager $queueManager
   */
  private $queueManager;

  /**
   * The Entity Type Manager service.
   *
   * @var EntityTypeManagerInterface $entityTypeManager
   */
  private $entityTypeManager;

  /**
   * The key used to identify solr document fields for last flagged.
   *
   * @var string
   */
  const LAST_FLAGGED_KEY = 'last_flagged';

  /**
   * SolrDocumentProcessor constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The current active database's master connection.
   * @param \Drupal\flag\FlagCountManager $flag_count_manager
   *   The flag count manager.
   * @param \Drupal\search_api\Utility\PostRequestIndexing $post_request_indexing
   *   The Search API Post request indexing service.
   * @param CommentsHelper $comments_helper
   *   The Comments Helper service.
   * @param EntityFileDownloadCount $entity_download_helper
   *   The Entity File Download Count service helper.
   * @param \Drupal\oec_group_flex\OECGroupFlexHelper $oec_group_flex_helper
   * @param QueueFactory $queue_factory
   *   The Queue Factory service.
   * @param QueueWorkerManager $queue_worker_manager
   *   The Queue Worker Manager service.
   */
  public function __construct(
    Connection $connection,
    FlagCountManager $flag_count_manager,
    PostRequestIndexing $post_request_indexing,
    NodeStatisticsDatabaseStorage $node_statistics_db_storage,
    CommentsHelper $comments_helper,
    EntityFileDownloadCount $entity_download_helper,
    OECGroupFlexHelper $oec_group_flex_helper,
    QueueFactory $queue_factory,
    QueueWorkerManager $queue_worker_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->connection = $connection;
    $this->flagCountManager = $flag_count_manager;
    $this->postRequestIndexing = $post_request_indexing;
    $this->nodeStatisticsDatabaseStorage = $node_statistics_db_storage;
    $this->commentsHelper = $comments_helper;
    $this->entityDownloadHelper = $entity_download_helper;
    $this->OECGroupFlexHelper = $oec_group_flex_helper;
    $this->queueFactory = $queue_factory;
    $this->queueManager = $queue_worker_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Set global fields data, gallery slides data and set by default content to
   * not private
   *
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param array $fields
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function processGlobalData(Document &$document, array $fields) {
    $title = '';
    $type = '';
    $type_label = '';
    $date = '';
    $status = FALSE;
    $fullname = '';
    $topics = [];
    $geo = [];
    $user_url = '';
    $datasource = $fields['ss_search_api_datasource'];
    $changed = 0;
    $language = t('English', [], ['context' => 'eic_search'])->render();

    switch ($datasource) {
      case 'entity:node':
        $title = $fields['ss_content_title'];
        $type = $fields['ss_content_type'];
        $node_type = NodeType::load($type);
        $type_label = $node_type instanceof NodeTypeInterface ?
          $node_type->label() :
          $fields['ss_content_type'];
        $date = $fields['ds_content_created'];
        $changed = $fields['ds_changed'];
        $status = $fields['bs_content_status'];
        $fullname = array_key_exists('ss_content_first_name', $fields) && array_key_exists('ss_content_last_name', $fields) ?
          $fields['ss_content_first_name'] . ' ' . $fields['ss_content_last_name'] :
          t('No name', [], ['context' => 'eic_search']);
        $topics = array_key_exists('sm_content_field_vocab_topics_string', $fields) ?
          $fields['sm_content_field_vocab_topics_string'] :
          [];
        $geo = array_key_exists('sm_content_field_vocab_geo_string', $fields) ?
          $fields['sm_content_field_vocab_geo_string'] :
          [];
        $language = array_key_exists('ss_content_language_string', $fields) ?
          $fields['ss_content_language_string'] :
          t('English', [], ['context' => 'eic_search'])->render();
        $user_url = '';
        if (array_key_exists('its_content_uid', $fields)) {
          $user = User::load($fields['its_content_uid']);
          $user_url = $user instanceof UserInterface ? $user->toUrl()
            ->toString() : '';
        }
        break;
      case 'entity:group':
        if (array_key_exists('ss_group_topic_name', $fields)) {
          $topics = $fields['ss_group_topic_name'];
        } else if (array_key_exists('sm_group_topic_name', $fields)) {
          $topics = $fields['sm_group_topic_name'];
        }

        $title = $fields['tm_X3b_en_group_label_fulltext'];
        $type = $fields['ss_group_type'];
        $date = $fields['ds_group_created'];
        $status = $fields['bs_group_status'];
        $geo = $fields['ss_group_field_vocab_geo_string'] ?? '';
        $language = t('English', [], ['context' => 'eic_search'])->render();
        $user_url = '';
        $group_id = $fields['its_group_id_integer'] ?? -1;
        $this->addOrUpdateDocumentField(
          $document,
          'its_group_id_integer',
          $fields,
          $group_id
        );
        $document->addField('ss_global_group_parent_id', $group_id);
        $group = Group::load($group_id);
        if ($group && $owner = EICGroupsHelper::getGroupOwner($group)) {
          $fullname = realname_load($owner);

          $this->addOrUpdateDocumentField(
            $document,
            'ss_group_user_image',
            $fields,
            UserHelper::getUserAvatar($owner)
          );
        }
        break;
      case 'entity:message':
        $user_url = '';
        if (array_key_exists('its_uid', $fields)) {
          $user = User::load($fields['its_uid']);
          $user_url = $user instanceof UserInterface ? $user->toUrl()
            ->toString() : '';
        }
        $status = TRUE;
        break;
      case 'entity:user':
        $user = User::load($fields['its_user_id']);
        $fullname = realname_load($user);
        $status = TRUE;
        break;
    }

    if ('gallery' === $type) {
      $slides_id = $fields['sm_content_gallery_slide_id_array'] ?: [];
      $slides_id = is_array($slides_id) ? $slides_id : [$slides_id];
      $image_style = ImageStyle::load('crop_50x50');
      $image_style_160 = ImageStyle::load('gallery_teaser_crop_160x160');
      $slides = array_map(function ($slide_id) use ($image_style, $image_style_160) {
        $slide = Paragraph::load($slide_id);
        $media = $slide->get('field_gallery_slide_media')->referencedEntities();

        if (empty($media)) {
          return [];
        }

        /** @var \Drupal\media\MediaInterface $media */
        $media = $media[0];
        $file = File::load($media->get('oe_media_image')->target_id);
        $image_uri = $file->getFileUri();

        $destination_uri = $image_style->buildUrl($image_uri);
        $destination_uri_160 = $image_style_160->buildUrl($image_uri);

        return json_encode([
          'id' => $slide->id(),
          'size' => $file->getSize(),
          'uri' => file_url_transform_relative(file_create_url($destination_uri)),
          'uri_160' => file_url_transform_relative(file_create_url($destination_uri_160)),
          'legend' => $slide->get('field_gallery_slide_legend')->value,
        ]);
      }, $slides_id);

      $document->setField('sm_content_gallery_slide_id_array', $slides);
    }

    //We need to use only one field key for the global search on the FE side
    $document->addField('tm_global_title', $title);
    $document->addField('ss_global_content_type', $type);
    $document->addField(
      'ss_global_content_type_label',
      !empty($type_label) ? $type_label : $type
    );
    $document->addField('ss_global_created_date', $date);
    $document->addField('bs_global_status', $status);
    $document->addField('ss_drupal_timestamp', strtotime($date));
    $document->addField('ss_drupal_changed_timestamp', strtotime($changed));
    $document->addField('ss_global_fullname', $fullname);
    $document->addField('tm_global_fullname', $fullname);
    $document->addField('ss_global_user_url', $user_url);
    $this->addOrUpdateDocumentField($document, 'sm_content_field_vocab_topics_string', $fields, $topics);
    $this->addOrUpdateDocumentField($document, 'sm_content_field_vocab_geo_string', $fields, $geo);

    if (!array_key_exists('bs_content_is_private', $fields)) {
      $document->addField('bs_content_is_private', FALSE);
    }

    if (!array_key_exists('ss_content_language_string', $fields)) {
      $document->addField('ss_content_language_string', $language);
    }

    if (array_key_exists('tm_X3b_en_rendered_item', $fields)) {
      $text = $fields['tm_X3b_en_rendered_item'];
      if (strlen($text) > 300) {
        $text = Unicode::truncate($text, 300, FALSE, TRUE);
      }

      //Trick to convert the &amp to & when nbsp
      $document->setField('tm_X3b_en_rendered_item', html_entity_decode($text));
    }

    $nid = $fields['its_content_nid'] ?? 0;
    $views = $this->nodeStatisticsDatabaseStorage->fetchView($nid);

    if ('entity:message' !== $datasource) {
      $this->addOrUpdateDocumentField(
        $document,
        'its_statistics_view',
        $fields,
        $views ? $views->getTotalCount() : 0
      );
    }
  }

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param array $fields
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function processGroupContentData(Document &$document, array $fields) {
    if ($fields['ss_search_api_datasource'] === 'entity:group') {
      return;
    }

    $group_parent_label = '';
    $group_parent_url = '';
    $group_parent_id = -1;

    if (array_key_exists('its_content__group_content__entity_id_gid', $fields)) {
      if ($group_entity = Group::load($fields['its_content__group_content__entity_id_gid'])) {
        $group_parent_label = $group_entity->label();
        $group_parent_url = $group_entity->toUrl()->toString();
        $group_parent_id = $group_entity->id();
      }
    }

    $document->addField('ss_global_group_parent_label', $group_parent_label);
    $document->addField('ss_global_group_parent_url', $group_parent_url);
    $document->addField('ss_global_group_parent_id', $group_parent_id);
  }

  /**
   * Process Message entity before sending to SOLR, add statistics data
   *
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param array $fields
   */
  public function processMessageData(Document &$document, array $fields) {
    if ($fields['ss_search_api_datasource'] !== 'entity:message') {
      return;
    }

    $node_ref = isset($fields['its_message_node_ref_id']) ? $fields['its_message_node_ref_id'] : NULL;
    $comment_ref = isset($fields['its_message_comment_ref_id']) ? $fields['its_message_comment_ref_id'] : NULL;

    if (!$node_ref && !$comment_ref) {
      return;
    }

    if ($comment_ref) {
      $comment = Comment::load($comment_ref);

      if (!$comment instanceof CommentInterface) {
        return;
      }

      $node = $comment->getCommentedEntity();
    }
    else {
      $node = Node::load($node_ref);
    }

    if (!$node instanceof NodeInterface) {
      return;
    }

    $views = $this->nodeStatisticsDatabaseStorage->fetchView($node->id());

    $this->addOrUpdateDocumentField(
      $document,
      'its_statistics_view',
      $fields,
      $views ? $views->getTotalCount() : 0
    );
    $this->addOrUpdateDocumentField(
      $document,
      'its_content_comment_count',
      $fields,
      $this->commentsHelper->countEntityComments($node)
    );
    $this->addOrUpdateDocumentField(
      $document,
      'its_document_download_total',
      $fields,
      $this->entityDownloadHelper->getFileDownloads($node)
    );
  }

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param array $fields
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function processDiscussionData(Document &$document, array $fields) {
    //Only apply logic to discussion
    if (!array_key_exists('ss_content_type', $fields) || 'discussion' !== $fields['ss_content_type']) {
      return;
    }

    if (array_key_exists('ss_content_field_body_fulltext', $fields)) {
      $document->addField('ss_global_body_no_html', html_entity_decode(strip_tags($fields['ss_content_field_body_fulltext'])));
    }

    $nid = $fields['its_content_nid'];

    $results = \Drupal::entityTypeManager()->getStorage('comment')
      ->getQuery()
      ->condition('entity_id', $nid)
      ->condition('pid', 0, 'IS NULL')
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    $total_comments = \Drupal::entityTypeManager()->getStorage('comment')
      ->getQuery()
      ->condition('entity_id', $nid)
      ->count()
      ->execute();

    $document->addField('its_discussion_total_comments', $total_comments);

    if (!$results) {
      $document->addField('ss_discussion_last_comment_text', '');
      return;
    }

    $comment = Comment::load(reset($results));

    if (!$comment instanceof CommentInterface) {
      $document->addField('ss_discussion_last_comment_text', '');
      return;
    }

    $author = $comment->get('uid')->referencedEntities();
    $author = reset($author);

    /** @var \Drupal\media\MediaInterface|NULL $author_media */
    $author_media = $author->get('field_media')->entity;
    /** @var File|NULL $author_file */
    $author_file = $author_media instanceof MediaInterface ? File::load($author_media->get('oe_media_image')->target_id) : NULL;
    $author_file_url = $author_file ? file_url_transform_relative(file_create_url($author_file->get('uri')->value)) : NULL;

    $document->addField('ss_discussion_last_comment_text', $comment->get('comment_body')->value);
    $document->addField('ss_discussion_last_comment_timestamp', $comment->getCreatedTime());
    $document->addField('ss_discussion_last_comment_author', $author instanceof UserInterface ? $author->get('field_first_name')->value . ' ' . $author->get('field_last_name')->value : '');
    $document->addField('ss_discussion_last_comment_author_image', $author_file_url);
    $document->addField('ss_discussion_last_comment_url', $author instanceof UserInterface ? $author->toUrl()
      ->toString() : '');
  }

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param array $fields
   * @param array $items
   * @param \Drupal\eic_groups\EICGroupsHelper $group_helper
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function processGroupVisibilityData(Document &$document, array $fields, array $items, EICGroupsHelper $group_helper) {
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

    $group = $group_helper->getGroupByEntity($original_object);

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

            $document->addField('ss_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS, implode(',', $users));
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

  /**
   * @param Document $document
   * @param array $fields
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function processGroupUserData(DocumentInterface &$document, array $fields) {
    $datasource = $fields['ss_search_api_datasource'];

    if ($datasource !== 'entity:user') {
      return;
    }

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

    if (array_key_exists('its_user_profile', $fields)) {
      $profile = Profile::load($fields['its_user_profile']);
      if ($profile instanceof ProfileInterface) {
        $socials = $profile->get('field_social_links')->getValue();
        $document->addField('ss_profile_socials', json_encode($socials));

        /** @var \Drupal\user\UserInterface $user */
        $user = $profile->getOwner();

        /** @var \Drupal\group\GroupMembershipLoader $grp_membership_service */
        $grp_membership_service = \Drupal::service('group.membership_loader');
        $grps = $grp_membership_service->loadByUser($user);

        $grp_ids = array_map(function (GroupMembership $grp_membership) {
          return $grp_membership->getGroup()->id();
        }, $grps);

        $document->setField('itm_user__group_content__uid_gid', $grp_ids);
      }
    }
  }

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param $fields
   */
  public function processDocumentData(Document &$document, $fields) {
    if (!array_key_exists('ss_content_type', $fields) || 'document' !== $fields['ss_content_type']) {
      return;
    }

    $node = Node::load($fields['its_content_nid']);
    $document->addField('its_document_download_total', $this->entityDownloadHelper->getFileDownloads($node));
  }

  /**
   * Updates flagging data for a document.
   *
   * @param \Solarium\QueryType\Update\Query\Document $document
   *   The Solr document.
   * @param $fields
   *   Document fields.
   */
  public function processFlaggingData(Document &$document, $fields) {
    $entity_id = NULL;
    $entity_type = NULL;
    $last_flagging_flag_types = [];

    switch ($fields['ss_search_api_datasource']) {
      case 'entity:node':
        $entity_id = $fields['its_content_nid'];
        $entity_type = 'node';
        $last_flagging_flag_types = [
          FlagType::BOOKMARK_CONTENT,
          FlagType::HIGHLIGHT_CONTENT,
          FlagType::LIKE_CONTENT,
        ];

        $node = Node::load($entity_id);
        $flags_count = $this->flagCountManager->getEntityFlagCounts($node);

        $this->addOrUpdateDocumentField(
          $document,
          'its_flag_like_content',
          $fields,
          isset($flags_count['like_content']) ? $flags_count['like_content'] : 0
        );
        break;
      case 'entity:group':
        $entity_id = $fields['its_group_id_integer'];
        $entity_type = 'group';

        $node = Group::load($entity_id);
        $flags_count = $this->flagCountManager->getEntityFlagCounts($node);

        $this->addOrUpdateDocumentField(
          $document,
          'its_flag_recommend_group',
          $fields,
          isset($flags_count['recommend_group']) ? $flags_count['recommend_group'] : 0
        );
        break;
    }

    // If we don't have a proper entity ID and type, skip this document.
    if (empty($entity_id) || empty($entity_type)) {
      return;
    }

    // Get the last flagging timestamp for each of the targeted flag types.
    foreach ($last_flagging_flag_types as $flag_type) {
      // Unfortunately flaggings don't have timestamps, so we grab the
      // last_updated from the flag_counts table.
      $result = $this->connection->select('flag_counts', 'fc')
        ->fields('fc', ['count', 'last_updated'])
        ->condition('flag_id', $flag_type)
        ->condition('entity_type', $entity_type)
        ->condition('entity_id', $entity_id)
        ->execute()->fetchAssoc();
      if (!empty($result['last_updated'])) {
        $document->addField('its_' . self::LAST_FLAGGED_KEY . '_' . $flag_type, $result['last_updated']);
      }
    }
  }

  /**
   * Updates event data for a document.
   *
   * @param \Solarium\QueryType\Update\Query\Document $document
   *   The Solr document.
   * @param $fields
   *   Document fields.
   */
  public function processGroupEventData(Document &$document, $fields) {
    $datasource = $fields['ss_search_api_datasource'];
    $content_type = $fields['ss_content_type'] ?? NULL;

    if ($datasource !== 'entity:node' || $content_type !== 'event') {
      return;
    }

    $start_date = new DrupalDateTime($fields['ds_content_field_date_range']);
    $end_date = new DrupalDateTime($fields['ds_content_field_date_range_end_value']);

    $this->addOrUpdateDocumentField(
      $document,
      GroupEventSourceType::START_DATE_SOLR_FIELD_ID,
      $fields,
      $start_date->getTimestamp()
    );

    $this->addOrUpdateDocumentField(
      $document,
      GroupEventSourceType::END_DATE_SOLR_FIELD_ID,
      $fields,
      $end_date->getTimestamp()
    );

    $this->updateEventState(
      $document,
      $fields,
      $start_date->getTimestamp(),
      $end_date->getTimestamp()
    );

    if (array_key_exists('ss_content_country_code', $fields)) {
      $country_code = $fields['ss_content_country_code'];
      $countries = CountryManager::getStandardList();

      $this->addOrUpdateDocumentField(
        $document,
        'ss_content_country_code',
        $fields,
        array_key_exists($country_code, $countries) ? $countries[$country_code] : $country_code
      );
    }
  }

  /**
   * Updates global event data for a document.
   *
   * @param \Solarium\QueryType\Update\Query\Document $document
   *   The Solr document.
   * @param $fields
   *   Document fields.
   */
  public function processGlobalEventData(Document &$document, $fields) {
    $group_type = array_key_exists('ss_group_type', $fields) ?
      $fields['ss_group_type'] :
      NULL;

    if ($group_type !== 'event') {
      return;
    }

    $start_date = new DrupalDateTime($fields['ds_group_field_date_range']);
    $end_date = new DrupalDateTime($fields['ds_group_field_date_range_end_value']);

    $this->addOrUpdateDocumentField(
      $document,
      GroupEventSourceType::START_DATE_SOLR_FIELD_ID,
      $fields,
      $start_date->getTimestamp()
    );

    $this->addOrUpdateDocumentField(
      $document,
      GroupEventSourceType::END_DATE_SOLR_FIELD_ID,
      $fields,
      $end_date->getTimestamp()
    );

    $this->updateEventState(
      $document,
      $fields,
      $start_date->getTimestamp(),
      $end_date->getTimestamp()
    );

    if (array_key_exists('ss_group_country_code', $fields)) {
      $country_code = $fields['ss_group_country_code'];
      $countries = CountryManager::getStandardList();

      $this->addOrUpdateDocumentField(
        $document,
        'ss_group_event_country',
        $fields,
        array_key_exists($country_code, $countries) ? $countries[$country_code] : $country_code
      );
    }
  }

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param $key
   * @param $group
   */
  private function setGroupOwner(Document &$document, $key, $group) {
    $group_owner = EICGroupsHelper::getGroupOwner($group);
    $document->addField(
      $key,
      $group_owner instanceof UserInterface ? $group_owner->id(): -1
    );
  }

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param array $fields
   * @param int $start_date
   * @param int $end_date
   */
  private function updateEventState(
    Document &$document,
    array $fields,
    int $start_date,
    int $end_date
  ) {
    $now = time();
    // We set a weight value depending the state of the event: 1.ongoing 2.future 3.past
    // so we can sort easily in different overviews.
    // By default we set it as past event
    $weight_event_state = Event::WEIGHT_STATE_PAST;

    if ($now < $start_date) {
      $weight_event_state = Event::WEIGHT_STATE_FUTURE;
    }

    if ($now >= $start_date && $now <= $end_date) {
      $weight_event_state = Event::WEIGHT_STATE_ONGOING;
    }

    $this->addOrUpdateDocumentField(
      $document,
      Event::SOLR_FIELD_ID_WEIGHT_STATE,
      $fields,
      $weight_event_state
    );

    $labels_map = Event::getStateLabelsMapping();

    $this->addOrUpdateDocumentField(
      $document,
      Event::SOLR_FIELD_ID_WEIGHT_STATE_LABEL,
      $fields,
      $labels_map[$weight_event_state]
    );
  }

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param $key
   * @param $fields
   * @param $value
   */
  private function addOrUpdateDocumentField(Document &$document, $key, $fields, $value) {
    array_key_exists($key, $fields) ?
      $document->setField($key, $value) :
      $document->addField($key, $value);
  }

  /**
   * Requests reindexing of the given entities.
   *
   * @param EntityInterface[] $items
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function reIndexEntities(array $items) {
    $global_index = Index::load(SolrIndexes::GLOBAL);
    $item_ids = [];
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($items as $entity) {
      if (!$entity instanceof EntityInterface) {
        continue;
      }
      $datasource_id = 'entity:' . $entity->getEntityTypeId();

      try {
        $datasource = $global_index->getDatasource($datasource_id);
      } catch (SearchApiException $api_exception) {
        continue;
      }

      $item_id = $datasource->getItemId($entity->getTypedData());
      $item_ids[] = Utility::createCombinedId($datasource_id, $item_id);
    }

    // Request reindexing for the given items.
    $this->postRequestIndexing->registerIndexingOperation(SolrIndexes::GLOBAL, $item_ids);
  }

  /**
   * @param \Drupal\group\Entity\GroupInterface $group
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function reIndexEntitiesFromGroup(GroupInterface $group) {
    $queue = $this->queueFactory->get('eic_groups_group_content_search_api');
    $queue_worker = $this->queueManager->createInstance('eic_groups_group_content_search_api');

    /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content');
    $contents = $storage->loadByGroup($group);

    foreach ($contents as $group_content) {
      $queue->createItem($group_content);
    }

    while ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      } catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      }
    }
  }

}
