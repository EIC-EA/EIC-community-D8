<?php

namespace Drupal\eic_content_wiki_page;

use Drupal\book\BookManager;

/**
 * Extends a book manager service with custom logic for wiki pages.
 */
class WikiPageBookManager extends BookManager {

  /**
   * Overrides the maximum supported depth of the book tree.
   */
  const BOOK_MAX_DEPTH = 4;

  /**
   * Builds the parent selection form element for the node form or outline tab.
   *
   * This function is also called when generating a new set of options during
   * the Ajax callback, so an array is returned that can be used to replace an
   * existing form element.
   *
   * @param array $book_link
   *   A fully loaded book link that is part of the book hierarchy.
   *
   * @return array
   *   A parent selection form element.
   */
  public function addWikiPageParentSelectFormElements(array $book_link) {
    $book_link['parent_depth_limit'] = self::BOOK_MAX_DEPTH;
    return $this->addParentSelectFormElements($book_link);
  }

}
