<?php

namespace Drupal\eic_search\Search\Sources;

use Drupal\Core\StringTranslation\StringTranslationTrait;

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
    return ['timestamp', 'DESC'];
  }

  /**
   * @inheritDoc
   */
  public function getLayoutTheme(): string {
    return self::LAYOUT_COLUMNS_COMPACT;
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
  public function getPrefilteredGroupFieldId(): string {
    return 'its_group_id';
  }

  /**
   * @inheritDoc
   */
  public function allowPagination(): bool {
    return FALSE;
  }

}
