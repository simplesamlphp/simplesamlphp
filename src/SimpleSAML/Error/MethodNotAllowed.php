<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

/**
 * Error for method not allowed.
 *
 * @package SimpleSAMLphp
 */

class MethodNotAllowed extends Error
{
    /**
     * Create the error
     *
     * @param string $message The message coming from Symfony, caught by the ExceptionHandler.
     */
    public function __construct(string $message)
    {
        $this->includeTemplate = 'core:method_not_allowed.twig';
        parent::__construct(
            [
                ErrorCodes::METHODNOTALLOWED,
                '%MESSAGE%' => $message,
            ],
            null,
            405,
        );
    }
}
