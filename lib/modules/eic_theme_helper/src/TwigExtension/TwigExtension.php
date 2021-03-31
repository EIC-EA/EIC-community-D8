<?php

declare(strict_types = 1);

namespace Drupal\eic_theme_helper\TwigExtension;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Template\Attribute;
use Drupal\eic_theme_helper\EuropeanUnionLanguages;
use Drupal\smart_trim\Truncate\TruncateHTML;
use Drupal\Core\Template\TwigExtension as CoreTwigExtension;

/**
 * Collection of extra Twig extensions as filters and functions.
 *
 * We don't enforce any strict type checking on filters' arguments as they are
 * coming straight from Twig templates.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new TwigExtension object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(LanguageManagerInterface $languageManager, RendererInterface $renderer) {
    $this->languageManager = $languageManager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters(): array {
    return [
      new \Twig_SimpleFilter('format_size', 'format_size'),
      new \Twig_SimpleFilter('to_language', [$this, 'toLanguageName']),
      new \Twig_SimpleFilter(
        'to_native_language',
        [$this, 'toNativeLanguageName']
      ),
      new \Twig_SimpleFilter(
        'to_internal_language_id',
        [$this, 'toInternalLanguageId']
      ),
      new \Twig_SimpleFilter('to_file_icon', [$this, 'toFileIcon']),
      new \Twig_SimpleFilter('to_date_status', [$this, 'toDateStatus']),
      new \Twig_SimpleFilter(
        'to_ecl_attributes',
        [$this, 'toEclAttributes']
      ),
      new \Twig_SimpleFilter(
        'smart_trim',
        [$this, 'smartTrim'],
        ['needs_environment' => TRUE]
      ),
      new \Twig_SimpleFilter(
        'is_external_url',
        [UrlHelper::class, 'isExternal']
      ),
      new \Twig_SimpleFilter('filter_empty', [$this, 'filterEmpty']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new \Twig_SimpleFunction('to_ecl_icon', [$this, 'toEclIcon'], ['needs_context' => TRUE]),
      new \Twig_SimpleFunction('get_link_icon', [$this, 'getLinkIcon'], ['needs_context' => TRUE]),
      new \Twig_SimpleFunction('ecl_footer_links', [$this, 'eclFooterLinks'], ['needs_context' => TRUE]),
    ];
  }

  /**
   * Get a translated language name given its code.
   *
   * @param mixed $language_code
   *   Two letters language code.
   *
   * @return string
   *   Language name.
   */
  public function toLanguageName($language_code): string {
    return (string) $this->languageManager->getLanguageName($language_code);
  }

  /**
   * Get a native language name given its code.
   *
   * @param string $language_code
   *   The language code as defined by the W3C language tags document.
   *
   * @return string
   *   The native language name.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the passed in language code does not exist.
   */
  public function toNativeLanguageName($language_code): string {
    $languages = $this->languageManager->getNativeLanguages();
    if (!empty($languages[$language_code])) {
      return $languages[$language_code]->getName();
    }
    // The fallback implemented in case we don't have enabled language.
    $predefined = EuropeanUnionLanguages::getLanguageList() + LanguageManager::getStandardLanguageList();
    if (!empty($predefined[$language_code][1])) {
      return $predefined[$language_code][1];
    }

    throw new \InvalidArgumentException('The language code ' . $language_code . ' does not exist.');
  }

  /**
   * Get an internal language ID given its code.
   *
   * @param string $language_code
   *   The language code as defined by the W3C language tags document.
   *
   * @return string
   *   The internal language ID, or the given language code if none found.
   */
  public function toInternalLanguageId($language_code): string {
    if (EuropeanUnionLanguages::hasLanguage($language_code)) {
      return EuropeanUnionLanguages::getInternalLanguageCode($language_code);
    }

    return $language_code;
  }

  /**
   * Returns a list of language data.
   *
   * This is the data that is expected to be returned by the overridden language
   * manager as supplied by the OpenEuropa Multilingual module.
   *
   * @return array
   *   An array with language codes as keys, and English and native language
   *   names as values.
   *
   * @deprecated use EuropeanUnionLanguages::getLanguageList() instead.
   */
  public static function getEuropeanUnionLanguageList(): array {
    return EuropeanUnionLanguages::getLanguageList();
  }

  /**
   * Get date variant class given its status.
   *
   * @param string $status
   *   File extension.
   *
   * @return string
   *   File icon class name.
   */
  public static function toDateStatus(string $status): string {
    $variant_mapping = [
      'default' => 'default',
      'ongoing' => 'ongoing',
      'cancelled' => 'canceled',
      'past' => 'past',
    ];

    return array_key_exists($status, $variant_mapping) ? $variant_mapping[$status] : $status;
  }

  /**
   * Get file icon class given its extension.
   *
   * @param string $extension
   *   File extension.
   *
   * @return string
   *   File icon class name.
   */
  public function toFileIcon(string $extension): string {
    $extension = strtolower($extension);
    $extension_mapping = [
      'image' => [
        'jpg',
        'jpeg',
        'gif',
        'png',
        'webp',
      ],
      'presentation' => [
        'ppt',
        'pptx',
        'pps',
        'ppsx',
        'odp',
      ],
      'spreadsheet' => [
        'xls',
        'xlsx',
        'ods',
      ],
      'video' => [
        'mp4',
        'mov',
        'mpeg',
        'avi',
        'm4v',
        'webm',
      ],
    ];

    foreach ($extension_mapping as $file_type => $extensions) {
      if (in_array($extension, $extensions)) {
        return $file_type;
      }
    }

    return 'file';
  }

  /**
   * Convert Drupal attribute arrays to ECL Twig compatible ones.
   *
   * ECL Twig expects an array of string attributes, keyed by a name/value pair.
   * We use Drupal's Attribute class to make sure that we always get a printable
   * string, regardless of what we get as input from accessor preprocesses.
   *
   * @param mixed $attributes
   *   Drupal attributes, what we initialize the Drupal's Attribute class with.
   *
   * @return array
   *   An ECL Twig compatible attributes list.
   */
  public function toEclAttributes($attributes): array {
    // Only deal with iterable data.
    if (!is_iterable($attributes)) {
      return [];
    }

    $ecl_attributes = [];
    $attributes = new Attribute($attributes);
    foreach ($attributes as $key => $value) {
      $ecl_attributes[] = [
        'name' => $key,
        'value' => (string) $value,
      ];
    }

    return $ecl_attributes;
  }

  /**
   * Convert icon names to the ECL supported names and apply rotation if needed.
   *
   * @param array $context
   *   The twig context.
   * @param string|null $icon
   *   The icon to be converted.
   * @param string $size
   *   The icon size.
   *
   * @return array
   *   Icon array for ECL components containing icon name, path and rotation.
   */
  public function toEclIcon(array $context, $icon, string $size = ''): array {
    $path = $context['ecl_icon_path'];

    // ECL supported icons naming and rotation.
    $icons = [
      'facebook' => [
        'name' => 'branded--facebook',
      ],
      'instagram' => [
        'name' => 'branded--instagram',
      ],
      'linkedin' => [
        'name' => 'branded--linkedin',
      ],
      'pinterest' => [
        'name' => 'branded--pinterest',
      ],
      'rss' => [
        'name' => 'branded--rss',
      ],
      'skype' => [
        'name' => 'branded--skype',
      ],
      'twitter' => [
        'name' => 'branded--twitter',
      ],
      'youtube' => [
        'name' => 'branded--youtube',
      ],
      'audio' => [
        'name' => 'general--audio',
      ],
      'book' => [
        'name' => 'general--book',
      ],
      'brochure' => [
        'name' => 'general--brochure',
      ],
      'budget' => [
        'name' => 'general--budget',
      ],
      'calendar' => [
        'name' => 'general--calendar',
      ],
      'copy' => [
        'name' => 'general--copy',
      ],
      'data' => [
        'name' => 'general--data',
      ],
      'digital' => [
        'name' => 'general--digital',
      ],
      'edit' => [
        'name' => 'general--edit',
      ],
      'energy' => [
        'name' => 'general--energy',
      ],
      'euro' => [
        'name' => 'general--euro',
      ],
      'faq' => [
        'name' => 'general--faq',
      ],
      'feedback' => [
        'name' => 'general--feedback',
      ],
      'file' => [
        'name' => 'general--file',
      ],
      'gear' => [
        'name' => 'general--gear',
      ],
      'generic-lang' => [
        'name' => 'general--generic-lang',
      ],
      'global' => [
        'name' => 'general--global',
      ],
      'googleplus' => [
        'name' => 'general--digital',
      ],
      'growth' => [
        'name' => 'general--growth',
      ],
      'hamburger' => [
        'name' => 'general--hamburger',
      ],
      'image' => [
        'name' => 'general--image',
      ],
      'infographic' => [
        'name' => 'general--infographic',
      ],
      'language' => [
        'name' => 'general--language',
      ],
      'livestreaming' => [
        'name' => 'general--livestreaming',
      ],
      'location' => [
        'name' => 'general--location',
      ],
      'log-in' => [
        'name' => 'general--log-in',
      ],
      'logged-in' => [
        'name' => 'general--logged-in',
      ],
      'multiple-files' => [
        'name' => 'general--multiple-files',
      ],
      'organigram' => [
        'name' => 'general--organigram',
      ],
      'package' => [
        'name' => 'general--package',
      ],
      'presentation' => [
        'name' => 'general--presentation',
      ],
      'print' => [
        'name' => 'general--print',
      ],
      'regulation' => [
        'name' => 'general--regulation',
      ],
      'search' => [
        'name' => 'general--search',
      ],
      'share' => [
        'name' => 'general--share',
      ],
      'slides' => [
        'name' => 'general--presentation',
      ],
      'spinner' => [
        'name' => 'general--spinner',
      ],
      'spreadsheet' => [
        'name' => 'general--spreadsheet',
      ],
      'video' => [
        'name' => 'general--video',
      ],
      'camera' => [
        'name' => 'general--video',
      ],
      'error' => [
        'name' => 'notifications--error',
      ],
      'information' => [
        'name' => 'notifications--information',
      ],
      'info' => [
        'name' => 'notifications--information',
      ],
      'success' => [
        'name' => 'notifications--success',
      ],
      'warning' => [
        'name' => 'notifications--warning',
      ],
      'check' => [
        'name' => 'ui--check',
      ],
      'check-filled' => [
        'name' => 'ui--check-filled',
      ],
      'close' => [
        'name' => 'ui--close',
      ],
      'close-filled' => [
        'name' => 'ui--close-filled',
      ],
      'corner-arrow' => [
        'name' => 'ui--corner-arrow',
      ],
      'download' => [
        'name' => 'ui--download',
      ],
      'external' => [
        'name' => 'ui--external',
      ],
      'fullscreen' => [
        'name' => 'ui--fullscreen',
      ],
      'minus' => [
        'name' => 'ui--minus',
      ],
      'plus' => [
        'name' => 'ui--plus',
      ],
      'rounded-arrow' => [
        'name' => 'ui--rounded-arrow',
      ],
      'solid-arrow' => [
        'name' => 'ui--solid-arrow',
      ],
      'close-dark' => [
        'name' => 'ui--close-filled',
      ],
      'in' => [
        'name' => 'ui--download',
      ],
      'tag-close' => [
        'name' => 'ui--close',
      ],
      'up' => [
        'name' => 'ui--rounded-arrow',
      ],
      'arrow-down' => [
        'name' => 'ui--solid-arrow',
        'transform' => 'rotate-180',
      ],
      'arrow-up' => [
        'name' => 'ui--solid-arrow',
      ],
      'breadcrumb' => [
        'name' => 'ui--rounded-arrow',
        'transform' => 'rotate-90',
      ],
      'down' => [
        'name' => 'ui--rounded-arrow',
        'transform' => 'rotate-180',
      ],
      'left' => [
        'name' => 'ui--rounded-arrow',
        'transform' => 'rotate-270',
      ],
      'right' => [
        'name' => 'ui--rounded-arrow',
        'transform' => 'rotate-90',
      ],
    ];

    if (array_key_exists($icon, $icons)) {
      $icons[$icon]['path'] = $path;
      if ($size) {
        $icons[$icon]['size'] = $size;
      }

      return $icons[$icon];
    }

    if ($size) {
      return [
        'name' => 'general--digital',
        'path' => $path,
        'size' => $size,
      ];
    }

    return [
      'name' => 'general--digital',
      'path' => $path,
    ];
  }

  /**
   * Trim given input using smart_trim module heuristics.
   *
   * @param \Twig_Environment $env
   *   Current Twig environment.
   * @param mixed $input
   *   Input to be trimmed, it can be a string, an object or a render array.
   * @param int $limit
   *   Amount of text to allow.
   *
   * @return mixed
   *   The trimmed output.
   */
  public function smartTrim(\Twig_Environment $env, $input, $limit) {
    // Bubbles Twig template argument's cacheability & attachment metadata.
    $this->bubbleArgMetadata($input);
    $truncate = new TruncateHTML();

    // If input is a Markup object, trim it and return it as such.
    if ($input instanceof MarkupInterface) {
      return Markup::create($truncate->truncateChars((string) $input, $limit));
    }

    $output = $env->getExtension(CoreTwigExtension::class)->renderVar($input);

    // If rendered output is a Markup object, trim it and return it as such.
    if ($output instanceof MarkupInterface) {
      return Markup::create($truncate->truncateChars((string) $output, $limit));
    }

    // If rendered output is a scalar, trim it and return it as a string.
    if (is_scalar($output)) {
      return $truncate->truncateChars((string) $output, $limit);
    }

    // Just return input if we didn't fall in any of the cases above.
    return $input;
  }

  /**
   * Filter out empty, false and null values.
   *
   * @param array $entry
   *   Array to be filtered.
   *
   * @return array
   *   The filtered output.
   */
  public function filterEmpty(array $entry): array {
    return array_filter($entry, function ($var) {
      return $var !== '' && $var !== FALSE && $var !== NULL;
    });
  }

  /**
   * Bubbles Twig template argument's cacheability & attachment metadata.
   *
   * For example: a generated link or generated URL object is passed as a Twig
   * template argument, and its bubbleable metadata must be bubbled.
   *
   * @param mixed $arg
   *   A Twig template argument that is about to be printed.
   *
   * @see \Drupal\Core\Template\TwigExtension::bubbleArgMetadata()
   */
  protected function bubbleArgMetadata($arg) {
    // If it's a renderable, then it'll be up to the generated render array it
    // returns to contain the necessary cacheability & attachment metadata. If
    // it doesn't implement CacheableDependencyInterface or AttachmentsInterface
    // then there is nothing to do here.
    if ($arg instanceof RenderableInterface || !($arg instanceof CacheableDependencyInterface || $arg instanceof AttachmentsInterface)) {
      return;
    }

    $arg_bubbleable = [];
    BubbleableMetadata::createFromObject($arg)
      ->applyTo($arg_bubbleable);

    $this->renderer->render($arg_bubbleable);
  }

  /**
   * Gets icon based on the url that is used in ecl-twig/link component.
   *
   * @param array $context
   *   The twig context.
   * @param string $path
   *   The internal path or external URL.
   * @param string $size
   *   Size of the icon. Default is "s".
   *
   * @return array
   *   Icon settings to be used in the ecl-twig/link component.
   */
  public function getLinkIcon(array $context, string $path, string $size = 's'): array {
    $icon_path = $context['ecl_icon_path'];

    $icon = [
      'path' => $icon_path,
      'type' => 'ui',
      'size' => $size,
      'color' => 'primary',
    ];
    if (UrlHelper::isExternal($path)) {
      $icon['name'] = 'external';
    }
    else {
      $icon['name'] = 'corner-arrow';
      $icon['transform'] = 'rotate-90';
    }

    return $icon;
  }

  /**
   * Processes footer links to make them compatible with ECL formatting.
   *
   * @param array $context
   *   The twig context.
   * @param array $links
   *   Set of links to be processed.
   *
   * @return array
   *   Set of processed links.
   */
  public function eclFooterLinks(array $context, array $links): array {
    $ecl_links = [];

    foreach ($links as $link) {
      $ecl_link = [
        'link' => [
          'label' => $link['label'],
          'path' => $link['href'],
          'icon_position' => 'after',
        ],
      ];

      if (!empty($link['external']) && $link['external'] === TRUE) {
        $ecl_link += [
          'icon' => [
            'path' => $context['ecl_icon_path'],
            'type' => 'ui',
            'name' => 'external',
          ],
        ];
      }

      if (!empty($link['social_network'])) {
        $ecl_link['link']['icon_position'] = 'before';
        $ecl_link += [
          'icon' => [
            'path' => $context['ecl_icon_path'],
            'type' => 'branded',
            'name' => $link['social_network'],
          ],
        ];
      }

      $ecl_links[] = $ecl_link;
    }

    return $ecl_links;
  }

}
