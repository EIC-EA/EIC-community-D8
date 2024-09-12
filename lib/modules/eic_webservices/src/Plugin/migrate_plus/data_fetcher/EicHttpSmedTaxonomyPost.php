<?php

namespace Drupal\eic_webservices\Plugin\migrate_plus\data_fetcher;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\Http;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
/**
 * Retrieve data over a SMED API HTTP connection for migration.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: url
 *   data_fetcher_plugin: eic_http_smed_taxonomy_post
 *   taxonomy_vocabulary_smed_id: ISO3166Countries
 *   headers:
 *     Accept: application/json
 *     User-Agent: Internet Explorer 6
 *     Authorization-Key: secret
 *     Arbitrary-Header: foobarbaz
 * @endcode
 *
 * @DataFetcher(
 *   id = "eic_http_smed_taxonomy_post",
 *   title = @Translation("EIC HTTP SMED Taxonomy Post")
 * )
 */
class EicHttpSmedTaxonomyPost extends Http implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getResponse($url): ResponseInterface {
    try {
      // First check if we have vocabulary ID to query on.
      if (empty($this->configuration['taxonomy_vocabulary_smed_id'])) {
        throw new MigrateException('No key specified for taxonomy_vocabulary_smed_id parameter.');
      }

      $options = ['headers' => $this->getRequestHeaders()];
      if (!empty($this->configuration['authentication'])) {
        $options = array_merge($options, $this->getAuthenticationPlugin()->getAuthenticationOptions());
      }
      // Add the body required by SMED API.
      $body = [];
      $body['Name'] = $this->configuration['taxonomy_vocabulary_smed_id'];
      $options['json'] = $body;

      $response = $this->httpClient->post($url, $options);
      if (empty($response)) {
        throw new MigrateException('No response at ' . $url . '.');
      }
    }
    catch (RequestException $e) {
      throw new MigrateException('Error message: ' . $e->getMessage() . ' at ' . $url . '.');
    }
    return $response;
  }

}
