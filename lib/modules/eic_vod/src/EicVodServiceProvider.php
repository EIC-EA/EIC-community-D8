<?php

namespace Drupal\eic_vod;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Site\Settings;

/**
 * Class EicVodServiceProvider
 *
 * @package Drupal\eic_vod
 */
class EicVodServiceProvider extends ServiceProviderBase {

  /**
   * Modifies existing service definitions.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The ContainerBuilder whose service definitions can be altered.
   */
  public function alter(ContainerBuilder $container) {
    $eic_vod_settings = Settings::get('eic_vod');
    if (empty($eic_vod_settings)
      || !isset($eic_vod_settings['cloudfront_url'])
      || !isset($eic_vod_settings['cloudfront_api_key'])
      && $container->hasDefinition('stream_wrapper.private')
    ) {
      // Replace the vod stream with the private stream if the required settings are not provided.
      $container->getDefinition('eic_vod.video_wrapper')
        ->setClass('Drupal\Core\StreamWrapper\PrivateStream');
    }
  }

}
