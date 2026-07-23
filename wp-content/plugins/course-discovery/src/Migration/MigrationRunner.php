<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Migration;

/** Runs each Migration at most once, tracked via a WP option keyed by version. */
final class MigrationRunner
{
    private const OPTION_KEY = 'course_discovery_applied_migrations';

    /** @param list<Migration> $migrations */
    public function run(array $migrations): void
    {
        $applied = get_option(self::OPTION_KEY, []);

        if (! is_array($applied)) {
            $applied = [];
        }

        foreach ($migrations as $migration) {
            if (in_array($migration->version(), $applied, true)) {
                continue;
            }

            $migration->run();
            $applied[] = $migration->version();
        }

        update_option(self::OPTION_KEY, $applied);
    }
}
