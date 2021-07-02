<?php

namespace Drupal\eic_seo;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\pathauto\AliasCleaner as AliasCleanerBase;
use Drupal\pathauto\AliasStorageHelperInterface;
use Drupal\pathauto\PathautoGeneratorInterface;

/**
 * Overrides the PathAuto alias cleaner.
 */
class AliasCleaner extends AliasCleanerBase {

  /**
   * Creates a new AliasCleaner.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\pathauto\AliasStorageHelperInterface $alias_storage_helper
   *   The alias storage helper.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasStorageHelperInterface $alias_storage_helper, LanguageManagerInterface $language_manager, CacheBackendInterface $cache_backend, TransliterationInterface $transliteration, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory, $alias_storage_helper, $language_manager, $cache_backend, $transliteration, $module_handler);
  }

  /**
   * Provides a configurable cleanString() method.
   *
   * Pathauto config is set globally. This method helps if you need to provide
   * custom configuration for a particular string.
   * We need this until this issue is resolved:
   * https://www.drupal.org/project/pathauto/issues/471644.
   *
   * @param string $string
   *   A string to clean.
   * @param array $options
   *   (optional) A keyed array of settings and flags to control the Pathauto
   *   clean string replacement process. Supported options are:
   *   - langcode: A language code to be used when translating strings.
   * @param array $customConfig
   *   (optional) A keyed array of settings to override against global config.
   *   Check the pathauto.settings config schema for the available options.
   *
   * @return string
   *   The cleaned string.
   */
  public function cleanString($string, array $options = [], array $customConfig = []) {

    $this->generateCachedVariables($customConfig);

    $output = parent::cleanString($string, $options);

    // Make sure we empty the cleanStringCache property for later normal use.
    $this->resetCaches();

    return $output;
  }

  /**
   * Generate and cache variables used in cleanString() method.
   *
   * This is a verbatim code taken from PathAuto AliasCleaner cleanString()
   * (based on version 8.x-1.8) method with just a way to override the global
   * config.
   *
   * @param array $customConfig
   *   (optional) A keyed array of settings to override against global config.
   *   Check the pathauto.settings config schema for the available options.
   */
  protected function generateCachedVariables(array $customConfig = []) {
    // Generate and cache variables used in this method.
    $config = $this->configFactory->get('pathauto.settings');
    $this->cleanStringCache = [
      'separator' => $customConfig['separator'] ?? $config->get('separator'),
      'strings' => [],
      'transliterate' => $customConfig['transliterate'] ?? $config->get('transliterate'),
      'punctuation' => [],
      'reduce_ascii' => (bool) $customConfig['reduce_ascii'] ?? $config->get('reduce_ascii'),
      'ignore_words_regex' => FALSE,
      'lowercase' => (bool) $customConfig['case'] ?? $config->get('case'),
      'maxlength' => min($customConfig['max_component_length'] ?? $config->get('max_component_length'), $this->aliasStorageHelper->getAliasSchemaMaxLength()),
    ];

    // Generate and cache the punctuation replacements for strtr().
    $punctuation = $this->getPunctuationCharacters();
    foreach ($punctuation as $name => $details) {
      $action = $customConfig['punctuation.' . $name] ?? $config->get('punctuation.' . $name);
      switch ($action) {
        case PathautoGeneratorInterface::PUNCTUATION_REMOVE:
          $this->cleanStringCache['punctuation'][$details['value']] = '';
          break;

        case PathautoGeneratorInterface::PUNCTUATION_REPLACE:
          $this->cleanStringCache['punctuation'][$details['value']] = $this->cleanStringCache['separator'];
          break;

        case PathautoGeneratorInterface::PUNCTUATION_DO_NOTHING:
          // Literally do nothing.
          break;
      }
    }

    // Generate and cache the ignored words regular expression.
    $ignore_words = $customConfig['ignore_words'] ?? $config->get('ignore_words');
    $ignore_words_regex = preg_replace(
      ['/^[,\s]+|[,\s]+$/', '/[,\s]+/'],
      ['', '\b|\b'],
      $ignore_words
    );
    if ($ignore_words_regex) {
      $this->cleanStringCache['ignore_words_regex'] = '\b' . $ignore_words_regex . '\b';
      if (function_exists('mb_eregi_replace')) {
        mb_regex_encoding('UTF-8');
        $this->cleanStringCache['ignore_words_callback'] = 'mb_eregi_replace';
      }
      else {
        $this->cleanStringCache['ignore_words_callback'] = 'preg_replace';
        $this->cleanStringCache['ignore_words_regex'] = '/' . $this->cleanStringCache['ignore_words_regex'] . '/i';
      }
    }
  }

}
