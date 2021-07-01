<?php

/**
 * @file
 * Contains implementation for hook_preprocess_page().
 */

use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Implements hook_preprocess_page().
 */
function eic_community_preprocess_page(array &$variables): void {
  $variables['main_attributes'] = new Attribute([
    'class' => ['ecl-u-pb-xl'],
    'id' => ['main-content'],
  ]);

  foreach ($variables['page']['content'] as $key => $content_section) {
    if (isset($content_section['#theme']) && $content_section['#theme'] === 'block') {
      $variables['page']['content'][$key]['#attributes']['class'][] = 'ecl-section-wrapper';
    }
  }

  $variables['site_header'] = [
    'icon_file_path' =>  $variables['eic_icon_path'],
    'logo' => [
      'title' =>  t('European Commission'),
      'alt' =>  t('European Commission logo'),
      'path' => \Drupal::urlGenerator()->generateFromRoute('<front>'),
      'src_desktop' => $variables['eic_logo_path'] . '/logo--' . $variables['language']->getId() . '.svg',
    ]
  ];

  if ($variables['user']->isAnonymous()) {
    $variables['site_header']['login']['link'] = [
      'label' => t('Log in'),
      'path' => Url::fromRoute('user.login'),
    ];
  }else{
    $account = \Drupal\user\Entity\User::load($variables['user']->id());
    $user = eic_community_get_teaser_user_display($account);
    unset($user['path']);

    $user['actions'] = [
      [
        'link' => [
          'label' => t('My profile'),
          'path' => $account->toUrl()->toString(),
        ],
      ],
      [
        'link' => [
          'label' => t('Log out'),
          'path' => '/user/logout',
        ],
      ],
    ];

    $variables['site_header']['user'] = $user;
  }
}
