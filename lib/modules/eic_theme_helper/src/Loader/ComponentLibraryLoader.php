<?php

declare(strict_types = 1);

namespace Drupal\eic_theme_helper\Loader;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use OpenEuropa\Twig\Loader\EuropaComponentLibraryLoader;
use Drupal\Core\Messenger\MessengerTrait;

/**
 * Load ECL components Twig templates.
 */
class ComponentLibraryLoader extends EuropaComponentLibraryLoader {

  use MessengerTrait;

  /**
   * Theme path, if any.
   *
   * @var string
   */
  protected $themePath;

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct($namespaces, $root, $theme, $directories, ThemeHandlerInterface $theme_handler, LoggerChannelFactoryInterface $logger_factory) {
    // Make sure the theme exists before getting its path.
    // This is necessary when the "eic_theme_helper" module is enabled before
    // the theme is or the theme is disabled and the "eic_theme_helper" is not.
    $path = '';
    foreach ($namespaces as $namespace) {
      if ($namespace == 'ecl-twig') {
        $namespace = ['ecl', 'ecl-twig'];
        $prefix = 'ec-component';
        if ($theme_handler->themeExists($theme)) {
          $this->themePath = $theme_handler->getTheme($theme)->getPath();
          $path = $this->themePath . DIRECTORY_SEPARATOR . 'node_modules/@ecl-twig';
        }
      }
      else {
        $namespace = ['ecl'];
        $prefix = 'twig-component';
        if ($theme_handler->themeExists($theme)) {
          $this->themePath = $theme_handler->getTheme($theme)->getPath();
          $path = $this->themePath . DIRECTORY_SEPARATOR . 'node_modules/@ecl';
        }
      }

    }

    $this->logger = $logger_factory->get('ecl');
    parent::__construct($namespace, $path, $root, $prefix, 'ecl-');
  }

}
