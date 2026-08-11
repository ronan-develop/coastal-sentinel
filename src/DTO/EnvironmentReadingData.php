<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\EnvironmentVariable;

final class EnvironmentReadingData
{
    /**
     * @param array<string, mixed>|null $rawPayload
     */
    public function __construct(
        public readonly EnvironmentVariable $variable,
        public readonly float $value,
        public readonly string $unit,
        public readonly \DateTimeImmutable $measuredAt,
        public readonly ?int $horizon = null,
        public readonly ?array $rawPayload = null,
    ) {
    }
}
