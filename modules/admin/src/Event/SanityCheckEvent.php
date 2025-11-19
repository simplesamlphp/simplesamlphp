<?php

declare(strict_types=1);

namespace SimpleSAML\Module\admin\Event;

class SanityCheckEvent
{
    private array $info = [];
    private array $errors = [];

    public function __construct() {
    }

    public function addInfo(string $message): void
    {
        $this->info[] = $message;
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function getInfo(): array
    {
        return $this->info;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}