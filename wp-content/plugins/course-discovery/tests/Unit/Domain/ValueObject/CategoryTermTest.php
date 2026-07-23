<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Domain\ValueObject;

use OxfordInternational\CourseDiscovery\Domain\ValueObject\CategoryTerm;
use PHPUnit\Framework\TestCase;

final class CategoryTermTest extends TestCase
{
    public function test_a_term_with_no_parent_is_top_level(): void
    {
        $term = new CategoryTerm(1, 'Design', 'design');

        self::assertTrue($term->isTopLevel());
        self::assertNull($term->parentId());
    }

    public function test_a_term_with_a_parent_is_not_top_level(): void
    {
        $term = new CategoryTerm(2, 'Graphic Design', 'graphic-design', 1);

        self::assertFalse($term->isTopLevel());
        self::assertSame(1, $term->parentId());
    }

    public function test_equality_is_by_id(): void
    {
        $a = new CategoryTerm(1, 'Design', 'design');
        $b = new CategoryTerm(1, 'Design (renamed)', 'design-renamed');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(new CategoryTerm(2, 'Design', 'design')));
    }
}
