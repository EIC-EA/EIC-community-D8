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

if ($solr_host = getenv('SOLR_HOST')) {
  $config['search_api.server.global']['backend_config']['connector_config']['host'] = $solr_host;
}

/**
 * Matomo settings.
 */
$config['matomo.settings']['site_id'] = getenv('MATOMO_SITE_ID');
$config['matomo.settings']['url_http'] = 'http://' . getenv('MATOMO_URL') . '/';
$config['matomo.settings']['url_https'] = 'https://' . getenv('MATOMO_URL') . '/';

/**
 * EU Login settings.
 */
$config['cas.settings']['server']['hostname'] = getenv('EULOGIN_URL');

// Uncomment this line to force EU Login known user accounts to login through EU
// Login.
//$config['cas.settings']['user_accounts.prevent_normal_login'] = TRUE;
// Allow self-registered users to login.
$config['oe_authentication.settings']['assurance_level'] = 'LOW';

if ($bucket = getenv('AWS_S3_BUCKET')) {
  $config['s3fs.settings']['bucket'] = $bucket;
  $settings['s3fs.use_s3_for_private'] = TRUE;
  $settings['s3fs.use_s3_for_public'] = TRUE;
  $settings['s3fs.upload_as_private'] = TRUE;
}

if (getenv('TIKA_HOST')) {
  $config['search_api_attachments.admin_config']['extraction_method'] = 'tika_server_extractor';
  $config['search_api_attachments.admin_config']['tika_server_extractor_configuration'] = [
    'scheme' => getenv('TIKA_SCHEME'),
    'host' => getenv('TIKA_HOST'),
    'port' => getenv('TIKA_PORT'),
  ];
}

/**
 * SMTP settings.
 */
if (!empty(getenv('SMTP_SERVER'))) {
  $config['smtp.settings']['smtp_host'] = getenv('SMTP_SERVER');
  $config['smtp.settings']['smtp_hostbackup'] = '';
  $config['smtp.settings']['smtp_port'] = getenv('SMTP_PORT');
  $config['smtp.settings']['smtp_protocol'] = getenv('SMTP_PROTOCOL');
  $config['smtp.settings']['smtp_client_hostname'] = '';
}
if (!empty(getenv('SMTP_PASSWORD'))) {
  $config['smtp.settings']['smtp_username'] = getenv('SMTP_USERNAME');
  $config['smtp.settings']['smtp_password'] = getenv('SMTP_PASSWORD');
}

if ($from_mail = getenv('NOREPLY_MAIL')) {
  $config['smtp.settings']['smtp_from'] = $from_mail;
  $config['mimemail.settings']['mail'] = $from_mail;
  $config['smtp.settings']['smtp_fromname'] = getenv('SMTP_FROM_NAME') ?: '';
  $config['mimemail.settings']['name'] = getenv('SMTP_FROM_NAME') ?: '';
}

/**
 * SMED User webservice.
 */
$config['eic_user_login.settings']['endpoint_url'] = getenv('SMED_USERCHECK_URL');
$config['eic_user_login.settings']['basic_auth_username'] = getenv('SMED_USERCHECK_USERNAME');
$config['eic_user_login.settings']['basic_auth_password'] = getenv('SMED_USERCHECK_PASSWORD');
$config['eic_user_login.settings']['api_key'] = getenv('SMED_USERCHECK_API_KEY');

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
$settings['cron_interval_late_reindex_entities'] = 3600;

$settings['eic_vod']['cloudfront_url'] = getenv('CLOUDFRONT_URL');
$settings['eic_vod']['cloudfront_api_key'] = getenv('CLOUDFRONT_API_KEY');

$settings['cron_interval_late_reindex_entities'] = getenv('CRON_INTERVAL_LATE_REINDEX_ENTITIES_QUEUE');

$databases['migrate']['default'] = array (
  'database' => 'communityd7',
  'username' => getenv('MIGRATION_DATABASE_USER'),
  'password' => getenv('MIGRATION_DATABASE_PASSWORD'),
  'prefix' => '',
  'host' => getenv('MIGRATION_DATABASE_HOST'),
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
  'collation' => 'utf8mb4_general_ci',
);

if (PHP_SAPI === 'cli') {
  ini_set('memory_limit', '4G');
}
