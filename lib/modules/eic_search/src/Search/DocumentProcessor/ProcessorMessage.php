<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\eic_comments\CommentsHelper;
use Drupal\eic_media_statistics\EntityFileDownloadCount;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\statistics\NodeStatisticsDatabaseStorage;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorMessage
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorMessage extends DocumentProcessor {

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
   * @param \Drupal\eic_media_statistics\EntityFileDownloadCount $entityDownloadHelper
   * @param \Drupal\statistics\NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorage
   * @param CommentsHelper $commentsHelper
   */
  public function __construct(
    EntityFileDownloadCount $entityDownloadHelper,
    NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorage,
    CommentsHelper $commentsHelper
  ) {
    $this->entityDownloadHelper = $entityDownloadHelper;
    $this->nodeStatisticsDatabaseStorage = $nodeStatisticsDatabaseStorage;
    $this->commentsHelper = $commentsHelper;
  }

  /**
   * @inheritDoc
   */
  public function process(Document &$document, array $fields, array $items = []): void {
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
  }

  /**
   * @inerhitDoc
   */
  public function supports(array $fields): bool {
    return $fields['ss_search_api_datasource'] === 'entity:message';
  }
}