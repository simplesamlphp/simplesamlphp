<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use Exception;

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
     * @param \Exception $cause The exception caught by the ExceptionHandler.
     */
    public function __construct(Exception $cause)
    {
        $this->includeTemplate = 'core:method_not_allowed.twig';
        parent::__construct(
            [
                ErrorCodes::METHODNOTALLOWED,
                '%MESSAGE%' => $cause->getMessage(),
            ],
            $cause,
            405,
        );
    }
}
