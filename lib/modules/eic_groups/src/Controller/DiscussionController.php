<?php

namespace Drupal\eic_groups\Controller;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;
use Laminas\Diactoros\Response\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route for discussion
 */
class DiscussionController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DiscussionController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  public function addComment(Request $request, $discussion_id) {
    $account = \Drupal::currentUser();

    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }

    $user = User::load($account->id());
    $content = json_decode($request->getContent(), TRUE);
    $text = $content['text'];
    $parent_id = $content['parentId'];

    $comment = Comment::create([
      'status' => CommentInterface::PUBLISHED,
      'uid' => $user->id(),
      'entity_type' => 'node',
      'entity_id' => 10,
      'field_name' => 'comment',
      'comment_body' => [
        'value' => $text,
        'format' => 'filtered_html',
      ],
      'comment_type' => 'node_comment',
      'pid' => $parent_id,
    ]);

    $comment->save();

    return new JsonResponse([]);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function fetchComments(Request $request, $discussion_id) {
    $comments = $this->entityTypeManager->getStorage('comment')
      ->getQuery()
      ->condition('entity_id', $discussion_id)
      ->condition('pid', 0, 'IS NULL')
      ->sort('created', 'DESC')
      ->execute();

    $comments = Comment::loadMultiple($comments);
    $comments_data = [];

    foreach ($comments as $comment) {
      $user = $comment->getOwner();

      /** @var \Drupal\media\MediaInterface|null $media_picture */
      $media_picture = $user->get('field_media')->referencedEntities();
      /** @var File|NULL $file */
      $file = $media_picture ? File::load($media_picture[0]->get('oe_media_image')->target_id) : NULL;
      $file_url = $file ? file_url_transform_relative(file_create_url($file->get('uri')->value)) : NULL;

      $comments_data[] = [
        'user_image' => $file_url,
        'user_fullname' => $user->get('field_first_name')->value . ' ' . $user->get('field_last_name')->value,
        'user_url' => $user->toUrl()->toString(),
        'created_timestamp' => $comment->getCreatedTime(),
        'text' => $comment->get('comment_body')->value,
        'comment_id' => $comment->id(),
        'children' => $this->getComments($discussion_id, $comment->id()),
      ];
    }

    return new JsonResponse($comments_data);
  }

  /**
   * @param int $discussion_id
   * @param int $parent_id
   *
   * @return array|array[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  private function getComments(int $discussion_id, int $parent_id = 0): array {
    $query = $this->entityTypeManager->getStorage('comment')
      ->getQuery()
      ->condition('entity_id', $discussion_id)
      ->condition('pid', $parent_id)
      ->sort('created', 'DESC');

    $parent_id === 0 ?
      $query->condition('pid', 0, 'IS NULL') :
      $query->condition('pid', $parent_id);

    $comments_id = $query->execute();

    $comments = Comment::loadMultiple($comments_id);

    if (empty($comments)) {
      return [];
    }

    return array_map(function (Comment $comment) {
      $user = $comment->getOwner();

      /** @var \Drupal\media\MediaInterface|null $media_picture */
      $media_picture = $user->get('field_media')->referencedEntities();
      /** @var File|NULL $file */
      $file = $media_picture ? File::load($media_picture[0]->get('oe_media_image')->target_id) : NULL;
      $file_url = $file ? file_url_transform_relative(file_create_url($file->get('uri')->value)) : NULL;

      return [
        'user_image' => $file_url,
        'user_fullname' => $user->get('field_first_name')->value . ' ' . $user->get('field_last_name')->value,
        'user_url' => $user->toUrl()->toString(),
        'created_timestamp' => $comment->getCreatedTime(),
        'text' => $comment->get('comment_body')->value,
        'comment_id' => $comment->id(),
      ];
    }, $comments);
  }

}
