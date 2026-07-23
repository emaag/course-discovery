<?php

/**
 * Config for the WP_UnitTestCase integration suite. Only valid when run
 * from inside the wordpress container (`docker compose exec wordpress
 * php vendor/bin/phpunit -c phpunit-integration.xml.dist`) — ABSPATH and
 * DB_HOST both assume that environment, not the host machine.
 */

define('DB_NAME', getenv('WP_TESTS_DB_NAME') ?: 'wordpress_test');
define('DB_USER', getenv('WP_TESTS_DB_USER') ?: 'wordpress');
define('DB_PASSWORD', getenv('WP_TESTS_DB_PASSWORD') ?: 'wordpress');
define('DB_HOST', getenv('WP_TESTS_DB_HOST') ?: 'db');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

$table_prefix = 'wptests_';

define('WP_TESTS_DOMAIN', 'localhost');
define('WP_TESTS_EMAIL', 'admin@example.com');
define('WP_TESTS_TITLE', 'Course Discovery Test Suite');

define('WP_PHP_BINARY', 'php');
define('WPLANG', '');

define('ABSPATH', '/var/www/html/');
