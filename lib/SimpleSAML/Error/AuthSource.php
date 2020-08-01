<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use SimpleSAML\Assert\Assert;

/**
 * Baseclass for auth source exceptions.
 *
 * @package SimpleSAMLphp
 *
 */

class AuthSource extends Error
{
    /**
     * Authsource module name
     * @var string
     */
    private $authsource;

    /**
     * Reason why this request was invalid.
     * @var string
     */
    private $reason;


    /**
     * Create a new AuthSource error.
     *
     * @param string $authsource  Authsource module name from where this error was thrown.
     * @param string $reason  Description of the error.
     * @param \Exception|null $cause
     */
    public function __construct(string $authsource, string $reason, \Exception $cause = null)
    {
        $this->authsource = $authsource;
        $this->reason = $reason;
        parent::__construct(
            [
                'AUTHSOURCEERROR',
                '%AUTHSOURCE%' => htmlspecialchars(var_export($this->authsource, true)),
                '%REASON%' => htmlspecialchars(var_export($this->reason, true))
            ],
            $cause
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
