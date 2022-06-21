<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\eic_flags\FlagType;
use Drupal\eic_statistics\StatisticsHelper;
use Drupal\file\Entity\File;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;
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
   * @var \Drupal\eic_statistics\StatisticsHelper
   */
  private StatisticsHelper $statisticsHelper;

  /**
   * @param \Drupal\eic_statistics\StatisticsHelper $statistics_helper
   */
  public function __construct(StatisticsHelper $statistics_helper) {
    $this->statisticsHelper = $statistics_helper;
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

    $discussion = Node::load($nid);
    $likes = 0;
    $follows = 0;

    if ($discussion instanceof NodeInterface) {
      $statistics = $this->statisticsHelper->getEntityStatistics($discussion);

      $likes = $statistics[FlagType::LIKE_CONTENT];
      $follows = $statistics[FlagType::FOLLOW_CONTENT];
    }

    $most_active_total = 3 * (int) $total_comments + 2 * (int) $follows + (int) $likes;

    $this->addOrUpdateDocumentField($document, self::SOLR_MOST_ACTIVE_ID, $fields, $most_active_total);
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
    $author_file = $author_media instanceof MediaInterface ? File::load(
      $author_media->get('oe_media_image')->target_id
    ) : NULL;
    $author_file_url = $author_file ? \Drupal::service('file_url_generator')->transformRelative(
      file_create_url($author_file->get('uri')->value)
    ) : NULL;

    $comment_text = '';
    if ($comment->get('comment_body')->value) {
      // Sanitize text before indexing.
      $comment_text = $this->sanitizeFulltextString($comment->get('comment_body')->value);
    }
    
    $document->addField('ss_discussion_last_comment_text', $comment_text);
    $document->addField('ss_discussion_last_comment_timestamp', $comment->getCreatedTime());
    $document->addField(
      'ss_discussion_last_comment_author',
      $author instanceof UserInterface ? $author->get('field_first_name')->value . ' ' . $author->get(
          'field_last_name'
        )->value : ''
    );
    $document->addField('ss_discussion_last_comment_author_image', $author_file_url);
    $document->addField(
      'ss_discussion_last_comment_url',
      $author instanceof UserInterface ? $author->toUrl()
        ->toString() : ''
    );
  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    return array_key_exists('ss_content_type', $fields) && 'discussion' === $fields['ss_content_type'];
  }

}
