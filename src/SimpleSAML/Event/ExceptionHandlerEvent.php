<?php

declare(strict_types=1);

namespace SimpleSAML\Event;

class ExceptionHandlerEvent
{
    private \Throwable $exception;

    public function __construct(\Throwable $exception)
    {
        $this->exception = $exception;
    }

    public function getException(): \Throwable
    {
        return $this->exception;
    }
}