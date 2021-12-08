<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block() for page_banner.
 */

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;

/**
 * Implements hook_preprocess_block() for basic.
 */
function eic_community_preprocess_block__basic(&$variables, &$cache_tags = []) {
  /** @var \Drupal\block_content\Entity\BlockContent $block_content */
  $variables['entity'] = $variables['content']['#block_content'];
  if ($variables['user'] instanceof AccountProxyInterface && $variables['user']->isAuthenticated()) {
    $variables['user_entity'] = User::load($variables['user']->id());
  }
}
