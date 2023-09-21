<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\eic_flags\FlagType;
use Drupal\eic_statistics\StatisticsHelper;
use Drupal\file\Entity\File;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorDiscussion
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorDiscussion extends DocumentProcessor {

  /**
   * The EIC Group statistics helper service.
   *
   * @var \Drupal\eic_statistics\StatisticsHelper
   */
  private StatisticsHelper $statisticsHelper;

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
   * @param \Drupal\eic_statistics\StatisticsHelper $statistics_helper
   *   The EIC Group statistics helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $url_generator
   *   The URL generator service.
   */
  public function __construct(
    StatisticsHelper $statistics_helper,
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $url_generator
  ) {
    $this->statisticsHelper = $statistics_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->urlGenerator = $url_generator;
  }

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
    if (array_key_exists('ss_content_field_body_fulltext', $fields)) {
      $document->addField(
        'ss_global_body_no_html',
        html_entity_decode(strip_tags($fields['ss_content_field_body_fulltext']))
      );
    }

    $nid = $fields['its_content_nid'];

    $comment = NULL;
    $results = $this->entityTypeManager->getStorage('comment')
      ->getQuery()
      ->condition('entity_id', $nid)
      ->condition('pid', 0, 'IS NULL')
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();
    if (!empty($results)) {
      $comment = Comment::load(reset($results));
    }

    /** @var \Drupal\node\NodeInterface $discussion */
    $discussion = $this->entityTypeManager->getStorage('node')->load($nid);

    // Handle statistics.
    $likes = 0;
    $follows = 0;
    $total_comments = $discussion->field_comments->comment_count;
    if ($discussion instanceof NodeInterface) {
      $statistics = $this->statisticsHelper->getEntityStatistics($discussion);
      $likes = $statistics[FlagType::LIKE_CONTENT];
      $follows = $statistics[FlagType::FOLLOW_CONTENT];
    }
    $most_active_total = 3 * (int) $total_comments + 2 * (int) $follows + (int) $likes;

    // Handle Author picture.
    /** @var \Drupal\image\Entity\ImageStyle $image_style */
    $image_style = $this->entityTypeManager->getStorage('image_style')
      ->load('crop_80x80');
    $user_picture_uri = array_key_exists('ss_content_author_image_uri', $fields) ?
      $fields['ss_content_author_image_uri'] :
      NULL;
    $user_picture_relative = '';
    // Generates image style for the author picture.
    if ($user_picture_uri) {
      $user_picture_relative = $this->urlGenerator->transformRelative($image_style->buildUrl($user_picture_uri));
    }
    $this->addOrUpdateDocumentField($document, 'ss_content_author_formatted_image', $fields, $user_picture_relative);

    $this->addOrUpdateDocumentField($document, self::SOLR_MOST_ACTIVE_ID, $fields, $most_active_total);
    $document->addField('its_discussion_total_comments', $total_comments);

    if (empty($comment)) {
      $document->addField('ss_discussion_last_comment_text', '');
      // Since last comment from the comments field always set a timestamp even
      // when there are no comments, we set it to 0 here.
      $document->setField('its_last_comment_timestamp', 0);
    }
    else {
      // Handle last comment.
      $image_style = $this->entityTypeManager->getStorage('image_style')
        ->load('crop_36x36');

      $last_comment_author = $comment->get('uid')->referencedEntities()[0];

      /** @var \Drupal\media\MediaInterface|NULL $author_media */
      $author_media = $last_comment_author->get('field_media')->entity;
      /** @var File|NULL $author_file */
      $author_file = $author_media instanceof MediaInterface ? File::load(
        $author_media->get('oe_media_image')->target_id
      ) : NULL;
      $author_file_url = $author_file ?
        $this->urlGenerator->transformRelative($image_style->buildUrl($author_file->get('uri')->value)) :
        NULL;

      $comment_text = '';
      if (!empty($comment) && $comment->get('comment_body')->value) {
        if ($comment->get('comment_body')->value) {
          // Sanitize text before indexing.
          $comment_text = $this->sanitizeFulltextString($comment->get('comment_body')->value);
        }
        $document->addField('ss_discussion_last_comment_text', $comment_text);
        $document->addField('ss_discussion_last_comment_timestamp', $comment->getCreatedTime());
      }

      $document->addField(
        'ss_discussion_last_comment_author',
        $last_comment_author instanceof UserInterface ? $last_comment_author->getDisplayName() : ''
      );
      $document->addField('ss_discussion_last_comment_author_image', $author_file_url);
      $document->addField(
        'ss_discussion_last_comment_url',
        $last_comment_author instanceof UserInterface ? $last_comment_author->toUrl()
          ->toString() : ''
      );
    }

  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    return array_key_exists('ss_content_type', $fields) && 'discussion' === $fields['ss_content_type'];
  }

}
