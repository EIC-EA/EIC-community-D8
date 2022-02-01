<?php

namespace Drupal\eic_search\Search\Sources\User;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_search\Search\Sources\SourceType;

/**
 * Class ActivityStreamSourceType
 *
 * @package Drupal\eic_groups\Search\Sources
 */
class ActivityStreamSourceType extends SourceType {

  use StringTranslationTrait;

  /**
   * @inheritDoc
   */
  public function getSourcesId(): array {
    return ['message'];
  }

  /**
   * @inheritDoc
   */
  public function getLabel(): string {
    return $this->t('Activity stream', [], ['context' => 'eic_search']);
  }

  /**
   * @inheritDoc
   */
  public function getEntityBundle(): string {
    return 'activity_stream';
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSort(): array {
    return ['ds_created', 'DESC'];
  }

  /**
   * @inheritDoc
   */
  public function getLayoutTheme(): string {
    return self::LAYOUT_GLOBAL;
  }

  /**
   * @inheritDoc
   */
  public function ableToPrefilteredByGroup(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getPrefilteredGroupFieldId(): array {
    return ['its_group_id'];
  }

  /**
   * @inheritDoc
   */
  public function getPrefilteredTopicsFieldId(): array {
    return ['itm_message_node_ref_field_vocab_topics'];
  }

  /**
   * @inheritDoc
   */
  public function allowPagination(): bool {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function prefilterByCurrentUser(): bool {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getUserFieldId(): string {
    return '';
  }

}
