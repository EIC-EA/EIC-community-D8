<?php

/**
 * @file
 * Contains implementation for media preprocess hook.
 */

/**
 * Implements hook_preprocess_media__document__eic_community_main_content().
 *
 * Style the view mode used for embedding the document media entity.
 */
function eic_community_preprocess_media__document__eic_community_main_content(&$variables) {
  eic_community_preprocess_media__document__default($variables);
}
