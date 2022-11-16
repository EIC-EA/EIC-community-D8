<?php

namespace Drupal\eic_user\Hooks;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Class GroupTokens.
 *
 * Implementations of token hooks.
 */
class UserTokens {

  use StringTranslationTrait;

  /**
   * Implements hook_token_info().
   */
  public function tokenInfo() {
    $info = [];
    $info['tokens']['site'] = [
      'eic-member-access-page-url' => [
        'name' => $this->t('EIC Member access page url'),
        'description' => $this->t('The url of the EIC Member access page'),
      ],
    ];

    return $info;
  }

  /**
   * Implements hook_tokens().
   */
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $replacements = [];

    switch ($type) {
      case 'site':
        // Site tokens.
        $replacements = $this->siteTokens($tokens, $data, $options, $bubbleable_metadata);
        break;

    }

    return $replacements;
  }

  /**
   * Replace site tokens.
   *
   * @param mixed $tokens
   *   An array of tokens to be replaced.
   * @param array $data
   *   An associative array of data objects to be used when generating
   *   replacement values.
   * @param array $options
   *   An associative array of options for token replacement.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata.
   *
   * @return array
   *   An associative array of replacement values.
   */
  private function siteTokens($tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $replacements = [];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'eic-member-access-page-url':
          $replacements[$original] = Url::fromRoute('eic_user_login.member_access', [], ['absolute' => TRUE])
            ->toString();
          break;

      }
    }

    return $replacements;
  }

}
