<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\eic_comments\CommentsHelper;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_media_statistics\EntityFileDownloadCount;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\message\Entity\Message;
use Drupal\message\MessageInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\statistics\NodeStatisticsDatabaseStorage;
use Drupal\taxonomy\TermInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorMessage
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorMessage extends DocumentProcessor {

  /**
   * We need to map weird machine name to the correct one for the activity overview.
   */
  private const MAP_ACTIVITY_TYPES = [
    'node_comment' => 'comment',
  ];

  /**
   * @var \Drupal\eic_media_statistics\EntityFileDownloadCount $entityDownloadHelper
   */
  private $entityDownloadHelper;

  /**
   * @var \Drupal\statistics\NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorage
   */
  private $nodeStatisticsDatabaseStorage;

  /**
   * @var \Drupal\eic_comments\CommentsHelper $commentsHelper
   */
  private $commentsHelper;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  private $flagService;

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  private $urlGenerator;

  /**
   * @param \Drupal\eic_media_statistics\EntityFileDownloadCount $entityDownloadHelper
   *   The entity file download count service.
   * @param \Drupal\statistics\NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorage
   *   The node statistics database storage service.
   * @param CommentsHelper $commentsHelper
   *   The comment helper service.
   * @param FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $url_generator
   *   The URL generator service.
   */
  public function __construct(
    EntityFileDownloadCount $entityDownloadHelper,
    NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorage,
    CommentsHelper $commentsHelper,
    FlagServiceInterface $flag_service,
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $url_generator
  ) {
    $this->entityDownloadHelper = $entityDownloadHelper;
    $this->nodeStatisticsDatabaseStorage = $nodeStatisticsDatabaseStorage;
    $this->commentsHelper = $commentsHelper;
    $this->flagService = $flag_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->urlGenerator = $url_generator;
  }

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    $node_ref = isset($fields['its_message_node_ref_id']) ? $fields['its_message_node_ref_id'] : NULL;
    $comment_ref = isset($fields['its_message_comment_ref_id']) ? $fields['its_message_comment_ref_id'] : NULL;
    $moderation_state = DefaultContentModerationStates::PUBLISHED_STATE;

    if (!$node_ref && !$comment_ref) {
      return;
    }

    $node = NULL;
    if ($comment_ref) {
      $comment = Comment::load($comment_ref);

      if (!$comment instanceof CommentInterface) {
        return;
      }

      $node = $comment->getCommentedEntity();
    }
    elseif ($node_ref) {
      $node = Node::load($node_ref);
    }

    if (!$node instanceof NodeInterface) {
      return;
    }

    $group_contents = GroupContent::loadByEntity($node);

    if (!empty($group_contents)) {
      /** @var GroupContent $group_content */
      $group_content = reset($group_contents);
      $group_id = $group_content->getGroup() instanceof GroupInterface ?
        $group_content->getGroup()->id() :
        -1;

      $this->addOrUpdateDocumentField($document, 'its_global_group_parent_id', $fields, $group_id);
    }

    $index_views = TRUE;
    $index_comments = TRUE;
    $index_downloads = TRUE;

    // Discard some counters from being indexed depending on the node type.
    switch ($node->bundle()) {
      case 'wiki_page':
        $index_comments = FALSE;
        break;

    }

    $views = $this->nodeStatisticsDatabaseStorage->fetchView($node->id());
    $type = $fields['ss_type'];
    $map_type = array_key_exists($type, self::MAP_ACTIVITY_TYPES) ?
      self::MAP_ACTIVITY_TYPES[$type] :
      $type;

    $this->addOrUpdateDocumentField(
      $document,
      'ss_activity_type',
      $fields,
      $map_type
    );

    $this->addOrUpdateDocumentField(
      $document,
      'ss_message_moderation_state',
      $fields,
      $moderation_state
    );

    $message = Message::load($fields['its_message_id']);
    // Index all followers link to the message data.
    if ($message instanceof MessageInterface) {
      $topics = $node->get('field_vocab_topics')->referencedEntities();
      $topics_follows = [];
      foreach ($topics as $topic) {
        $topics_follows = array_merge(
          $topics_follows,
          $this->getFollowUidByFlag('follow_taxonomy_term', $topic)
        );
      }

      /** @var \Drupal\taxonomy\TermInterface|NULL $language */
      $language = $node->hasField('field_language') ?
        $node->get('field_language')->entity :
        NULL;

      $this->addOrUpdateDocumentField(
        $document,
        'ss_content_language_label',
        $fields,
        $language instanceof TermInterface ? $language->label() : NULL
      );

      $user_follows = [];
      if ($message->getOwner()) {
        $user_follows = $this->getFollowUidByFlag('follow_user', $message->getOwner());
      }
      $node_follows = $this->getFollowUidByFlag('follow_content', $node);
      $group_follows = [];
      $node_group_id = array_key_exists('its_group_id', $fields) ? $fields['its_group_id'] : NULL;

      if (!$node_group_id) {
        return;
      }

      if ($group = Group::load($node_group_id)) {
        $group_follows = $this->getFollowUidByFlag('follow_group', $group);
        $group_parent_id = $group->id();
        $group_type = $group->getGroupType()->id();

        $this->addOrUpdateDocumentField($document, 'ss_global_group_parent_type', $fields, $group_type);
        $this->addOrUpdateDocumentField($document, 'its_global_group_parent_id', $fields, $group_parent_id);
      }

      $follows = array_merge(
        $user_follows,
        $node_follows,
        $group_follows,
        $topics_follows
      );

      $this->addOrUpdateDocumentField(
        $document,
        'itm_follow_uid',
        $fields,
        array_unique($follows)
      );
    }

    if ($index_views) {
      $this->addOrUpdateDocumentField(
        $document,
        'its_statistics_view',
        $fields,
        $views ? $views->getTotalCount() : 0
      );
    }
    if ($index_comments) {
      $this->addOrUpdateDocumentField(
        $document,
        'its_content_comment_count',
        $fields,
        $this->commentsHelper->countEntityComments($node)
      );
    }
    if ($index_downloads) {
      $this->addOrUpdateDocumentField(
        $document,
        'its_document_download_total',
        $fields,
        $this->entityDownloadHelper->getFileDownloads($node)
      );
    }

    $author_picture_uri = array_key_exists('ss_author_profile_picture_uri', $fields) ?
      $fields['ss_author_profile_picture_uri'] :
      NULL;

    $teaser_relative = '';

    // Generates image style for the user picture.
    if ($author_picture_uri) {
      /** @var \Drupal\image\Entity\ImageStyle $image_style */
      $image_style = $this->entityTypeManager->getStorage('image_style')
        ->load('crop_80x80');
      $teaser_relative = $this->urlGenerator->transformRelative($image_style->buildUrl($author_picture_uri));
    }

    $this->addOrUpdateDocumentField(
      $document,
      'ss_author_formatted_profile_picture',
      $fields,
      $teaser_relative
    );
  }

  /**
   * @param string $flag_id
   *   The flag id.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get flaggings.
   *
   * @return array
   *   Return the array of user flagged.
   */
  private function getFollowUidByFlag(string $flag_id, EntityInterface $entity): array {
    $flag_follow = $this->flagService->getFlagById($flag_id);
    $follows = $this->flagService->getEntityFlaggings($flag_follow, $entity);

    return array_map(function (FlaggingInterface $flagging) {
      return (int) $flagging->getOwnerId();
    }, $follows);
  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    return $fields['ss_search_api_datasource'] === 'entity:message';
  }

}
