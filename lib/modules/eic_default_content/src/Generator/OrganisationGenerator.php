<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\Core\Url;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\group\Entity\Group;

/**
 * Class to generate organisations using fixtures.
 *
 * @package Drupal\eic_default_content\Generator
 */
class OrganisationGenerator extends CoreGenerator {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $organisations_sample = [
      'Prophets',
      'Blue4You',
      'Foreach',
    ];
    /** @var \Drupal\oec_group_features\GroupFeaturePluginManager $group_feature_manager */
    $group_feature_manager = \Drupal::service('plugin.manager.group_feature');

    foreach ($organisations_sample as $organisation_sample) {
      $values = [
        'label' => $organisation_sample,
        'type' => 'organisation',
        'status' => TRUE,
        'field_body' => $this->getFormattedText('full_html'),
        'moderation_state' => GroupsModerationHelper::GROUP_PUBLISHED_STATE,
        'field_organisation_employees' => random_int(1000, 2000),
        'field_date_establishement' => random_int(1900, 2021),
        'field_organisation_turnover' => random_int(2000, 20000),
        'field_vocab_services_products' => $this->getRandomEntities('taxonomy_term', ['vid' => 'services_and_products'], 1),
        'field_vocab_target_markets' => $this->getRandomEntities('taxonomy_term', ['vid' => 'target_markets'], 1),
        'field_vocab_topics' => $this->getRandomEntities('taxonomy_term', ['vid' => 'topics'], 1),
        'field_header_visual' => $this->createMedia([
          'oe_media_image' => $this->getRandomImage(),
        ], 'image'),
        'field_thumbnail' => $this->createMedia([
          'oe_media_image' => $this->getRandomImage(),
        ], 'image'),
        'field_organisation_type' => $this->getRandomEntities('taxonomy_term', ['vid' => 'organisation_types'], 3),
        'uid' => 1,
        'field_contact_label' => "$organisation_sample contact",
        'field_email' => strtolower($organisation_sample) . '@' . strtolower($organisation_sample) . '.be',
        'field_organisation_link' => $this->getLink(
          Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
          "Visit website"
        ),
        'field_address' => [
          [
            'langcode' => 'en',
            'country_code' => 'BE',
            'locality' => 'Brussels',
            'postal_code' => '1000',
            'address_line1' => 'Example address',
          ],
        ],
        'field_social_links' => [
          [
            'social' => 'facebook',
            'link' => 'example',
          ],
          [
            'social' => 'twitter',
            'link' => 'example',
          ],
          [
            'social' => 'linkedin',
            'link' => 'example',
          ],
        ],
        'field_locations' => [
          $this->createParagraph([
            'type' => 'organisation_location',
            'field_city' => 'Brussels',
            'field_country' => $this->getRandomEntities('taxonomy_term', ['vid' => 'geo'], 1)[0],
          ]),
        ],
        'field_offers' => [
          $this->createParagraph([
            'type' => 'announcement',
            'field_title' => "What we offering at $organisation_sample",
            'field_description' => $this->getFormattedText('full_html'),
            'field_cta_link' => $this->getLink(
              'https://ec.europa.eu/easme',
              "Contact us",
              'primary'
            ),
          ]),
        ],
        'field_needs' => [
          $this->createParagraph([
            'type' => 'announcement',
            'field_title' => "Looking for partners",
            'field_description' => $this->getFormattedText('full_html'),
            'field_cta_link' => $this->getLink(
              'https://ec.europa.eu/easme',
              "Contact us",
              'primary'
            ),
          ]),
        ],
        'field_team_members' => [
          $this->createParagraph([
            'type' => 'organisation_member',
            'field_job_title' => "CEO",
            'field_role' => 'Business analyst',
            'field_user_ref' => $this->getRandomEntities('user', [], 2)[1],
          ]),
        ],
      ];

      $group = Group::create($values);
      $group->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    $this->unloadEntities('group_permission');
    $this->unloadEntities('group');
  }

}
