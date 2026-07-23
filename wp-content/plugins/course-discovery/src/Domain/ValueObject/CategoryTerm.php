<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Domain\ValueObject;

/** A term from the hierarchical `course_category` taxonomy. */
final class CategoryTerm
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $slug,
        private readonly ?int $parentId = null,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function parentId(): ?int
    {
        return $this->parentId;
    }

    public function isTopLevel(): bool
    {
        return $this->parentId === null;
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }
}
