<?php

/**
 * @file
 * Prepares variables for paragraph banner template.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Implements hook_preprocess_paragraph().
 */
function eic_community_preprocess_paragraph__contributor(array &$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];

  $contributor = [
    'icon_file_path' => $variables['eic_icon_path'],
    'size' => 'medium',
  ];

  if ($paragraph->paragraph_view_mode->value === 'external_person') {
    $contributor['name'] = $paragraph->field_name->value;

    $link = $paragraph->field_contributor_link->getValue();
    if (count($link) > 0 && $variables['logged_in']) {
      $contributor['path'] = $link[0]['uri'];
    }

    $organisation = $paragraph->field_organisation->getString();

    if (strlen($organisation) > 0) {
      $contributor['description'] = $organisation;
    }

    if (!$paragraph->get('field_media')->isEmpty()) {
      /** @var \Drupal\media\Entity\Media $media */
      $media = $paragraph->get('field_media')->entity;
      $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
      $contributor['image'] = [
        'src' => $image_item->getSource(),
        'alt' => $image_item->getAlt(),
      ];
    }

    $mail = $paragraph->field_person_email->getString();

    if (strlen($mail) > 0 && $variables['logged_in']) {
      $contributor['actions'][] = [
        'label' => t('e-mail'),
        'path' => 'mailto:' . $mail,
        'icon' => [
          'type' => 'custom',
          'name' => 'mail',
        ],
      ];
    }
  }
  else {
    $user = User::load($paragraph->field_user_ref->getString());
    if ($user instanceof UserInterface) {
      $user_display = eic_community_get_teaser_user_display($user);

      $contributor = array_merge($contributor, $user_display);
    }
  }

  $variables['author'] = $contributor;
}
