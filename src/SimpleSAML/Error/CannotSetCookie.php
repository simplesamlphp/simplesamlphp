<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

/**
 * Exception to indicate that we cannot set a cookie.
 *
 * @package SimpleSAMLphp
 */

class CannotSetCookie extends Exception
{
    /**
     * The exception was thrown for unknown reasons.
     */
    public const int UNKNOWN = 0;

    /**
     * The exception was due to the HTTP headers being already sent, and therefore we cannot send additional headers to
     * set the cookie.
     */
    public const int HEADERS_SENT = 1;

    /**
     * The exception was due to trying to set a secure cookie over an insecure channel.
     */
    public const int SECURE_COOKIE = 2;
}
