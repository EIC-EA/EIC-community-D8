<?php

namespace Drupal\eic_default_content\Generator;

use Drupal\block_content\Entity\BlockContent;
use Drupal\fragments\Entity\Fragment;
use Drupal\fragments\Entity\FragmentType;

/**
 * Class BlockContentGenerator
 *
 * @package Drupal\eic_default_content\Generator
 */
class BlockContentGenerator extends CoreGenerator {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createLatestNewsBlock();
    $this->createHomeBanner();
    $this->createFollowUsBlock();
    $this->createNewsletterBlock();
    $this->createFiguresBlock();
    $this->createSiteInfoBlock();
  }

  /**
   * {@inheritdoc}
   */
  public function unLoad() {
    $this->unloadEntities('block_content');
  }

  /**
   * Creates the home's 'facts & figures' block.
   */
  private function createFiguresBlock() {
    $values = [
      'status' => TRUE,
      'type' => 'facts_figures',
      // For BC reasons we keep the same UUIDs since they are referenced in configs.
      'uuid' => 'e1aefa16-ad60-4d15-ac07-4a9a2d8b6044',
      'info' => 'Homepage - Facts & Figures block',
      'field_facts_figures' => $this->getFigureFragments(),
    ];

    $block = BlockContent::create($values);
    $block->save();
  }

  /**
   * Creates the home's 'stay up to date' block.
   */
  private function createNewsletterBlock() {
    $values = [
      'status' => TRUE,
      'type' => 'cta_tiles',
      // For BC reasons we keep the same UUIDs since they are referenced in configs.
      'uuid' => '880aff79-c4fa-416e-9703-0a3119bff5d0',
      'info' => 'Homepage - Stay up to date block',
      'field_title' => 'Stay up to date',
      'field_cta_tiles' => [
        $this->createParagraph([
          'type' => 'cta_tile',
          'field_body' => $this->getFormattedText('full_html'),
          'field_title' => 'Sign up to the EIC Newsletter',
          'field_cta_link' => $this->getLink('internal:/', 'Keep me up to date', 'call'),
        ]),
        $this->createParagraph([
          'type' => 'cta_tile',
          'field_body' => $this->getFormattedText('full_html'),
          'field_title' => 'Executive Agency for SMEs',
          'field_cta_link' => $this->getLink('https://ec.europa.eu/easme', 'Visit EASME',
            'primary'),
        ]),
      ],
    ];

    $block = BlockContent::create($values);
    $block->save();
  }

  /**
   * Creates the site info block for the footer.
   */
  private function createSiteInfoBlock() {
    $values = [
      'status' => TRUE,
      'type' => 'basic',
      // For BC reasons we keep the same UUIDs since they are referenced in configs.
      'uuid' => '34a810c9-d608-4979-ac40-00657bce344b',
      'info' => 'Footer - Site info',
      'field_body' => $this->getFormattedText('full_html'),
      'field_title' => 'European Innovation Council Community',
    ];

    $block = BlockContent::create($values);
    $block->save();
  }

  /**
   * Creates the home's 'social_media' block.
   */
  private function createFollowUsBlock() {
    $values = [
      'status' => TRUE,
      'type' => 'social_media',
      // For BC reasons we keep the same UUIDs since they are referenced in configs.
      'uuid' => '4b678ea5-f56e-45af-8399-7adde0abab77',
      'info' => 'Homepage - Follow us block',
      'field_title' => 'Follow us',
      'field_social_media_links' => [
        $this->getLink('https://twitter.com/eu_eic', NULL, 'twitter'),
        $this->getLink('https://www.facebook.com', NULL, 'facebook'),
        $this->getLink('https://www.linkedin.com', NULL, 'linkedin'),
      ],
    ];

    $block = BlockContent::create($values);
    $block->save();
  }

  /**
   * Creates home's 'page_banner' block content.
   */
  private function createHomeBanner() {
    $values = [
      'status' => TRUE,
      'type' => 'page_banner',
      // For BC reasons we keep the same UUIDs since they are referenced in configs.
      'uuid' => '5b0c5199-f4b0-4b8a-90fa-68d424e8315b',
      'field_title' => 'Your community to find partners and share knowledge.',
      'field_subtitle' => 'Welcome to the EIC Community',
      'info' => 'Homepage - Banner block',
      'field_body' => $this->getFormattedText('full_html'),
      'field_cta_links' => [
        $this->getLink('internal:/user/register', 'Register', 'cta'),
        $this->getLink('internal:/user/login', 'Login', 'default'),
      ],
      'field_media' => $this->getRandomEntities('media', ['bundle' => 'image']),
    ];

    $block = BlockContent::create($values);
    $block->save();
  }

  /**
   * Creates the 'latest_news_stories' block content.
   */
  private function createLatestNewsBlock() {
    $values = [
      'status' => TRUE,
      'type' => 'latest_news_stories',
      // For BC reasons we keep the same UUIDs since they are referenced in configs.
      'uuid' => '844a381c-6076-4279-834e-a75de967cf65',
      'info' => 'Homepage - Latest News & Stories',
      'field_articles' => $this->getRandomEntities('node', ['type' => 'news'], 3),
    ];

    $block = BlockContent::create($values);
    $block->save();
  }

  /**
   * Creates the facts & figures fragments for the home.
   *
   * @return array
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function getFigureFragments(): array {
    $types = [
      'user__user',
      'group__organisation',
      'group__group',
      'group__project',
      'node__challenge',
      'group__event',
    ];

    $fragments = [];
    foreach ($types as $type) {
      $fragment = Fragment::create([
        'type' => 'fact_figure',
        'field_body' => $this->getFormattedText('full_html'),
        'field_fact_figure_type' => $type,
      ]);

      $fragment->save();
      $fragments[] = $fragment;
    }

    return $fragments;
  }

}
