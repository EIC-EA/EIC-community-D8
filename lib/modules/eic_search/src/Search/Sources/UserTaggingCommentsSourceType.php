<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class UserTaggingCommentsSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class UserTaggingCommentsSourceType extends SourceType {

  use StringTranslationTrait;

  /**
   * @inheritDoc
   */
  public function getSourcesId(): array {
    return ['user'];
  }

  /**
   * @inheritDoc
   */
  public function getLabel(): string {
    return $this->t('User tagging comments', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'user_tagging_comment';
  }

  /**
   * @inheritDoc
   */
  public function getSearchFieldsId(): array {
    return ['ss_global_fullname', 'ss_user_mail'];
  }

  /**
   * @inheritDoc
   */
  public function prefilterByGroupVisibility(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getAuthorFieldId(): string {
    return 'its_user_id';
  }

  /**
   * @inheritDoc
   */
  public function ignoreAnonymousUser(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getUniqueId(): string {
    return 'user-tagging-comments-' . parent::getUniqueId();
  }

}
