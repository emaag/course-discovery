<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Migration;

/** One implementation per schema change, run once and tracked by MigrationRunner. */
interface Migration
{
    /** A stable, sortable identifier — never reuse or reorder once shipped. */
    public function version(): string;

    public function run(): void;
}
