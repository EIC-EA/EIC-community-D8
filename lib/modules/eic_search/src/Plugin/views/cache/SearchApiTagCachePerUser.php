<?php

namespace Drupal\eic_search\Plugin\views\cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\search_api\Plugin\views\cache\SearchApiTagCache;

/**
 * Defines a tag-based per user cache plugin for use with Search API views.
 *
 * @see SearchApiTagCache
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 *   id = "search_api_tag_per_user",
 *   title = @Translation("Search API (tag-based per user)"),
 *   help = @Translation("Cache results until the associated cache tags are invalidated. Useful for small sites that use the database search backend. <strong>Caution:</strong> Can lead to stale results and might harm performance for complex search pages.")
 * )
 */
class SearchApiTagCachePerUser extends SearchApiTagCache {

  /**
   * {@inheritdoc}
   */
  public function alterCacheMetadata(CacheableMetadata $cache_metadata) {
    parent::alterCacheMetadata($cache_metadata);
    // Add cache context per user.
    $cache_metadata->addCacheContexts(['user']);
  }

}
