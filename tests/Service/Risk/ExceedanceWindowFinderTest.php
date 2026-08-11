<?php

declare(strict_types=1);

namespace App\Tests\Service\Risk;

use App\Service\Risk\ExceedanceWindowFinder;
use PHPUnit\Framework\TestCase;

final class ExceedanceWindowFinderTest extends TestCase
{
    private function day(string $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date);
    }

    public function testReturnsNullForEmptySeries(): void
    {
        $finder = new ExceedanceWindowFinder();

        self::assertNull($finder->findLongestQualifyingWindow([], 3));
    }

    public function testReturnsNullWhenNothingExceeds(): void
    {
        $finder = new ExceedanceWindowFinder();
        $series = [
            ['date' => $this->day('2026-08-14'), 'exceeds' => false],
            ['date' => $this->day('2026-08-15'), 'exceeds' => false],
            ['date' => $this->day('2026-08-16'), 'exceeds' => false],
        ];

        self::assertNull($finder->findLongestQualifyingWindow($series, 3));
    }

    public function testReturnsWindowWhenExactlyMinConsecutiveDaysExceed(): void
    {
        $finder = new ExceedanceWindowFinder();
        $series = [
            ['date' => $this->day('2026-08-14'), 'exceeds' => true],
            ['date' => $this->day('2026-08-15'), 'exceeds' => true],
            ['date' => $this->day('2026-08-16'), 'exceeds' => true],
        ];

        $window = $finder->findLongestQualifyingWindow($series, 3);

        self::assertNotNull($window);
        self::assertSame('2026-08-14', $window->start->format('Y-m-d'));
        self::assertSame('2026-08-16', $window->end->format('Y-m-d'));
        self::assertSame(3, $window->days());
    }

    public function testReturnsNullWhenRunShorterThanMinExposure(): void
    {
        $finder = new ExceedanceWindowFinder();
        $series = [
            ['date' => $this->day('2026-08-14'), 'exceeds' => true],
            ['date' => $this->day('2026-08-15'), 'exceeds' => true],
            ['date' => $this->day('2026-08-16'), 'exceeds' => false],
        ];

        self::assertNull($finder->findLongestQualifyingWindow($series, 3));
    }

    public function testMissingDayBreaksTheConsecutiveRun(): void
    {
        $finder = new ExceedanceWindowFinder();
        // 14 et 15 exceed, 16 manquant (trou de données), 17 et 18 exceed
        // → deux séries de 2 jours, aucune n'atteint 3 jours consécutifs.
        $series = [
            ['date' => $this->day('2026-08-14'), 'exceeds' => true],
            ['date' => $this->day('2026-08-15'), 'exceeds' => true],
            ['date' => $this->day('2026-08-17'), 'exceeds' => true],
            ['date' => $this->day('2026-08-18'), 'exceeds' => true],
        ];

        self::assertNull($finder->findLongestQualifyingWindow($series, 3));
    }

    public function testReturnsTheLongestQualifyingRunWhenSeveralExist(): void
    {
        $finder = new ExceedanceWindowFinder();
        $series = [
            ['date' => $this->day('2026-08-10'), 'exceeds' => true],
            ['date' => $this->day('2026-08-11'), 'exceeds' => true],
            ['date' => $this->day('2026-08-12'), 'exceeds' => true],
            ['date' => $this->day('2026-08-13'), 'exceeds' => false],
            ['date' => $this->day('2026-08-14'), 'exceeds' => true],
            ['date' => $this->day('2026-08-15'), 'exceeds' => true],
            ['date' => $this->day('2026-08-16'), 'exceeds' => true],
            ['date' => $this->day('2026-08-17'), 'exceeds' => true],
        ];

        $window = $finder->findLongestQualifyingWindow($series, 3);

        self::assertNotNull($window);
        self::assertSame('2026-08-14', $window->start->format('Y-m-d'));
        self::assertSame('2026-08-17', $window->end->format('Y-m-d'));
        self::assertSame(4, $window->days());
    }
}
