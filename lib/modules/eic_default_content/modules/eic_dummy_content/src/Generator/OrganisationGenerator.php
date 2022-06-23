<?php

namespace Drupal\eic_dummy_content\Generator;

use Drupal\Core\Url;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_default_content\Generator\CoreGenerator;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_moderation\Constants\EICContentModeration;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;
use Drupal\oec_group_features\GroupFeatureHelper;

/**
 * Class to generate organisations using fixtures.
 *
 * @package Drupal\eic_dummy_content\Generator
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

    /** @var \Drupal\oec_group_features\GroupFeatureHelper $group_feature_helper */
    $group_feature_helper = \Drupal::service('oec_group_features.helper');

    // Get all available features for this group type.
    $available_features = [];
    foreach ($group_feature_helper->getGroupTypeAvailableFeatures('organisation') as $plugin_id => $label) {
      $available_features[] = $plugin_id;
    }

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
        'features' => [],
      ];

      $group = Group::create($values);
      $group->save();

      // Save group features.
      $group->set(GroupFeatureHelper::FEATURES_FIELD_NAME, $available_features);
      $group->save();

      $this->createOrganisationEvents($group);
      $this->createOrganisationNews($group);
    }
  }

  /**
   * Creates a single group news (node) for the given organisation.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   */
  private function createOrganisationEvents(GroupInterface $group) {
    $content_type_id = $group
      ->getGroupType()
      ->getContentPlugin('group_node:event')
      ->getContentTypeConfigId();

    $node = Node::create([
      'field_body' => $this->getFormattedText('full_html'),
      'type' => 'event',
      'title' => 'Event in ' . $group->label(),
      'field_link' => 'https://myevent.site',
      'field_organised_by' => 'European Innovation Council',
      'field_vocab_event_type' => $this->getRandomEntities('taxonomy_term', ['vid' => 'event_type'], 1),
      'field_vocab_topics' => $this->getRandomEntities('taxonomy_term', ['vid' => 'topics'], 3),
      'field_vocab_geo' => $this->getRandomEntities('taxonomy_term', ['vid' => 'geo'], 2),
      'status' => TRUE,
      'uid' => 1,
      'moderation_state' => DefaultContentModerationStates::PUBLISHED_STATE,
    ]);

    $node->save();
    $group_content = GroupContent::create([
      'type' => $content_type_id,
      'gid' => $group->id(),
      'entity_id' => $node->id(),
    ]);
    $group_content->save();
  }

  /**
   * Creates a single group news (node) for the given organisation.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   */
  private function createOrganisationNews(GroupInterface $group) {
    $content_type_id = $group
      ->getGroupType()
      ->getContentPlugin('group_node:news')
      ->getContentTypeConfigId();

    $node = Node::create([
      'type' => 'news',
      'title' => 'News in ' . $group->label(),
      'field_body' => $this->getFormattedText('full_html'),
      'field_introduction' => $this->getFormattedText('full_html'),
      'field_header_visual' => $this->createMedia([
        'oe_media_image' => $this->getRandomImage(),
      ], 'image'),
      'field_image' => $this->createMedia([
        'oe_media_image' => $this->getRandomImage(),
      ], 'image'),
      'field_image_caption' => $this->faker->sentence(10),
      'field_vocab_topics' => $this->getRandomEntities('taxonomy_term', ['vid' => 'topics'], 1),
      'field_vocab_geo' => $this->getRandomEntities('taxonomy_term', ['vid' => 'geo'], 1),
      'moderation_state' => EICContentModeration::STATE_PUBLISHED,
      'uid' => 1,
    ]);

    $node->save();
    $group_content = GroupContent::create([
      'type' => $content_type_id,
      'gid' => $group->id(),
      'entity_id' => $node->id(),
    ]);
    $group_content->save();
  }

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    $this->unloadEntities('group_permission');
    $this->unloadEntities('group');
  }

}
