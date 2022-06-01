<?php

/**
 * @file
 * Prepares variables for media templates.
 */

/**
 * Implements template_template_preprocess_media().
 */
function eic_community_preprocess_media(array &$variables) {
  // We don't want to show a link to the media edit form.
  if (isset($variables['content']['name'])) {
    unset($variables['content']['name']);
  }
}
