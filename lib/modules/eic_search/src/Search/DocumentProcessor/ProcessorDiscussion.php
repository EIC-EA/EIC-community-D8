<?php

namespace Drupal\eic_search\Search\DocumentProcessor;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\file\Entity\File;
use Drupal\media\MediaInterface;
use Drupal\user\UserInterface;
use Solarium\QueryType\Update\Query\Document;

/**
 * Class ProcessorDiscussion
 *
 * @package Drupal\eic_search\DocumentProcessor
 */
class ProcessorDiscussion extends DocumentProcessor {

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
    $author_file_url = $author_file ? file_url_transform_relative(
      file_create_url($author_file->get('uri')->value)
    ) : NULL;

    $document->addField('ss_discussion_last_comment_text', $comment->get('comment_body')->value);
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
