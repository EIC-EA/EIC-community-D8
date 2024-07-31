<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_group_statistics\GroupStatisticsHelper;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_user\UserHelper;
use Drupal\file\Entity\File;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
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
   * @var \Drupal\statistics\NodeStatisticsDatabaseStorage
   *   $nodeStatisticsDatabaseStorage
   */
  private $nodeStatisticsDatabaseStorage;

  /**
   * @var FileUrlGeneratorInterface $urlGenerator
   */
  private $urlGenerator;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $em;

  /**
   * @var \Drupal\eic_group_statistics\GroupStatisticsHelper
   */
  private $groupStatisticsHelper;

  /**
   * @param \Drupal\statistics\NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorage
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $urlGenerator
   * @param \Drupal\Core\Entity\EntityTypeManager $em
   * @param \Drupal\eic_group_statistics\GroupStatisticsHelper $groupStatisticsHelper
   */
  public function __construct(
    NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorage,
    FileUrlGeneratorInterface $urlGenerator,
    EntityTypeManager $em,
    GroupStatisticsHelper $groupStatisticsHelper
  ) {
    $this->nodeStatisticsDatabaseStorage = $nodeStatisticsDatabaseStorage;
    $this->urlGenerator = $urlGenerator;
    $this->em = $em;
    $this->groupStatisticsHelper = $groupStatisticsHelper;
  }

  /**
   * @inheritDoc
   */
  public function process(
    Document &$document,
    array $fields,
    array $items = []
  ): void {
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
    $moderation_state = DefaultContentModerationStates::PUBLISHED_STATE;
    $last_moderation_state = DefaultContentModerationStates::PUBLISHED_STATE;
    $group_last_moderation_state = GroupsModerationHelper::GROUP_PUBLISHED_STATE;
    $is_group_parent_published = 1;

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
        $moderation_state = $fields['ss_content_moderation_state'];
        $fullname = array_key_exists(
          'ss_content_first_name',
          $fields
        ) && array_key_exists(
          'ss_content_last_name',
          $fields
        ) ?
          $fields['ss_content_first_name'] . ' ' . $fields['ss_content_last_name'] :
          t('No name', [], ['context' => 'eic_search']);
        $topics = array_key_exists(
          'sm_content_field_vocab_topics_string',
          $fields
        ) ?
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
        $node = Node::load($fields['its_content_nid']);
        if ($node instanceof NodeInterface) {
          if (
            $node->hasField('field_language') &&
            !$node->get('field_language')->isEmpty() &&
            $language_entity = $node->get('field_language')->first()->entity
          ) {
            $language = $language_entity->label();
          }

          $group_last_moderation_state = $last_moderation_state = $this->getLastRevisionModerationState($node);

          // For News and Stories, the published date should be saved.
          if (in_array($node->bundle(), ['news', 'story'])) {
            // Ensure that the value is set as it might not be when in
            // Draft state.
            if (
              $node->hasField('published_at') &&
              !$node->get('published_at')->isEmpty()
            ) {
              $date = \Drupal::service('date.formatter')
                ->format(
                  $node->get('published_at')->published_at_or_created,
                  'custom',
                  DateTimeItemInterface::DATETIME_STORAGE_FORMAT
                );
            }
          }
        }

        // The node should not be accessible if the group is not published.
        if ($node_group_contents = GroupContent::loadByEntity($node)) {
          $node_group_content = reset($node_group_contents);
          $group_last_moderation_state = $this->getLastRevisionModerationState($node_group_content->getGroup());
          $status = $node_group_content->getGroup()->isPublished() ?
            $fields['bs_content_status'] :
            FALSE;
        }
        break;
      case 'entity:group':
        if (array_key_exists('ss_group_topic_name', $fields)) {
          $topics = $fields['ss_group_topic_name'];
        }
        else {
          if (array_key_exists('sm_group_topic_name', $fields)) {
            $topics = $fields['sm_group_topic_name'];
          }
        }

        $changed = $fields['ds_aggregated_changed'];
        $title = $fields['tm_X3b_en_group_label_fulltext'];
        $type = $fields['ss_group_type'];
        $date = $fields['ds_group_created'];
        $status = $fields['bs_group_status'];
        $is_group_parent_published = (int) $status;
        $geo = $fields['ss_group_field_vocab_geo_string'] ?? '';
        $language = t('English', [], ['context' => 'eic_search'])->render();
        $user_url = '';
        $group_id = $fields['its_group_id_integer'] ?? -1;
        $moderation_state = $fields['ss_group_moderation_state'];
        $this->addOrUpdateDocumentField(
          $document,
          'its_group_id_integer',
          $fields,
          $group_id
        );
        $document->addField('its_global_group_parent_id', $group_id);
        $group = Group::load($group_id);
        if ($group instanceof GroupInterface) {
          $lastActivityTime = $this->groupStatisticsHelper->getGroupLatestActivity(
            $group
          );

          // If we have a value for a last activity stream in the group, we replace the default changed time.
          if ($lastActivityTime) {
            $changed = $lastActivityTime;
          }

          $group_last_moderation_state = $last_moderation_state = $this->getLastRevisionModerationState(
            $group
          );
          $this->addOrUpdateDocumentField(
            $document,
            'its_content_uid',
            $fields,
            $group->getOwnerId()
          );
        }
        if ($group && $owner = EICGroupsHelper::getGroupOwner($group)) {
          $fullname = $owner->getDisplayName();

          $this->addOrUpdateDocumentField(
            $document,
            'ss_group_user_image',
            $fields,
            UserHelper::getUserAvatar($owner)
          );
          $user_url = $owner->toUrl()->toString();
        }
        break;
      case 'entity:message':
        $user_url = '';
        if (array_key_exists('its_uid', $fields)) {
          $user = User::load($fields['its_uid']);
          $user_url = $user instanceof UserInterface ? $user->toUrl()
            ->toString() : '';
        }
        if (!empty($fields['ss_author_first_name']) && !empty($fields['ss_author_last_name'])) {
          $fullname = $fields['ss_author_first_name'] . ' ' . $fields['ss_author_last_name'];
        }
        else {
          $fullname = 'undefined';
        }
        $status = TRUE;
        $type = $fields['ss_type'] ?? '';
        $topics = $fields['sm_message_node_ref_field_vocab_topics_name'] ?? [];
        $date = $fields['ds_created'];
        $title = $fields['ss_title'] ?? '';
        break;
      case 'entity:user':
        $user = User::load($fields['its_user_id']);
        $fullname = $user->getDisplayName();
        $date = $fields['ds_user_created'];
        $changed = $fields['ds_user_changed'];

        $this->addOrUpdateDocumentField(
          $document,
          'tm_user_mail',
          $fields,
          $fields['ss_user_mail'] ?? ''
        );

        $status = TRUE;
        break;
    }

    if ('gallery' === $type) {
      $slides_id = $fields['sm_content_gallery_slide_id_array'] ?? [];
      $slides_id = is_array($slides_id) ? $slides_id : [$slides_id];
      $image_style = ImageStyle::load('crop_50x50');
      $image_style_160 = ImageStyle::load('gallery_teaser_crop_160x160');
      $slides = array_map(
        function($slide_id) use ($image_style, $image_style_160) {
          $slide = Paragraph::load($slide_id);
          $media = $slide->get('field_gallery_slide_media')->referencedEntities(
          );

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
            'uri' => $this->urlGenerator->transformRelative(
              file_create_url($destination_uri)
            ),
            'uri_160' => $this->urlGenerator->transformRelative(
              file_create_url($destination_uri_160)
            ),
            'legend' => $slide->get('field_gallery_slide_legend')->value,
          ]);
        },
        $slides_id
      );

      $document->setField('sm_content_gallery_slide_id_array', $slides);
    }

    //We need to use only one field key for the global search on the FE side
    $document->addField('tm_global_title', $title);
    $document->addField('ss_global_title', $title);
    $document->addField('ss_global_content_type', $type);
    $document->addField(
      'ss_global_content_type_label',
      !empty($type_label) ? $type_label : $type
    );
    $document->addField('ss_global_created_date', $date);
    $document->addField('bs_global_status', $status);
    $document->addField('ss_drupal_timestamp', strtotime($date));
    $document->addField(
      'ss_drupal_changed_timestamp',
      is_numeric($changed) && (int) $changed == $changed ?
        $changed :
        strtotime($changed)
    );
    $document->addField('ss_global_fullname', $fullname);
    $document->addField('tm_global_fullname', $fullname);
    $document->addField('ss_global_user_url', $user_url);
    $this->addOrUpdateDocumentField(
      $document,
      'sm_content_field_vocab_topics_string',
      $fields,
      $topics
    );
    $this->addOrUpdateDocumentField(
      $document,
      'sm_content_field_vocab_geo_string',
      $fields,
      $geo
    );
    $this->addOrUpdateDocumentField(
      $document,
      'ss_global_moderation_state',
      $fields,
      $moderation_state
    );
    $this->addOrUpdateDocumentField(
      $document,
      'ss_global_last_moderation_state',
      $fields,
      $last_moderation_state
    );
    $this->addOrUpdateDocumentField(
      $document,
      'ss_global_group_last_moderation_state',
      $fields,
      $group_last_moderation_state
    );

    // Set by default parent group to TRUE and method processGroupContentData will update it.
    $this->addOrUpdateDocumentField(
      $document,
      'its_global_group_parent_published',
      $fields,
      $is_group_parent_published
    );

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
   * Return the last moderation state of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *  The entity.
   *
   * @return string
   *   The moderation state.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getLastRevisionModerationState(EntityInterface $entity
  ): string {
    $entity_type = $entity->getEntityTypeId();
    if ('group' === $entity_type) {
      $last_revision_id = $entity->getRevisionId();
    }
    else {
      $revision_ids = $this->em->getStorage($entity_type)->revisionIds($entity);
      $last_revision_id = end($revision_ids);
    }

    $last_revision = $entity->getRevisionId() !== $last_revision_id ?
      $this->em->getStorage($entity_type)->loadRevision($last_revision_id) :
      $this->em->getStorage($entity_type)->loadRevision(
        $entity->getRevisionId()
      );

    return $last_revision->get('moderation_state')->value;
  }

}
