<?php

namespace Drupal\eic_theme_helper\Template;


use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Template\TwigEnvironment as TwigEnvironmentBase;
use Drupal\eic_theme_helper\TokenParser\SpacelessTokenParser;
use Twig\Loader\LoaderInterface;

class TwigEnvironment extends TwigEnvironmentBase {

  public function __construct($root, CacheBackendInterface $cache, $twig_extension_hash, StateInterface $state, LoaderInterface $loader, array $options = []) {
    parent::__construct($root, $cache, $twig_extension_hash, $state, $loader, $options);
    $this->addTokenParser(new SpacelessTokenParser());
  }


}
