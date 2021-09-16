<?php

namespace Drupal\eic_groups\Controller;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\Entity\File;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\FlagService;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides route for discussion
 */
class DiscussionController extends ControllerBase {

  const BATCH_PAGE = 3;

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

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param $discussion_id
   *
   * @return \Drupal\Core\Access\AccessResultForbidden|\Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addComment(Request $request, $discussion_id) {
    $account = $this->currentUser();

    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }

    $user = User::load($account->id());
    $content = json_decode($request->getContent(), TRUE);
    $text = Xss::filter($content['text']);
    $parent_id = $content['parentId'];

    $comment = Comment::create([
      'status' => CommentInterface::PUBLISHED,
      'uid' => $user->id(),
      'entity_type' => 'node',
      'entity_id' => $discussion_id,
      'field_name' => 'comment',
      'comment_body' => [
        'value' => $text,
        'format' => 'plain_text',
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
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function fetchComments(Request $request, $discussion_id) {
    $page = $request->query->get('page', 1);
    $parent_id = $request->query->get('parentId', 0);
    $total_to_load = $page * self::BATCH_PAGE;

    $query = $this->entityTypeManager->getStorage('comment')
      ->getQuery()
      ->condition('pid', $parent_id, $parent_id === 0 ? 'IS NULL' : '=')
      ->condition('status', Node::PUBLISHED)
      ->sort('created', 'DESC')
      ->range(0, $total_to_load);

    if (!$parent_id) {
      $query->condition('entity_id', $discussion_id);
    }

    $comments = $query->execute();

    $total = $this->entityTypeManager->getStorage('comment')
      ->getQuery()
      ->condition('entity_id', $discussion_id)
      ->condition('pid', $parent_id, $parent_id === 0 ? 'IS NULL' : '=')
      ->condition('status', Node::PUBLISHED)
      ->count()
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
        'user_id' => $user->id(),
        'user_fullname' => $user->get('field_first_name')->value . ' ' . $user->get('field_last_name')->value,
        'user_url' => $user->toUrl()->toString(),
        'created_timestamp' => $comment->getCreatedTime(),
        'text' => $comment->get('comment_body')->value,
        'comment_id' => $comment->id(),
        'likes' => $this->getCommentLikesData($comment, $account),
      ];
    }

    $data['comments'] = $comments_data;
    $data['total'] = $total;
    $data['total_loaded'] = $total_to_load;

    return new JsonResponse($data);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param $discussion_id
   * @param $comment_id
   * @param $type
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
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
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param int $discussion_id
   * @param $comment_id
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function editComment(Request $request, int $discussion_id, $comment_id) {
    $content = json_decode($request->getContent(), TRUE);
    $text = Xss::filter($content['text']);

    $comment = Comment::load($comment_id);

    if (!$comment instanceof CommentInterface) {
      return new JsonResponse('Cannot find comment entity', Response::HTTP_BAD_REQUEST);
    }

    try {
      $comment->set('comment_body', [
        'value' => $text,
        'format' => 'plain_text',
      ]);
      $comment->save();
    } catch (EntityStorageException $e) {
      \Drupal::logger('eic_groups')->error($e->getMessage());

      return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
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

}
