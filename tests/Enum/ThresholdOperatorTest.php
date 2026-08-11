<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\ThresholdOperator;
use PHPUnit\Framework\TestCase;

final class ThresholdOperatorTest extends TestCase
{
    public function testGreaterThan(): void
    {
        self::assertTrue(ThresholdOperator::GreaterThan->compare(29.0, 28.0));
        self::assertFalse(ThresholdOperator::GreaterThan->compare(28.0, 28.0));
        self::assertFalse(ThresholdOperator::GreaterThan->compare(27.0, 28.0));
    }

    public function testGreaterThanOrEqual(): void
    {
        self::assertTrue(ThresholdOperator::GreaterThanOrEqual->compare(28.0, 28.0));
        self::assertFalse(ThresholdOperator::GreaterThanOrEqual->compare(27.9, 28.0));
    }

    public function testLessThan(): void
    {
        self::assertTrue(ThresholdOperator::LessThan->compare(1.0, 2.0));
        self::assertFalse(ThresholdOperator::LessThan->compare(2.0, 2.0));
    }

    public function testLessThanOrEqual(): void
    {
        self::assertTrue(ThresholdOperator::LessThanOrEqual->compare(2.0, 2.0));
        self::assertFalse(ThresholdOperator::LessThanOrEqual->compare(2.1, 2.0));
    }
}
