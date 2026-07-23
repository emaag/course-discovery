<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Query;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;

final class CourseQueryResult
{
    /** @param list<Course> $courses */
    public function __construct(
        private readonly array $courses,
        private readonly int $total,
        private readonly int $totalPages,
        private readonly int $page,
        private readonly int $perPage,
    ) {
    }

    /** @return list<Course> */
    public function courses(): array
    {
        return $this->courses;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function totalPages(): int
    {
        return $this->totalPages;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }
}
