<?php

namespace SimpleSAML\Error;

/**
 * Exception indicating user aborting the authentication process.
 *
 * @package SimpleSAMLphp
 */

class UserAborted extends Error
{
    /**
     * Create the error
     *
     * @param \Exception|null $cause  The exception that caused this error.
     */
    public function __construct(\Exception $cause = null)
    {
        parent::__construct('USERABORTED', $cause);
    }
}
