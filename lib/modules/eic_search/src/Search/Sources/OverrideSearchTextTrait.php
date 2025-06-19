<?php

namespace Drupal\eic_search\Search\Sources;

/**
 * Use this trait to override the custom search text in specific cases.
 */
trait OverrideSearchTextTrait {

  /**
   * Override the custom search text for specific use cases.
   *
   * @return string
   *
   */
  abstract public function getCustomSearchText();

}
