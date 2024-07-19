<?php

declare(strict_types=1);

namespace SimpleSAML\Module\testsauthsource\Auth\Source;

use SimpleSAML\Error;
use SimpleSAML\Error\ErrorCodes;

/**
 * A custom Error class
 */
class CustomError extends Error\Error
{
    private ?ErrorCodes $ec = null;

    public function __construct(
        string|array $errorCode,
        Throwable $cause = null,
        ?int $httpCode = null,
        ErrorCodes $errorCodes = null,
    ) {
        $this->ec = $errorCodes;
        if (!$this->ec) {
            $this->ec = new ErrorCodes();
        }
        parent::__construct($errorCode, $cause, $httpCode, $errorCodes);
    }
    public function getErrorCodes(): ErrorCodes
    {
        return $this->ec;
    }
}


