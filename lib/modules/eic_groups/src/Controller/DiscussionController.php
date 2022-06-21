<?php

namespace Drupal\eic_groups\Controller;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\eic_comments\CommentsHelper;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Drupal\eic_user\UserHelper;
use Drupal\file\Entity\File;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagService;
use Drupal\group\Entity\GroupContent;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\oec_group_comments\GroupPermissionChecker;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides route for discussion
 *
 * Class DiscussionController
 *
 * @package Drupal\eic_groups\Controller
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
   * The group permission checker
   *
   * @var GroupPermissionChecker
   */
  private $groupPermissionChecker;

  /**
   * @var \Drupal\Core\Datetime\DateFormatter $dateFormatter
   */
  private $dateFormatter;

  /**
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  private $fileUrlGenerator;

  /**
   * @var \Drupal\eic_search\Service\SolrDocumentProcessor|NULL
   */
  private ?SolrDocumentProcessor $solrDocumentProcessor;

  /**
   * DiscussionController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\flag\FlagService $flag_service
   * @param \Drupal\oec_group_comments\GroupPermissionChecker $group_permission_checker
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   * @param \Drupal\eic_search\Service\SolrDocumentProcessor $solr_document_processor
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FlagService $flag_service,
    GroupPermissionChecker $group_permission_checker,
    DateFormatter $date_formatter,
    FileUrlGeneratorInterface $file_url_generator,
    SolrDocumentProcessor $solr_document_processor
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->flagService = $flag_service;
    $this->groupPermissionChecker = $group_permission_checker;
    $this->dateFormatter = $date_formatter;
    $this->fileUrlGenerator = $file_url_generator;
    $this->solrDocumentProcessor = $solr_document_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('flag'),
      $container->get('oec_group_comments.group_permission_checker'),
      $container->get('date.formatter'),
      $container->get('file_url_generator'),
      $container->get('eic_search.solr_document_processor')
    );
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param $discussion_id
   *
   * @return \Drupal\Core\Access\AccessResultForbidden|\Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\search_api\SearchApiException
   */
  public function addComment(Request $request, $discussion_id) {
    if (!$this->hasPermission($discussion_id, 'post comments')) {
      return new JsonResponse('You do not have access to post comment', Response::HTTP_FORBIDDEN);
    }

    $user = User::load($this->currentUser()->id());
    $content = json_decode($request->getContent(), TRUE);
    $text = CommentsHelper::formatHtmlComment($content['text']);
    $tagged_users = $content['taggedUsers'] ?? NULL;
    $parent_id = $content['parentId'];

    if ($node = Node::load($discussion_id)) {
      Cache::invalidateTags($node->getCacheTags());
    }

    $comment = Comment::create([
      'status' => CommentInterface::PUBLISHED,
      'uid' => $user->id(),
      'entity_type' => 'node',
      'entity_id' => $discussion_id,
      'field_name' => 'field_comments',
      'comment_body' => [
        'value' => $text !== NULL ? $text : '',
        'format' => 'full_html',
      ],
      'field_tagged_users' => array_map(function ($tagged_user) {
        return [
          'target_id' => $tagged_user['tid'],
        ];
      }, $tagged_users),
      'comment_type' => 'node_comment',
      'pid' => $parent_id,
    ]);

    $comment->save();

    $resynced_entities = [$user];

    if ($node) {
      $resynced_entities[] = $node;
    }

    // Reindex entities to update their data like most_active_score.
    $this->solrDocumentProcessor->lateReIndexEntities($resynced_entities);

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
    $highlighted_comment = $request->query->get('highlightedComment', 0);
    $page = $request->query->get('page', 1);
    $parent_id = $request->query->get('parentId', 0);
    $total_to_load = $page * self::BATCH_PAGE;
    $highlighted_comment = Comment::load($highlighted_comment);

    $query = $this->entityTypeManager->getStorage('comment')
      ->getQuery()
      ->condition('pid', $parent_id, $parent_id === 0 ? 'IS NULL' : '=')
      ->condition('status', Node::PUBLISHED)
      ->sort('created', 'DESC')
      ->range(0, $total_to_load);

    if ($highlighted_comment) {
      $query->condition('cid', $highlighted_comment->id(), '<>');
    }

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

    $comments = Comment::loadMultiple($comments);
    $comments_data = [];

    // Put highlighted comment at the top.
    if ($highlighted_comment instanceof CommentInterface) {
      $comments_data[] = $this->renderCommentElement($highlighted_comment);
    }

    foreach ($comments as $comment) {
      $comments_data[] = $this->renderCommentElement($comment);
    }

    $data['comments'] = $comments_data;
    $data['total'] = $total;
    $data['total_loaded'] = $total_to_load;

    return new JsonResponse($data);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param int $discussion_id
   * @param int $comment_id
   * @param string $flag
   * @param string $type
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function flagComment(
    Request $request,
    int $discussion_id,
    int $comment_id,
    string $flag,
    string $type
  ): JsonResponse {
    $comment = Comment::load($comment_id);

    if (!$comment instanceof CommentInterface) {
      return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    $content = json_decode($request->getContent(), TRUE);
    $text = CommentsHelper::formatHtmlComment($content['text']);

    try {
      if ('like_comment' === $flag) {
        $flag_entity = $this->flagService->getFlagById('like_comment');

        if (!$flag_entity->actionAccess($type, $this->currentUser(), $comment)) {
          return new JsonResponse(
            'You do not have access to ' . $type . ' like comment',
            Response::HTTP_FORBIDDEN
          );
        }

        $this->flagService->{$type}(
          $flag_entity,
          $comment
        );
      }
      else {
        $flag_entity = $this->flagService->getFlagById($flag);

        $flagging = $this->entityTypeManager->getStorage('flagging')->create(
          [
            'uid' => $this->currentUser()->id(),
            'session_id' => NULL,
            'flag_id' => $flag_entity->id(),
            'entity_id' => $comment_id,
            'entity_type' => 'comment',
            'global' => $flag_entity->isGlobal(),
          ]
        );

        if (!$flag_entity->actionAccess('flag', $this->currentUser(), $comment)) {
          return new JsonResponse(
            'You do not have access to ' . $flag_entity->id(),
            Response::HTTP_FORBIDDEN
          );
        }

        $flagging->set('field_request_reason', $text);
        $flagging->set('field_request_status', RequestStatus::OPEN);

        $flagging->save();
      }
    } catch (\Exception $e) {
      \Drupal::logger('eic_groups')->error($e);
      return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
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
    $text = CommentsHelper::formatHtmlComment($content['text']);

    $comment = Comment::load($comment_id);

    if ($this->isGroupArchived($discussion_id)) {
      return new JsonResponse('You do not have access to edit own comment', Response::HTTP_FORBIDDEN);
    }

    if (!$comment instanceof CommentInterface) {
      return new JsonResponse('Cannot find comment entity', Response::HTTP_BAD_REQUEST);
    }

    $user = User::load($this->currentUser()->id());

    if ($user->id() === $comment->getOwnerId() && !$this->hasPermission($discussion_id, 'edit own comments')) {
      return new JsonResponse('You do not have access to edit own comment', Response::HTTP_FORBIDDEN);
    }

    if ($user->id() !== $comment->getOwnerId() && !$this->hasPermission($discussion_id, 'edit all comments')) {
      return new JsonResponse('You do not have access to edit all comments', Response::HTTP_FORBIDDEN);
    }

    $tagged_users = $content['taggedUsers'] ?? NULL;

    try {
      $comment->set('comment_body', [
        'value' => $text,
        'format' => 'plain_text',
      ]);
      $comment->set(
        'field_tagged_users',
        array_map(function ($tagged_user) {
          return [
            'target_id' => $tagged_user['uid'],
          ];
        }, $tagged_users)
      );

      $comment->save();
    } catch (EntityStorageException $e) {
      \Drupal::logger('eic_groups')->error($e->getMessage());

      return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
    }

    return new JsonResponse([]);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param int $group_id
   * @param int $discussion_id
   * @param $comment_id
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function deleteComment(Request $request, int $group_id, int $discussion_id, $comment_id) {
    $comment = Comment::load($comment_id);

    if ($this->isGroupArchived($discussion_id)) {
      return new JsonResponse('You do not have access to edit own comment', Response::HTTP_FORBIDDEN);
    }

    if (!$comment instanceof CommentInterface) {
      return new JsonResponse('Cannot find comment entity', Response::HTTP_BAD_REQUEST);
    }

    if ($node = Node::load($discussion_id)) {
      Cache::invalidateTags($node->getCacheTags());
    }

    try {
      $now = DrupalDateTime::createFromTimestamp(time());
      $comment->set(
        'field_comment_deletion_date',
        $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT)
      );
      $comment->set('comment_body', [
        'value' => $this->t('This comment has been removed by a content administrator at @time',
          ['@time' => $now->format('d/m/Y - H:i')]
        ),
        'format' => 'plain_text',
      ]);
      $comment->set('field_comment_is_soft_deleted', TRUE);
      $comment->save();
    } catch (EntityStorageException $e) {
      \Drupal::logger('eic_groups')->error($e->getMessage());

      return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
    }

    return new JsonResponse([]);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param int $discussion_id
   * @param int $comment_id
   * @param string $flag
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function hasFlagPermission(
    Request $request,
    int $discussion_id,
    int $comment_id,
    string $flag
  ): JsonResponse {
    $comment = Comment::load($comment_id);
    $flag_entity = $this->flagService->getFlagById($flag);

    if (!$comment instanceof CommentInterface || !$flag_entity instanceof FlagInterface) {
      return new JsonResponse(
        'No comment or flag found',
        Response::HTTP_BAD_REQUEST
      );
    }

    $access = $flag_entity->actionAccess('flag', $this->currentUser(), $comment);

    // Access to flag/unflag is not allowed.
    if (!$access->isAllowed()) {
      return new JsonResponse(
        ['allowed' => FALSE],
        Response::HTTP_OK
      );
    }

    return new JsonResponse(
      ['allowed' => TRUE],
      Response::HTTP_OK
    );
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultNeutral
   */
  public function accessDelete(AccountInterface $account) {
    return AccessResult::allowedIf(
      UserHelper::isPowerUser($this->currentUser())
    );
  }

  /**
   * @param \Drupal\comment\CommentInterface $comment
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return array
   */
  private function getCommentLikesData(CommentInterface $comment, AccountInterface $account) {
    $hasAccountLiked = FALSE;
    $flags = $this->flagService->getAllEntityFlaggings($comment);
    $flags = array_filter($flags, function (FlaggingInterface $flag) {
      return 'like_comment' === $flag->getFlagId();
    });

    foreach ($flags as $key => $flag) {
      if ('like_comment' !== $flag->getFlagId()) {
        unset($flags[$key]);
      }

      if (in_array($account->id(), array_keys($this->flagService->getFlaggingUsers($comment, $flag->getFlag())))) {
        $hasAccountLiked = TRUE;
      }
    }

    return [
      'total' => count($flags),
      'hasAccountLike' => $hasAccountLiked,
    ];
  }

  /**
   * Check permission for a user in group
   *
   * @param int $node_id
   * @param string $permission
   *
   * @return bool
   */
  private function hasPermission(int $node_id, string $permission): bool {
    /** @var \Drupal\node\NodeInterface|NULL $node */
    $node = Node::load($node_id);
    $group_contents = GroupContent::loadByEntity($node);

    // If we are in group.
    if ($group_contents) {
      $access = $this->groupPermissionChecker->getPermissionInGroups(
        $permission,
        $this->currentUser(),
        $group_contents
      );

      $has_access = $access->isAllowed();
    }
    else {
      $user_id = \Drupal::currentUser()->id();
      $user = User::load($user_id);

      $has_access = $user->hasPermission($permission);
    }

    return $has_access;
  }

  /**
   * Render array of comment element.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   *
   * @return array
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  private function renderCommentElement(CommentInterface $comment): array {
    $user = $comment->getOwner();

    /** @var \Drupal\media\MediaInterface|null $media_picture */
    $media_picture = $user->get('field_media')->referencedEntities();
    /** @var File|NULL $file */
    $file = $media_picture ? File::load($media_picture[0]->get('oe_media_image')->target_id) : NULL;
    $file_url = $file ? $this->fileUrlGenerator->transformRelative(file_create_url($file->get('uri')->value)) : NULL;

    $archive_flags = $this->flagService->getEntityFlaggings(
      $this->flagService->getFlagById('request_archive_comment'),
      $comment
    );
    $archived_flag_time = NULL;
    if (!empty($archive_flags)) {
      foreach ($archive_flags as $archive_flag) {
        if ($archive_flag->get('field_request_status')->value === RequestStatus::ACCEPTED) {
          $archived_flag_time = $this->dateFormatter->format(
            $archive_flag->get('created')->value,
            'eu_short_date_hour'
          );
          break;
        }
      }
    }

    $delete_flags = $this->flagService->getEntityFlaggings(
      $this->flagService->getFlagById('request_delete_comment'),
      $comment
    );
    $deleted_flag_time = NULL;
    if (!empty($delete_flags)) {
      foreach ($delete_flags as $delete_flag) {
        if ($delete_flag->get('field_request_status')->value === RequestStatus::ACCEPTED) {
          $deleted_flag_time = $this->dateFormatter->format($delete_flag->get('created')->value, 'eu_short_date_hour');
          break;
        }
      }
    }

    $edited_time = $comment->getCreatedTime() !== $comment->getChangedTime(
    ) && !$deleted_flag_time && !$archived_flag_time ?
      $this->dateFormatter->format($comment->getChangedTime(), 'eu_short_date_hour') :
      NULL;

    $created_time = $this->dateFormatter->format(
      $comment->getCreatedTime(),
      'eu_short_date_hour'
    );
    $soft_deleted = $comment->get('field_comment_is_soft_deleted')->value;
    $archived = $comment->get('field_comment_is_archived')->value;

    $tagged_users = $comment->get('field_tagged_users')->referencedEntities();

    return [
      'user_image' => $file_url,
      'user_id' => $user->id(),
      'user_fullname' => $user->getDisplayName(),
      'user_url' => $user->toUrl()->toString(),
      'created_timestamp' => $comment->getCreatedTime(),
      'text' => check_markup($comment->get('comment_body')->value, 'filtered_html'),
      'comment_id' => $comment->id(),
      'tagged_users' => array_map(function (UserInterface $user) {
        return [
          'uid' => $user->id(),
          'name' => $user->getDisplayName(),
          'url' => $user->toUrl()->toString(),
        ];
      }, $tagged_users),
      'likes' => $this->getCommentLikesData($comment, $this->currentUser()),
      'archived_flag_time' => $archived_flag_time ?
        $this->t('Archived on @time', ['@time' => $archived_flag_time], ['context' => 'eic_groups']) :
        NULL,
      'deleted_flag_time' => $deleted_flag_time ?
        $this->t('Deleted on @time', ['@time' => $deleted_flag_time], ['context' => 'eic_groups']) :
        NULL,
      'soft_deleted_time' => $soft_deleted ?
        $this->t('Deleted on @time', ['@time' => $edited_time], ['context' => 'eic_groups']) :
        NULL,
      'edited_time' => $edited_time ?
        $this->t('Edited on @time', ['@time' => $edited_time ?: $created_time], ['context' => 'eic_groups']) :
        NULL,
      'is_soft_delete' => (int) $soft_deleted,
      'is_archived' => (int) $archived,
      'created_time' => $this->t(
        'Created on @time',
        ['@time' => $created_time],
        ['context' => 'eic_groups']
      ),
    ];
  }

  /**
   * @param int $node_id
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function isGroupArchived(int $node_id): bool {
    $node = Node::load($node_id);

    if (!$node instanceof NodeInterface) {
      return FALSE;
    }

    $group_contents = $this->entityTypeManager->getStorage('group_content')->loadByEntity($node);

    if (empty($group_contents)) {
      return FALSE;
    }

    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    $group_content = reset($group_contents);
    $group = $group_content->getGroup();

    return $group->get('moderation_state')->value === DefaultContentModerationStates::ARCHIVED_STATE &&
      !UserHelper::isPowerUser($this->currentUser());
  }

}
