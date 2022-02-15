<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_user\UserHelper;
use Drupal\file\Entity\File;
use Drupal\group\Entity\Group;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\statistics\NodeStatisticsDatabaseStorage;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorGlobal
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorGlobal extends DocumentProcessor {

  /**
   * @var \Drupal\statistics\NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorage
   */
  private $nodeStatisticsDatabaseStorage;

  /**
   * @var FileUrlGeneratorInterface $urlGenerator
   */
  private $urlGenerator;

  /**
   * @param \Drupal\statistics\NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorage
   * @param FileUrlGeneratorInterface $urlGenerator
   */
  public function __construct(
    NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorage,
    FileUrlGeneratorInterface $urlGenerator
  ) {
    $this->nodeStatisticsDatabaseStorage = $nodeStatisticsDatabaseStorage;
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
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

    // Set by default parent group to TRUE and method processGroupContentData will update it.
    $this->addOrUpdateDocumentField($document, 'its_global_group_parent_published', $fields, 1);

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
        $type = $fields['ss_type'];
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

}