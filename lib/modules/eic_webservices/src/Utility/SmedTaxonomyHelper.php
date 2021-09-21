<?php

namespace Drupal\eic_webservices\Utility;

/**
 * Provides helper functions for the SMED taxonomy webservice.
 */
class SmedTaxonomyHelper {

  /**
   * Returns the parent term ID for the given vocabulary and child term.
   *
   * This function tries to guess the parent term ID, based on the given child
   * term ID. It does so with some logic based on the webservice output.
   *
   * @param string $vocabulary_name
   *   The vocabulary identifier provided by SMED webservice.
   * @param string $child_id
   *   The ID of the child term for which we want to find the parent.
   *
   * @return string|NULL
   *   The matched parent ID or 0 if parent ID couldn't be resolved.
   */
  public static function findTermParentId(string $vocabulary_name, string $child_id) {
    switch ($vocabulary_name) {
      case 'ThematicsTopics':
        return self::resolveThematicsTopicsParentId($child_id);
    }

    return NULL;
  }

  /**
   * Returns the parent ID for the given Child term ID.
   *
   * This function is specific for the ThematicsTopics SMED vocabulary.
   * The ID structure is as follows:
   * - T
   *   - T1
   *   - T2
   *   - ...
   * - H
   *   - H1
   *   - H2
   *     - H2-1
   *     - H2-2
   *     - ...
   *
   * Where T stands for "Thematic" and H "Horizontal".
   *
   * @param string $child_id
   *   The ID of the child term for which we want to find the parent.
   *
   * @return string|NULL
   *   The matched parent ID or NULL if parent ID couldn't be resolved.
   */
  protected static function resolveThematicsTopicsParentId(string $child_id) {
    $regex = '/^((H|T)(?:[0-9]*))(?:-[0-9]*)?$/';
    preg_match($regex, $child_id, $matches);
    // This regex always returns 3 matches, which represent the 3 levels. We
    // return the first one that is different from the original one.
    foreach ($matches as $item) {
      if ($item != $child_id) {
        return $item;
      }
    }
    return NULL;
  }

}
