<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use Webmozart\Assert\Assert;

/**
 * Baseclass for auth source exceptions.
 *
 * @package SimpleSAMLphp_base
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
    public function __construct($authsource, $reason, $cause = null)
    {
        Assert::string($authsource);
        Assert::string($reason);

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
    public function getAuthSource()
    {
        return $this->authsource;
    }


    /**
     * Retrieve the reason why the request was invalid.
     *
     * @return string  The reason why the request was invalid.
     */
    public function getReason()
    {
        return $this->reason;
    }
}
