<?php

/**
 * Show all error messages, with backtrace information.
 *
 * In case the error level could not be fetched from the database, as for
 * example the database connection failed, we rely only on this value.
 */
$settings['config_sync_directory'] = $app_root . '/../config/sync';
$settings['file_private_path'] = $app_root . '/../private_files';
$settings['hash_salt'] = getenv('DRUPAL_HASH_SALT');
$config['system.logging']['error_level'] = 'hide';
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

// Configure the default PhpStorage and Twig template cache directories.
if (!isset($settings['php_storage']['default'])) {
  $settings['php_storage']['default']['directory'] = $settings['file_private_path'];
}

if (!isset($settings['php_storage']['twig'])) {
  $settings['php_storage']['twig']['directory'] = $settings['file_private_path'];
}

$databases['default']['default'] = [
  'database' => getenv('DRUPAL_DATABASE_NAME'),
  'username' => getenv('DRUPAL_DATABASE_USERNAME'),
  'password' => getenv('DRUPAL_DATABASE_PASSWORD'),
  'host' => getenv('DRUPAL_DATABASE_HOST'),
  'port' => getenv('DRUPAL_DATABASE_PORT'),
  'driver' => 'mysql',
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
];

// Configuration for Redis
if (!\Drupal\Core\Installer\InstallerKernel::installationAttempted() && extension_loaded('redis')) {
  // Set Redis as the default backend for any cache bin not otherwise specified.
  $settings['cache']['default'] = 'cache.backend.redis';
  $settings['redis.connection']['host'] = getenv('REDIS_HOST');
  $settings['redis.connection']['port'] = getenv('REDIS_PORT');

  // Apply changes to the container configuration to make better use of Redis.
  // This includes using Redis for the lock and flood control systems, as well
  // as the cache tag checksum. Alternatively, copy the contents of that file
  // to your project-specific services.yml file, modify as appropriate, and
  // remove this line.
  $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';

  // Allow the services to work before the Redis module itself is enabled.
  $settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';

  // Manually add the classloader path, this is required for the container cache bin definition below
  // and allows to use it without the redis module being enabled.
  $class_loader->addPsr4('Drupal\\redis\\', 'modules/contrib/redis/src');

  // Use redis for container cache.
  // The container cache is used to load the container definition itself, and
  // thus any configuration stored in the container itself is not available
  // yet. These lines force the container cache to use Redis rather than the
  // default SQL cache.
  $settings['bootstrap_container_definition'] = [
    'parameters' => [],
    'services' => [
      'redis.factory' => [
        'class' => 'Drupal\redis\ClientFactory',
      ],
      'cache.backend.redis' => [
        'class' => 'Drupal\redis\Cache\CacheBackendFactory',
        'arguments' => [
          '@redis.factory',
          '@cache_tags_provider.container',
          '@serialization.phpserialize',
        ],
      ],
      'cache.container' => [
        'class' => '\Drupal\redis\Cache\PhpRedis',
        'factory' => ['@cache.backend.redis', 'get'],
        'arguments' => ['container'],
      ],
      'cache_tags_provider.container' => [
        'class' => 'Drupal\redis\Cache\RedisCacheTagsChecksum',
        'arguments' => ['@redis.factory'],
      ],
      'serialization.phpserialize' => [
        'class' => 'Drupal\Component\Serialization\PhpSerialize',
      ],
    ],
  ];
}

$settings['s3fs.use_s3_for_private'] = TRUE;
$settings['s3fs.use_s3_for_public'] = TRUE;

/**
 * EU Login settings.
 */
$config['cas.settings']['server.hostname'] = getenv('EULOGIN_URL');
// Uncomment this line to force EU Login known user accounts to login through EU
// Login.
//$config['cas.settings']['user_accounts.prevent_normal_login'] = TRUE;
// Allow self-registered users to login.
$config['oe_authentication.settings']['assurance_level'] = 'LOW';

$settings['s3fs.use_s3_for_private'] = TRUE;
$settings['s3fs.use_s3_for_public'] = TRUE;

/**
 * SMED API connection information.
 */
$settings['smed_api_taxonomy_username'] = getenv('SMED_API_USER');
$settings['smed_api_taxonomy_password'] = getenv('SMED_API_PASSWORD');
$settings['smed_api_taxonomy_endpoint'] = getenv('SMED_API_ENDPOINT');

/**
 * Webservices settings (REST endpoints).
 */
$config['eic_webservices.settings']['api_key'] = getenv('DRUPAL_WS_API_KEY');
$config['eic_webservices.settings']['smed_url'] = getenv('DRUPAL_SMED_URL');

// Interval time for the notification reminder to SA/SCM listing all groups pending for approval.
$settings['cron_interval_pending_approval_time'] = 86400;
$settings['cron_interval_group_invite_time'] = 86400;

$settings['eic_vod']['cloudfront_url'] = getenv('CLOUDFRONT_URL');
$settings['eic_vod']['cloudfront_api_key'] = getenv('CLOUDFRONT_API_KEY');
