<?php

declare(strict_types=1);

namespace App\Service\Risk;

final class ExceedanceWindow
{
    public function __construct(
        public readonly \DateTimeImmutable $start,
        public readonly \DateTimeImmutable $end,
    ) {
    }

    public function days(): int
    {
        return $this->start->diff($this->end)->days + 1;
    }
}
