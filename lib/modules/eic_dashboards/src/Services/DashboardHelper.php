<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\Core\Database\Connection;

/**
 * Implements dashboards helpers.
 */
class DashboardHelper implements DashboardHelperInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected RouteProviderInterface $routeProvider;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected UrlGeneratorInterface $urlGenerator;

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected TermStorageInterface $termStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    Connection $connection,
    RouteProviderInterface $routeProvider,
    UrlGeneratorInterface $urlGenerator,
  ) {
    $this->connection = $connection;
    $this->routeProvider = $routeProvider;
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('router.route_provider'),
      $container->get('url_generator'),
    );
  }

  /**
   * Get title from routing.
   */
  public function getRoutingTitle($route_name): string {
    $route = $this->routeProvider->getRouteByName($route_name);
    return (string) $route->getDefault('_title');
  }

  /**
   * Get URL from routing.
   */
  public function getRoutingUrl($route_name): string {
    return $this->urlGenerator->generateFromRoute($route_name, [], ['absolute' => TRUE]);
  }

  /**
   * Transforms data array into name and y format.
   */
  public function transformLabelCountToNameAndY($data): array {
    $result = [];

    foreach ($data as $item) {
      if (isset($item['label']) && isset($item['count'])) {
        $result[] = [
          'name' => $item['label'],
          'y' => $item['count'],
        ];
      }
    }

    return $result;
  }

  /**
   * Prepares data for jump menu, links to View with one argument.
   */
  public function prepareDataForJumpMenu($items, $viewMachineName, $routeParameters, $argumentIds): array {
    foreach ($items as $key => $item) {
      $query = [];
      foreach ($argumentIds as $argumentId) {
        if (isset($item[$argumentId])) {
          $query[$argumentId] = $item[$argumentId];
        }
      }

      $url = Url::fromRoute($viewMachineName, $routeParameters, ['query' => $query]);

      $links[$key] = [
        '#value' => $key,
        '#label' => $item['label'],
        '#attributes' => [
          'data-url' => $url->toString(),
        ],
      ];
    }

    if (!empty($links)) {
      return $links;
    }
    else {
      return [];
    }
  }

  /**
   * Encodes given data to JSON format for chart use.
   */
  public function jsonEncodeCategoriesSeries($data): array {
    return [
      'categories' => json_encode($data['categories']),
      'series' => json_encode($data['series'], JSON_NUMERIC_CHECK),
    ];
  }

  /**
   * Transforms data array into categories and series format.
   */
  public function transformIdCountToCategoriesSeries($data): array {
    $result = [
      'categories' => [],
      'series' => [],
    ];
    foreach ($data as $item) {
      if (isset($item['id']) && isset($item['count'])) {
        $result['categories'][] = $item['id'];
        $result['series'][] = $item['count'];
      }
    }

    return $result;
  }

  /**
   * Loads a flat tree of 2nd-level terms and collapses deeper terms under them.
   *
   * @param string $vid
   *   Vocabulary machine name.
   *
   * @return array
   *   Array of flat term objects at depth 1 or their collapsed children.
   */
  function loadTreeCollapsedToSecondLevel(string $vid): array {
    // TODO: The result could be statically cached.
    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    // Load full tree, no entity loading.
    $full_tree = $term_storage->loadTree($vid, 0, NULL, FALSE);

    $terms = [];
    $second_level = [];

    // Index terms by tid.
    $term_index = [];
    foreach ($full_tree as $term) {
      $term_index[$term->tid] = $term;
    }

    // Step 1: Get second-level terms.
    foreach ($full_tree as $term) {
      if ($term->depth === 1) {
        $second_level[$term->tid] = $term;
      }
    }

    // Step 2: Process all terms, collapsing deeper levels.
    foreach ($full_tree as $term) {
      if ($term->depth === 1) {
        // Keep second-level term as-is.
        $terms[$term->tid] = $term;
      }
      elseif ($term->depth > 1) {
        // Traverse up to find 2nd-level ancestor.
        $parent_tid = reset($term->parents);
        while (!empty($term_index[$parent_tid]) && $term_index[$parent_tid]->depth > 1) {
          $parent_tid = reset($term_index[$parent_tid]->parents);
        }

        // If a valid second-level ancestor exists, collapse under it.
        if (isset($second_level[$parent_tid])) {
          $collapsed_term = clone $term;
          $collapsed_term->name = $second_level[$parent_tid]->name;
          $collapsed_term->tid = $term->tid;
          $collapsed_term->pid = $parent_tid;
          $collapsed_term->collapsed = TRUE;
          $collapsed_term->depth = 1; // flatten it

          $terms[$collapsed_term->tid] = $collapsed_term;
        }
      }
    }

    return array_values($terms);
  }

  /**
   * Formats member counts for chart display, collapsing nested taxonomy terms.
   *
   * This method:
   * - Loads a flat taxonomy tree with second-level terms and their collapsed children.
   * - Reassigns counts from collapsed (nested) terms to their 2nd-level parents.
   * - Outputs a flat array formatted for charting libraries (e.g. Highcharts).
   *
   * @param array $rawData
   *   Raw member count data keyed by term ID, each item includes:
   *   - id: (int|string) The taxonomy term ID.
   *   - label: (string) The term name.
   *   - count: (int) The member count.
   *
   * @param string $vid
   *   The vocabulary machine name to use for taxonomy term tree loading.
   *
   * @return array
   *   A list of arrays formatted for highcharts as:
   *   - tid: (int) Taxonomy term ID.
   *   - name: (string) The term label.
   *   - y: (int) The total member count (collapsed and direct).
   */
  public function formatCollapsedCountsForChart(array $rawData, string $vid): array {
    $flat_tree = $this->loadTreeCollapsedToSecondLevel($vid);

    $collapsed_terms_parent_map = []; // Maps collapsed term TIDs to their 2nd-level parent TID.
    $second_level_tids = [];          // Used for fast lookup of 2nd-level term IDs.
    $second_level_sum_counts = [];    // Stores aggregated member counts per 2nd-level TID.

    // Step 1: Build term maps and initialize counts
    foreach ($flat_tree as $term) {
      $tid = (int) $term->tid;

      if (!empty($term->collapsed)) {
        $collapsed_terms_parent_map[$tid] = (int) $term->pid; // Map collapsed term to its 2nd-level parent.
      }
      else {
        // Initialize counter for each 2nd-level term.
        $second_level_tids[$tid] = TRUE;
        $second_level_sum_counts[$tid] = 0;
      }
    }

    // Step 2: Aggregate counts
    foreach ($rawData as $tid => $item) {
      $tid = (int) $tid;
      $count = (int) ($item['count'] ?? 0);

      // Skip unknown TIDs that are neither 2nd-level nor collapsed.
      if (!isset($second_level_tids[$tid]) && !isset($collapsed_terms_parent_map[$tid])) {
        continue;
      }

      // Resolve to 2nd-level TID: if collapsed, use its parent.
      $target_tid = $collapsed_terms_parent_map[$tid] ?? $tid;

      // Defensive check: only aggregate to known 2nd-level terms.
      if (!isset($second_level_sum_counts[$target_tid])) {
        continue;
      }

      $second_level_sum_counts[$target_tid] += $count;
    }

    // Step 3: Format result for charts.
    $result = [];
    foreach ($second_level_sum_counts as $tid => $count) {
      $result[] = [
        'tid' => $tid,
        'name' => $rawData[$tid]['label'],
        'y' => $count,
      ];
    }

    // Sort by count descending
    usort($result, fn($a, $b) => $b['y'] <=> $a['y']);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getTaxonomyTermLabel(string $tid): string {
    static $labelCache = [];

    if (!isset($labelCache[$tid])) {
      $termName = $this->connection->select('taxonomy_term_field_data', 'ttfd')
        ->fields('ttfd', ['name'])
        ->condition('tid', $tid)
        ->execute()
        ->fetchField();

      $labelCache[$tid] = $termName ?: "[Orphan term #{$tid}]";
    }

    return $labelCache[$tid];
  }

  /**
   * {@inheritdoc}
   */
  public function getListFieldValue(string $entityTypeId, string $fieldName, string $listItemValue): string {
    static $allowedValuesCache = [];

    $cacheKey = $entityTypeId . ':' . $fieldName;

    if (!isset($allowedValuesCache[$cacheKey])) {
      $configName = "field.storage.{$entityTypeId}.{$fieldName}";

      $settings = $this->connection->select('config', 'c')
        ->fields('c', ['data'])
        ->condition('name', $configName)
        ->execute()
        ->fetchField();

      if ($settings) {
        $decodedSettings = unserialize($settings);
        $allowedValues = array_column($decodedSettings['settings']['allowed_values'], 'label', 'value');
        $allowedValuesCache[$cacheKey] = $allowedValues ?? [];
      } else {
        $allowedValuesCache[$cacheKey] = [];
      }
    }

    return $allowedValuesCache[$cacheKey][$listItemValue] ?? $listItemValue;
  }

}
