<?php

/**
 * Bootstrap for the WP_UnitTestCase integration suite — boots a real
 * WordPress (the same install this container runs, against a dedicated
 * `wordpress_test` database) and loads this plugin + ACF as if activated,
 * so tests exercise the real WP_Query/tax_query/ACF machinery that
 * tests/Unit deliberately avoids depending on.
 */

declare(strict_types=1);

define('WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config-integration.php');

require_once __DIR__ . '/../vendor/wp-phpunit/wp-phpunit/includes/functions.php';

tests_add_filter('muplugins_loaded', static function (): void {
    require ABSPATH . 'wp-content/plugins/advanced-custom-fields/acf.php';
    require dirname(__DIR__) . '/course-discovery.php';
});

require_once __DIR__ . '/../vendor/wp-phpunit/wp-phpunit/includes/bootstrap.php';
