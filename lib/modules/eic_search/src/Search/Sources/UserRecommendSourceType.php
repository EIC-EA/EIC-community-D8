<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class UserRecommendSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class UserRecommendSourceType extends SourceType {

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
    return $this->t('User recommend', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'user_recommend';
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
    return 'user-recommend-' . parent::getUniqueId();
  }

}
