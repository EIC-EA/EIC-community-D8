<?php

namespace Drupal\eic_flags;

/**
 * EIC block flag types.
 *
 * @package Drupal\eic_flags
 */
final class BlockFlagTypes {

  const BLOCK_GROUP = 'block_group';

  /**
   * Returns an array of supported entity types for block flags.
   *
   * @return array
   *   Array of supported entity types for the block flag types.
   */
  public static function getSupportedEntityTypes() {
    return [
      'group' => 'block_group',
    ];
  }

}
