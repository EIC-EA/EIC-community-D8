<?php

namespace Drupal\eic_helper;

use Drupal\social_link_field\SocialLinkFieldPlatformManager;

/**
 * Provides helper methods for social_link type fields.
 *
 * This class applies to social_link_field module.
 */
class SocialLinksFieldHelper {

  /**
   * The Social Links field platform manager.
   *
   * @var \Drupal\social_link_field\SocialLinkFieldPlatformManager
   */
  protected $platformManager;

  /**
   * An array containing all available social platforms.
   *
   * @var array
   */
  protected $socialPlatforms = [];

  /**
   * @param \Drupal\social_link_field\SocialLinkFieldPlatformManager $platform_manager
   */
  public function setSocial(?SocialLinkFieldPlatformManager $platform_manager) {
    $this->platformManager = $platform_manager;
    $this->socialPlatforms = $this->platformManager->getPlatforms();
  }

  /**
   * Processes the links for social link fields to match the field constraints.
   *
   * @param string $social_name
   *   The name of the social platform.
   * @param string $value
   *   The link value.
   *
   * @return string
   *   The cleaned up value.
   */
  public function cleanUpSocialLinkValue(string $social_name, string $value) {
    // If value is empty, do nothing.
    if (strlen(trim($value)) === 0) {
      return $value;
    }

    // If social link platform is unknown, return the value as-is.
    if (!array_key_exists($social_name, $this->socialPlatforms)) {
      return $value;
    }
    $social = $this->socialPlatforms[$social_name];

    // Make sure full URLs start with "www".
    $link = str_replace('https://' . $social_name, 'https://www.' . $social_name, $value);

    $link = str_replace($social['urlPrefix'], '', $link);

    // Exception for LinkedIn since we need to prepend "in/" if missing.
    if ($social_name === 'linkedin') {
      // Remove backslash from the beginning if exists.
      if (substr($link, 0, 1) === '/') {
        $link = substr($link, 1);
      }

      if (substr($link, 0, 3) !== 'in/') {
        $link = 'in/' . $link;
      }

      // If the link value only contains the base LinkedIn path, we clean
      // up the value.
      if ($link === 'in/') {
        $link = '';
      }
    }

    return $link;
  }

}
