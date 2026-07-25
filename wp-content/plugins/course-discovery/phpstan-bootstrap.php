<?php

/**
 * Defines the plugin constants that course-discovery.php sets at runtime
 * (via plugin_dir_path()/plugin_dir_url()) so static analysis can resolve
 * COURSE_DISCOVERY_* usages in src/ without booting a real WordPress —
 * course-discovery.php itself can't be used as the bootstrap file, since it
 * exits immediately when ABSPATH isn't defined.
 */

declare(strict_types=1);

define('COURSE_DISCOVERY_VERSION', '0.1.0');
define('COURSE_DISCOVERY_FILE', __DIR__ . '/course-discovery.php');
define('COURSE_DISCOVERY_PATH', __DIR__ . '/');
define('COURSE_DISCOVERY_URL', 'https://example.test/wp-content/plugins/course-discovery/');
