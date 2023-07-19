<?php

namespace Drupal\eic_content_gallery\Controller;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_content_gallery\Constants\GalleryNode;
use Drupal\eic_helper\FileArchiver;
use Drupal\node\NodeInterface;
use Drupal\pathauto\AliasCleanerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Archive download controller class.
 */
class ArchiveDownloadController extends ControllerBase {

  /**
   * The files archiver.
   *
   * @var \Drupal\eic_helper\FileArchiver
   */
  protected $archiver;

  /**
   * The alias cleaner service.
   *
   * @var \Drupal\pathauto\AliasCleanerInterface
   */
  protected $aliasCleaner;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * ArchiveDownloadController constructor.
   *
   * @param \Drupal\eic_helper\FileArchiver $archiver
   *   The files archiver.
   * @param \Drupal\pathauto\AliasCleanerInterface $alias_cleaner
   *   The alias cleaner service.
   * @param \Drupal\Component\Datetime\Time $time
   *   The time service.
   */
  public function __construct(FileArchiver $archiver, AliasCleanerInterface $alias_cleaner, Time $time) {
    $this->archiver = $archiver;
    $this->aliasCleaner = $alias_cleaner;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_helper.file_archiver'),
      $container->get('pathauto.alias_cleaner'),
      $container->get('datetime.time'),
    );
  }

  /**
   * Creates the archive and send it to the browser.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The gallery node for which to create the archive.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The archive file.
   */
  public function downloadGallery(NodeInterface $node) {
    $date = DrupalDateTime::createFromTimestamp($this->time->getRequestTime());
    $node_title_slug = $this->aliasCleaner->cleanString($node->getTitle());
    $filename = $node_title_slug . '_' . $date->format('c') . '.zip';

    $file_entities = [];

    // Get all the file entities for this gallery.
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    foreach ($node->get(GalleryNode::FIELD_SLIDES)->referencedEntities() as $paragraph) {
      /** @var \Drupal\media\MediaInterface[] $medias */
      if ($medias = $paragraph->get('field_gallery_slide_media')->referencedEntities()) {
        foreach ($medias as $media) {
          /** @var \Drupal\file\FileInterface $file */
          foreach ($media->get('oe_media_image')->referencedEntities() as $file) {
            // At this point, the user should have access to all files of the
            // gallery, but we do an extra check just to be sure.
            if ($file->access('view')) {
              $file_entities[] = $file;
            }
          }
        }
      }
    }

    $archive = $this->archiver->archive($file_entities);

    $response = new BinaryFileResponse($archive);
    $response->headers->set('Content-Type', 'application/zip');
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

    return $response;
  }

  /**
   * Checks access for the archive download.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account being checked.
   * @param \Drupal\node\NodeInterface $node
   *   The node object being accessed.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, NodeInterface $node) {
    return AccessResult::allowedIf($account->hasPermission('download gallery archive')
      && $node->access('view', $account));
  }

}
