<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\taxonomy\Entity\Term;

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
    $this->createGlobalEventTypes();
    $this->createOrganisationTypes();
    $this->createContactCategories();
    $this->createTargetMarket();
    $this->createServiceProduct();
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
    $this->unloadEntities('taxonomy_term', ['vid' => 'global_event_type']);
    $this->unloadEntities('taxonomy_term', ['vid' => 'organisation_types']);
    $this->unloadEntities('taxonomy_term', ['vid' => 'contact_category']);
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
      'Hackathon',
      'Investor Days',
    ];
    foreach ($terms as $term) {
      $this->createTerm('event_type', ['name' => $term]);
    }
  }

  /**
   * Creates 'event_type' terms.
   */
  private function createGlobalEventTypes() {
    $terms = [
      'Event',
      'Learning / Training',
      'Meeting',
      'Academy',
      'Hackathon',
      'Investor Days',
    ];
    foreach ($terms as $term) {
      $this->createTerm('global_event_type', ['name' => $term, 'field_display_to_users' => TRUE]);
    }
  }

  /**
   * Creates 'organisation_types' terms.
   */
  private function createOrganisationTypes() {
    $types = [
      'EIC Beneficiaries',
      'EIC Summit',
      'EIC Seal of Excellence',
      'EIC Accelerator' => [
        'EIC SMEi',
        'EIC FTI',
      ],
      'EIC Pathfinder',
      'Other',
    ];

    foreach ($types as $key => $type) {
      if (is_array($type)) {
        $parent = $this->createTerm('organisation_types', ['name' => $key]);
        foreach ($type as $sub_type) {
          $this->createTerm('organisation_types', [
            'name' => $sub_type,
            'parent' => $parent->id(),
          ]);
        }
      }
      else {
        $this->createTerm('organisation_types', ['name' => $type]);
      }
    }
  }

  /**
   * Generate terms for contact categories.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createContactCategories() {
    $datas = [
      [
        'label' => '- Any -',
        'email' => 'any@eic.com',
      ],
      [
        'label' => 'Business',
        'email' => 'business@eic.com',
      ],
      [
        'label' => 'Organisation',
        'email' => 'organisation@eic.com',
      ],
      [
        'label' => 'Group',
        'email' => 'group@eic.com',
      ],
    ];

    foreach ($datas as $data) {
      $term = Term::create([
        'vid' => 'contact_category',
        'name' => $data['label'],
        'field_target_email' => $data['email'],
      ]);

      $term->save();
    }
  }

  /**
   * Creates 'target_markets' terms.
   */
  private function createTargetMarket() {
    $types = [
      'Economy',
      'Digital',
      'Business',
      'Entertainment',
    ];

    foreach ($types as $key => $type) {
      if (is_array($type)) {
        $parent = $this->createTerm('target_markets', ['name' => $key]);
        foreach ($type as $sub_type) {
          $this->createTerm('target_markets', [
            'name' => $sub_type,
            'parent' => $parent->id(),
          ]);
        }
      }
      else {
        $this->createTerm('target_markets', ['name' => $type]);
      }
    }
  }

  /**
   * Creates 'services_products' terms.
   */
  private function createServiceProduct() {
    $types = [
      'Product A' => [
        'Product A.1',
        'Product A.2',
      ],
      'Product B',
      'Product C',
      'Product D',
      'Product E',
      'Product F',
    ];

    foreach ($types as $key => $type) {
      if (is_array($type)) {
        $parent = $this->createTerm('services_and_products', ['name' => $key]);
        foreach ($type as $sub_type) {
          $this->createTerm('services_and_products', [
            'name' => $sub_type,
            'parent' => $parent->id(),
          ]);
        }
      }
      else {
        $this->createTerm('services_and_products', ['name' => $type]);
      }
    }
  }

}
