<?php

/**
 * @file
 * Contains implementation for video media preprocessor.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function eic_community_preprocess_field__field_video_media(array &$variables): void {
  eic_community_preprocess_field__field_related_downloads($variables);
}
