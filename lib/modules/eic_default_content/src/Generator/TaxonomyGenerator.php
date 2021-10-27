<?php

namespace Drupal\eic_default_content\Generator;

/**
 * Generates default content for taxonomy.
 *
 * @package Drupal\eic_default_content\Generator
 */
class TaxonomyGenerator extends CoreGenerator {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createLanguages();
    $this->createGeoTerms();
    $this->createDocumentTypes();
    $this->createJobTitles();
    $this->createTopics();
    $this->createFundingSources();
    $this->createEventTypes();
  }

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    $this->unloadEntities('taxonomy_term', ['vid' => 'languages']);
    $this->unloadEntities('taxonomy_term', ['vid' => 'geo']);
    $this->unloadEntities('taxonomy_term', ['vid' => 'topics']);
    $this->unloadEntities('taxonomy_term', ['vid' => 'document_types']);
    $this->unloadEntities('taxonomy_term', ['vid' => 'job_titles']);
    $this->unloadEntities('taxonomy_term', ['vid' => 'funding_source']);
    $this->unloadEntities('taxonomy_term', ['vid' => 'event_type']);
  }

  /**
   * Creates 'topics' terms.
   */
  private function createTopics() {
    $topics = [
      'Horizontal' => [
        'Financial development',
        'Marketing',
        'Women entrepreneurship',
      ],
      'Topic' => [
        'Biotech',
        'Energy',
        'Finance',
        'Health',
      ],
    ];

    foreach ($topics as $parent => $sub_topics) {
      $parent = $this->createTerm('topics', ['name' => $parent]);
      foreach ($sub_topics as $sub_topic) {
        $this->createTerm('topics', [
          'name' => $sub_topic,
          'parent' => $parent->id(),
        ]);
      }
    }
  }

  /**
   * Creates 'funding_source' terms.
   */
  private function createFundingSources() {
    $terms = ['Regional', 'EC', 'National'];
    foreach ($terms as $term) {
      $this->createTerm('funding_source', ['name' => $term]);
    }
  }

  /**
   * Creates 'job_titles' terms.
   */
  private function createJobTitles() {
    for ($i = 0; $i < 10; $i++) {
      $this->createTerm('job_titles', ['name' => $this->faker->jobTitle]);
    }
  }

  /**
   * Create 'document_types' terms.
   */
  private function createDocumentTypes() {
    $types = [
      'Communication material' => [
        'Newsletter',
        'Press release',
        'Picture',
      ],
      'EU official document',
      'Other',
    ];

    foreach ($types as $key => $type) {
      if (is_array($type)) {
        $parent = $this->createTerm('document_types', ['name' => $key]);
        foreach ($type as $sub_type) {
          $this->createTerm('document_types', [
            'name' => $sub_type,
            'parent' => $parent->id(),
          ]);
        }
      }
      else {
        $this->createTerm('document_types', ['name' => $type]);
      }
    }
  }

  /**
   * Creates 'geo' terms.
   */
  private function createGeoTerms() {
    for ($i = 0; $i < 5; $i++) {
      $this->createTerm('geo', ['name' => $this->faker->country()]);
    }
  }

  /**
   * Creates some languages.
   */
  private function createLanguages() {
    $languages = [
      'French',
      'English',
      'Dutch',
      'German',
    ];

    foreach ($languages as $language) {
      $this->createTerm('languages', ['name' => $language]);
    }
  }

  /**
   * Creates 'event_type' terms.
   */
  private function createEventTypes() {
    $terms = [
      'Event',
      'Learning / Training',
      'Meeting',
      'Academy',
      'Hackaton',
      'Investor Days',
    ];
    foreach ($terms as $term) {
      $this->createTerm('event_type', ['name' => $term]);
    }
  }

}
