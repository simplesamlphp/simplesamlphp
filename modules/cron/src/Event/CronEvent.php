<?php

declare(strict_types=1);

namespace SimpleSAML\Module\cron\Event;

class CronEvent
{
    private array $results = [];

    public function __construct(
        private readonly string $tag,
    )
    {}

    public function getTag(): string
    {
        return $this->tag;
    }

    public function addResult(string $taskName, bool $success, string $message = ''): void
    {
        $this->results[$taskName] = [
            'success' => $success,
            'message' => $message,
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function hasFailures(): bool
    {
        foreach ($this->results as $result) {
            if (!$result['success']) {
                return true;
            }
        }
        return false;
    }
}