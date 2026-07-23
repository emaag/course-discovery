<?php

/**
 * Plugin Name:       Course Discovery
 * Plugin URI:         https://github.com/oxfordinternational/course-discovery
 * Description:        Course discovery, search and filtering system for WordPress.
 * Version:             0.1.0
 * Requires at least:  6.7
 * Requires PHP:        8.2
 * Author:              Oxford International
 * License:             Proprietary
 * Text Domain:         course-discovery
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('COURSE_DISCOVERY_VERSION', '0.1.0');
define('COURSE_DISCOVERY_FILE', __FILE__);
define('COURSE_DISCOVERY_PATH', plugin_dir_path(__FILE__));
define('COURSE_DISCOVERY_URL', plugin_dir_url(__FILE__));

$course_discovery_autoloader = COURSE_DISCOVERY_PATH . 'vendor/autoload.php';

if (file_exists($course_discovery_autoloader)) {
    require_once $course_discovery_autoloader;
}

if (! class_exists(\OxfordInternational\CourseDiscovery\Plugin::class)) {
    return;
}

\OxfordInternational\CourseDiscovery\Plugin::instance()->boot();
