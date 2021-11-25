<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class UserInvitesListSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class UserInvitesListSourceType extends SourceType {

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
    return $this->t('User invites', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'user_invites';
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

}
