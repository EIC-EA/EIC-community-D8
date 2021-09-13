<?php

namespace Drupal\eic_groups\Controller;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\Entity\File;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\FlagService;
use Drupal\user\Entity\User;
use Laminas\Diactoros\Response\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides route for discussion
 */
class DiscussionController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The flag service
   *
   * @var \Drupal\flag\FlagService $flagService
   */
  private $flagService;

  /**
   * DiscussionController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\flag\FlagService $flag_service
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FlagService $flag_service
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->flagService = $flag_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('flag')
    );
  }

  public function addComment(Request $request, $discussion_id) {
    $account = $this->currentUser();

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
   * @param $discussion_id
   *
   * @return \Laminas\Diactoros\Response\JsonResponse
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

    $account = $this->currentUser();
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
        'likes' => $this->getCommentLikesData($comment, $account),
      ];
    }

    return new JsonResponse($comments_data);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param int $discussion_id
   * @param int $comment_id
   * @param string $type
   *
   * @return \Laminas\Diactoros\Response\JsonResponse
   */
  public function likeComment(Request $request, $discussion_id, $comment_id, $type) {
    $comment = Comment::load($comment_id);

    if (!$comment instanceof CommentInterface) {
      return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->flagService->{$type}(
        $this->flagService->getFlagById('like_comment'),
        $comment
      );
    } catch (\LogicException $e) {
      \Drupal::logger('eic_groups')->error($e);
      return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    return new JsonResponse([]);
  }

  /**
   * @param \Drupal\comment\CommentInterface $comment
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return array
   */
  private function getCommentLikesData(CommentInterface $comment, AccountInterface $account) {
    $hasAccountLiked = FALSE;
    $flags = $this->flagService->getAllEntityFlaggings($comment, $account);
    $flags = array_filter($flags, function (FlaggingInterface $flag) {
      return 'like_comment' === $flag->getFlagId();
    });

    foreach ($flags as $key => $flag) {
      if ('like_comment' !== $flag->getFlagId()) {
        unset($flags[$key]);
      }

      if (!empty($this->flagService->getFlaggingUsers($comment, $flag->getFlag()))) {
        $hasAccountLiked = TRUE;
      }
    }

    return [
      'total' => count($flags),
      'hasAccountLike' => $hasAccountLiked,
    ];
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
        'likes' => $this->getCommentLikesData($comment, $this->currentUser()),
      ];
    }, $comments);
  }

}
