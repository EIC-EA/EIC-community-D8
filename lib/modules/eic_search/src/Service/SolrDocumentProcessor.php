<?php

namespace Drupal\eic_search\Service;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Component\Utility\Unicode;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\file\Entity\File;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\statistics\NodeStatisticsDatabaseStorage;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class SolrDocumentProcessor
 *
 * @package Drupal\eic_search\Service
 */
class SolrDocumentProcessor {

  /**
   * The Entity file download count service.
   *
   * @var \Drupal\statistics\NodeStatisticsDatabaseStorage
   */
  protected $nodeStatisticsDatabaseStorage;

  /**
   * Constructs a EntityOperation object.
   *
   * @param \Drupal\statistics\NodeStatisticsDatabaseStorage $node_statistics_db_storage
   *   The Entity file download count service.
   */
  public function __construct(NodeStatisticsDatabaseStorage $node_statistics_db_storage) {
    $this->nodeStatisticsDatabaseStorage = $node_statistics_db_storage;
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
    $date = '';
    $status = FALSE;
    $fullname = '';
    $topics = [];
    $geo = [];
    $user_url = '';

    switch ($fields['ss_search_api_datasource']) {
      case 'entity:node':
        $title = $fields['ss_content_title'];
        $type = $fields['ss_content_type'];
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
        $title = $fields['ss_group_label_string'];
        $type = 'group';
        $date = $fields['ds_group_created'];
        $status = $fields['bs_group_status'];
        $fullname = $fields['ss_group_user_first_name'] . ' ' . $fields['ss_group_user_last_name'];
        $topics = $fields['ss_group_topic_name'];
        $geo = $fields['ss_group_field_vocab_geo_string'];
        $language = t('English', [], ['context' => 'eic_search'])->render();
        $user_url = '';
        if (array_key_exists('its_group_owner_id', $fields)) {
          $user = User::load($fields['its_group_owner_id']);
          $user_url = $user instanceof UserInterface ? $user->toUrl()
            ->toString() : '';
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
        $status = TRUE;
        break;
      default:
        $language = t('English', [], ['context' => 'eic_search'])->render();
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

        $destination_uri = $image_style->buildUri($image_uri);
        $destination_uri_160 = $image_style_160->buildUri($image_uri);

        $image_style->createDerivative($image_uri, $destination_uri);
        $image_style_160->createDerivative($image_uri, $destination_uri_160);

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
    $document->addField('ss_global_title', $title);
    $document->addField('ss_global_content_type', $type);
    $document->addField('ss_global_created_date', $date);
    $document->addField('bs_global_status', $status);
    $document->addField('ss_drupal_timestamp', strtotime($date));
    $document->addField('ss_drupal_changed_timestamp', strtotime($changed));
    $document->addField('ss_global_fullname', $fullname);
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
      $document->setField('tm_X3b_en_rendered_item', $text);
    }

    $nid = $fields['its_content_nid'];
    $views = $this->nodeStatisticsDatabaseStorage->fetchView($nid);

    $document->addField(
      'its_statistics_view',
      $views ? $views->getTotalCount() : 0
    );
  }

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param array $fields
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function processGroupData(Document &$document, array $fields) {
    $group_parent_label = '';
    $group_parent_url = '';
    $group_parent_id = -1;

    if (array_key_exists('its_content__group_content__entity_id_gid', $fields)) {
      if ($group_entity = Group::load($fields['its_content__group_content__entity_id_gid'])) {
        $group_parent_label = $group_entity instanceof GroupInterface ?
          $group_entity->label()
          : '';
        $group_parent_url = $group_entity instanceof GroupInterface ?
          $group_entity->toUrl()->toString()
          : '';
        $group_parent_id = $group_entity instanceof GroupInterface ?
          $group_entity->id()
          : -1;
      }
    }

    $document->addField('ss_global_group_parent_label', $group_parent_label);
    $document->addField('ss_global_group_parent_url', $group_parent_url);
    $document->addField('ss_global_group_parent_id', $group_parent_id);
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

    /** @var \Drupal\media\MediaInterface $author_media */
    $author_media = $author->get('field_media')->entity;
    /** @var File|NULL $author_file */
    $author_file = $author_media instanceof MediaInterface ? File::load($author_media->get('oe_media_image')->target_id) : NULL;
    $author_file_url = $author_file ? file_url_transform_relative(file_create_url($author_file->get('uri')->value)) : NULL;

    $document->addField('ss_discussion_last_comment_text', $comment->get('comment_body')->value);
    $document->addField('ss_discussion_last_comment_timestamp', $comment->getCreatedTime());
    $document->addField('ss_discussion_last_comment_author', $author instanceof UserInterface ? $author->get('field_first_name')->value . ' ' . $author->get('field_last_name')->value : '');
    $document->addField('ss_discussion_last_comment_author_image', $author_file_url);
    $document->addField('ss_discussion_last_comment_url', $author instanceof UserInterface ? $author->toUrl()->toString() : '');
  }

  /**
   * @param \Solarium\QueryType\Update\Query\Document $document
   * @param array $fields
   * @param $items
   * @param \Drupal\eic_groups\EICGroupsHelper $group_helper
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function processGroupVisibilityData(Document &$document, array $fields, $items, EICGroupsHelper $group_helper) {
    $search_id = array_key_exists('ss_search_api_id', $fields) ?
      $fields['ss_search_api_id'] :
      NULL;

    // By default we will add the visibility to "public" to every entity.
    // Even if it's not linked to group but we need to put it public
    // otherwise solr will not be able to reach content without this property.
    $group_visibility = GroupVisibilityType::GROUP_VISIBILITY_PUBLIC;

    if (!$search_id) {
      $document->addField('ss_group_visibility', $group_visibility);
      return;
    }

    /** @var \Drupal\search_api\Item\Item $item */
    $item = array_key_exists($search_id, $items) ? $items[$search_id] : NULL;

    if (!$item) {
      $document->addField('ss_group_visibility', $group_visibility);
      return;
    }

    $group = $group_helper->getGroupByEntity($item->getOriginalObject()
      ->getEntity());

    if (!$group) {
      $document->addField('ss_group_visibility', $group_visibility);
      return;
    }

    /** @var \Drupal\oec_group_flex\GroupVisibilityDatabaseStorage $group_visibility_storage */
    $group_visibility_storage = \Drupal::service('oec_group_flex.group_visibility.storage');
    $group_visibility_entity = $group_visibility_storage->load($group->id());

    switch ($group_visibility_entity->getType()) {
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

              return $user->id() . '|' . $user->getAccountName();
            }, $user_ids);

            $document->addField('ss_' . GroupVisibilityType::GROUP_VISIBILITY_OPTION_TRUSTED_USERS, implode(',', $users));
          }
        }
        break;
      default:
        $group_visibility = GroupVisibilityType::GROUP_VISIBILITY_PUBLIC;
        break;
    }

    $document->addField('ss_group_visibility', $group_visibility);
    $document->addField('ss_group_moderation_state', $group->get('moderation_state')->value);
    $document->addField('its_group_owner_id', $group->getOwnerId());
  }

  /**
   * @param $document
   * @param $fields
   */
  public function processGroupUserData(Document &$document, $fields) {
    if ($fields['ss_search_api_datasource'] === 'entity:user' && array_key_exists('its_user_profile', $fields)) {
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

    /** @var \Drupal\eic_media_statistics\EntityFileDownloadCount $entity_download_helper */
    $entity_download_helper = \Drupal::service('eic_media_statistics.entity_file_download_count');
    $node = Node::load($fields['its_content_nid']);

    $document->addField('its_document_download_total', $entity_download_helper->getFileDownloads($node));
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

}
