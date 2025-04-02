<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use Throwable;

/**
 * Baseclass for auth source exceptions.
 *
 * @package SimpleSAMLphp
 *
 */

class AuthSource extends Error
{
    /**
     * Create a new AuthSource error.
     *
     * @param string $authsource  Authsource module name from where this error was thrown.
     * @param string $reason  Description of the error.
     * @param \Throwable|null $cause
     */
    public function __construct(
        private string $authsource,
        private string $reason,
        ?Throwable $cause = null,
    ) {
        $this->authsource = $authsource;
        $this->reason = $reason;
        parent::__construct(
            [
                ErrorCodes::AUTHSOURCEERROR,
                '%AUTHSOURCE%' => $this->authsource,
                '%REASON%' => $this->reason,
            ],
            $cause,
        );

        $this->message = "Error with authentication source '$authsource': $reason";
    }


    /**
     * Retrieve the authsource module name from where this error was thrown.
     *
     * @return string  Authsource module name.
     */
    public function getAuthSource(): string
    {
        return $this->authsource;
    }


    /**
     * Retrieve the reason why the request was invalid.
     *
     * @return string  The reason why the request was invalid.
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
