<?php

namespace Drupal\eic_contact\Constant;

final class CategoryEmail {

  const CATEGORY_ID_ANY = 'any';

  const CATEGORY_ID_REPORT = 'report';

  const CATEGORY_ID_ORGANISATION = 'organisation';

  /**
   * Return a mapping of field id|label|email target.
   *
   * @return array[]
   */
  public static function mapCategoryEmails(): array {
    return [
      self::CATEGORY_ID_ANY => [
        'label' => t('- Any -'),
        'mail' => 'any@eic.net',
      ],
      self::CATEGORY_ID_REPORT => [
        'label' => t('Report'),
        'mail' => 'report@eic.net',
      ],
      self::CATEGORY_ID_ORGANISATION => [
        'label' => t('Organisation'),
        'mail' => 'organisation@eic.net',
      ],
    ];
  }

}
