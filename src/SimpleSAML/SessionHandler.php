<?php

/**
 * This file is part of SimpleSAMLphp. See the file COPYING in the
 * root of the distribution for licence information.
 *
 * This file defines a base class for session handling.
 * Instantiation of session handler objects should be done through
 * the class method getSessionHandler().
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML;

use SimpleSAML\Store\StoreFactory;
use SimpleSAML\Utils;

abstract class SessionHandler
{
    /**
     * This static variable contains a reference to the current
     * instance of the session handler. This variable will be NULL if
     * we haven't instantiated a session handler yet.
     *
     * @var \SimpleSAML\SessionHandler|null
     */
    protected static ?SessionHandler $sessionHandler = null;


    /**
     * This function retrieves the current instance of the session handler.
     * The session handler will be instantiated if this is the first call
     * to this function.
     *
     * @return self The current session handler.
     *
     * @throws \Exception If we cannot instantiate the session handler.
     */
    public static function getSessionHandler(): self
    {
        if (self::$sessionHandler === null) {
            self::$sessionHandler = self::createSessionHandler();
        }

        return self::$sessionHandler;
    }


    /**
     * This constructor is included in case it is needed in the
     * future. Including it now allows us to write parent::__construct() in
     * the subclasses of this class.
     */
    protected function __construct()
    {
    }


    /**
     * Create a new session id.
     *
     * @return string The new session id.
     */
    abstract public function newSessionId(): string;


    /**
     * Retrieve the session ID saved in the session cookie, if there's one.
     *
     * @return string|null The session id saved in the cookie or null if no session cookie was set.
     */
    abstract public function getCookieSessionId(): ?string;


    /**
     * Retrieve the session cookie name.
     *
     * @return string The session cookie name.
     */
    abstract public function getSessionCookieName(): string;


    /**
     * Save the session.
     *
     * @param \SimpleSAML\Session $session The session object we should save.
     */
    abstract public function saveSession(Session $session): void;


    /**
     * Load the session.
     *
     * @param string|null $sessionId The ID of the session we should load, or null to use the default.
     *
     * @return \SimpleSAML\Session|null The session object, or null if it doesn't exist.
     */
    abstract public function loadSession(?string $sessionId): ?Session;


    /**
     * Check whether the session cookie is set.
     *
     * This function will only return false if is is certain that the cookie isn't set.
     *
     * @return bool True if it was set, false if not.
     */
    abstract public function hasSessionCookie(): bool;


    /**
     * Set a session cookie.
     *
     * @param string $sessionName The name of the session.
     * @param string|null $sessionID The session ID to use. Set to null to delete the cookie.
     * @param array|null $cookieParams Additional parameters to use for the session cookie.
     *
     * @throws \SimpleSAML\Error\CannotSetCookie If we can't set the cookie.
     */
    abstract public function setCookie(string $sessionName, ?string $sessionID, ?array $cookieParams = null): void;


    /**
     * Initialize the session handler.
     *
     * This function creates an instance of the session handler which is
     * selected in the 'store.type' configuration directive. If no
     * session handler is selected, then we will fall back to the default
     * PHP session handler.
     *
     * @return self The created session handler.
     *
     * @throws \Exception If we cannot instantiate the session handler.
     */
    private static function createSessionHandler(): self
    {
        $config = Configuration::getInstance();
        $storeType = $config->getOptionalString('store.type', 'phpsession');

        $store = StoreFactory::getInstance($storeType);
        if ($store === false) {
            return new SessionHandlerPHP();
        } else {
            return new SessionHandlerStore($store);
        }
    }


    /**
     * Get the cookie parameters that should be used for session cookies.
     *
     * @return array An array with the cookie parameters.
     * @link http://www.php.net/manual/en/function.session-get-cookie-params.php
     */
    public function getCookieParams(): array
    {
        $config = Configuration::getInstance();
        $httpUtils = new Utils\HTTP();

        return [
            'lifetime' => $config->getOptionalInteger('session.cookie.lifetime', 0),
            'path'     => $config->getOptionalString('session.cookie.path', '/'),
            'domain'   => $config->getOptionalString('session.cookie.domain', null),
            'secure'   => $config->getOptionalBoolean('session.cookie.secure', $httpUtils->isHTTPS()),
            'samesite' => $config->getOptionalString('session.cookie.samesite', null),
            'httponly' => true,
        ];
    }
}
