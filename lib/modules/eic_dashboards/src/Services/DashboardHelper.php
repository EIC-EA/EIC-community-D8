<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\Core\Database\Connection;

/**
 * Implements dashboards generic helpers functions.
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

      $links[$item['label']] = [
        '#value' => $key,
        '#label' => $item['label'],
        '#attributes' => [
          'data-url' => $url->toString(),
        ],
      ];
    }

    if (!empty($links)) {
      ksort($links);
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
   * Transforms data array into categories and series format.
   */
  public function transformNameYToCategoriesSeries($data): array {
    $result = [
      'categories' => [],
      'series' => [],
    ];
    foreach ($data as $item) {
      if (isset($item['name']) && isset($item['y'])) {
        $result['categories'][] = $item['name'];
        $result['series'][] = $item['y'];
      }
    }

    return $result;
  }

  /**
   * Builds a flattened term map starting from 2nd-level taxonomy terms.
   *
   * This function:
   * - Skips 1st-level (root) taxonomy terms.
   * - For each 2nd-level term, it gathers all its descendant term IDs, including itself.
   * - Useful for aggregating member counts or content references to taxonomy subtrees.
   *
   * @param string $vid
   *   The vocabulary machine name (e.g. 'topics').
   *
   * @return array
   *   An associative array keyed by 2nd-level term TID. Each item includes:
   *   - 'name' => string, the term's name.
   *   - 'tids' => int[], a flat list of TIDs (self + all descendants).
   *   - 'count' => int, initialized to 0 for later use.
   */
  public function getNestedTidTree(string $vid): array {
    $result = [];

    // Step 1: Load top-level (1st-level) terms: parent = 0
    $topLevelTerms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vid, 0, 1, FALSE);

    // Step 2: For each 1st-level term, load its 2nd-level children only
    foreach ($topLevelTerms as $firstLevelTerm) {
      $secondLevelTerms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadTree($vid, $firstLevelTerm->tid, 1, FALSE);

      // Step 3: For each 2nd-level term, collect its descendants
      foreach ($secondLevelTerms as $secondLevel) {
        // Get all descendants under this term (level 3 and deeper)
        $descendants = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadTree($vid, $secondLevel->tid, NULL, FALSE);

        // Build a flat list of tids: self + all nested
        $tids = [$secondLevel->tid];
        foreach ($descendants as $descendant) {
          $tids[] = $descendant->tid;
        }

        // Build output record
        $result[$secondLevel->tid] = [
          'name' => $secondLevel->name,
          'tids' => $tids,
          'count' => 0,
        ];
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function transformTermTreeCountsForChart(array $rawData, string $vid): array {
    // Step 1: Get group structure from 2nd-level terms and their nested tids
    $secondLevelTerms = $this->getNestedTidTree($vid);

    // Step 2: Build reverse map: tid => group_tid (NOT reference to group array)
    $nestedTidToParent = [];
    foreach ($secondLevelTerms as $secondLevelTid => $term) {
      foreach ($term['tids'] as $tid) {
        $nestedTidToParent[$tid] = $secondLevelTid;
      }
    }

    // Step 3: Loop over flat counts and assign to the correct group
    foreach ($rawData as $tid => $count) {
      if (isset($nestedTidToParent[$tid])) {
        $secondLevelTid = $nestedTidToParent[$tid];
        $secondLevelTerms[$secondLevelTid]['count'] += (int) $count['count'];
      }
    }

    // Step 4: Prepare chart-ready output
    $result = [];
    foreach ($secondLevelTerms as $secondLevelTid => $term) {
      // Return only items with a count greater than zero.
      if($term['count'] === 0) {
        continue;
      }

      $result[] = [
        'name' => $term['name'],
        'y' => $term['count'],
      ];
    }

    // Step 5: Sort by count descending (optional)
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
