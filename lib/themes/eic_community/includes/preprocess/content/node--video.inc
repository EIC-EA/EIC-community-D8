<?php

/**
 * @file
 * Prepares variables for node video templates.
 */

/**
 * Implements hook_preprocess_node__bundle() for vide node.
 */
function eic_community_preprocess_node__video(array &$variables) {
  switch ($variables['view_mode']) {
    case 'full':
      $request = Drupal::request();

      $variables['parent']['link'] = [
        'label' => t('Previous page'),
        'path' => $request->request->get('referer', 'javascript:history.go(-1)'),
      ];
      $variables['icon_file_path'] = $variables['eic_icon_path'];

      _eic_community_display_flags($variables);
      _eic_community_display_topics($variables);
      _eic_community_preprocess_video_contributors($variables);

      // Provide the Social Share block.
      if ($share_block = _eic_community_get_social_share_block()) {
        $variables['social_share'] = $share_block;
      }
      break;
  }
}

/**
 * Preprocess video contributors to show in the detail page.
 */
function _eic_community_preprocess_video_contributors(array &$variables) {
  if (!empty($variables['elements']['contributor_ids'])) {
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($variables['elements']['contributor_ids']);
    $contributors = ['items' => []];

    foreach ($users as $user) {
      $contributors['items'][] = eic_community_get_teaser_user_display($user);
    }

    $variables['contributors'] = $contributors;
  }
}
