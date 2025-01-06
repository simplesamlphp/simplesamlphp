<?php

/**
 * This file is part of SimpleSAMLphp. See the file COPYING in the root of the distribution for licence information.
 *
 * This file defines a session handler which uses the default php session handler for storage.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML;

use SimpleSAML\{Error, Utils};
use SimpleSAML\Assert\Assert;

use function array_key_exists;
use function bin2hex;
use function headers_sent;
use function ini_get;
use function openssl_random_pseudo_bytes;
use function session_create_id;
use function session_get_cookie_params;
use function session_id;
use function session_name;
use function session_regenerate_id;
use function session_save_path;
use function session_set_cookie_params;
use function session_start;
use function session_status;
use function session_write_close;
use function unserialize;

class SessionHandlerPHP extends SessionHandler
{
    /**
     * This variable contains the session cookie name.
     *
     * @var string
     */
    protected string $cookie_name;

    /**
     * An associative array containing the details of a session existing previously to creating or loading one with this
     * session handler. The keys of the array will be:
     *
     *   - id: the ID of the session, as returned by session_id().
     *   - name: the name of the session, as returned by session_name().
     *   - cookie_params: the parameters of the session cookie, as returned by session_get_cookie_params().
     *
     * @var array
     */
    private array $previous_session = [];


    /**
     * Initialize the PHP session handling. This constructor is protected because it should only be called from
     * \SimpleSAML\SessionHandler::createSessionHandler(...).
     */
    protected function __construct()
    {
        // call the parent constructor in case it should become necessary in the future
        parent::__construct();

        $config = Configuration::getInstance();
        $this->cookie_name = $config->getOptionalString(
            'session.phpsession.cookiename',
            ini_get('session.name') ?: 'PHPSESSID',
        );

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (session_name() === $this->cookie_name) {
                Logger::warning(
                    'There is already a PHP session with the same name as SimpleSAMLphp\'s session, or the ' .
                    "'session.phpsession.cookiename' configuration option is not set. Make sure to set " .
                    "SimpleSAMLphp's cookie name with a value not used by any other applications.",
                );
            }

            /*
             * We shouldn't have a session at this point, so it might be an application session. Save the details to
             * retrieve it later and commit.
             */
            $this->previous_session['cookie_params'] = session_get_cookie_params();
            $this->previous_session['id'] = session_id();
            $this->previous_session['name'] = session_name();
            session_write_close();
        }

        if (empty($this->cookie_name)) {
            $this->cookie_name = session_name();
        }

        $params = $this->getCookieParams();

        if (!headers_sent()) {
            session_name($this->cookie_name);

            /** @psalm-suppress InvalidArgument */
            session_set_cookie_params([
                'lifetime' => $params['lifetime'],
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
        }

        $savepath = $config->getOptionalString('session.phpsession.savepath', null);
        if (!empty($savepath)) {
            session_save_path($savepath);
        }
    }


    /**
     * Restore a previously-existing session.
     *
     * Use this method to restore a previous PHP session existing before SimpleSAMLphp initialized its own session.
     *
     * WARNING: do not use this method directly, unless you know what you are doing. Calling this method directly,
     * outside of \SimpleSAML\Session, could cause SimpleSAMLphp's session to be lost or mess the application's one. The
     * session must always be saved properly before calling this method. If you don't understand what this is about,
     * don't use this method.
     *
     */
    public function restorePrevious(): void
    {
        if (empty($this->previous_session)) {
            return; // nothing to do here
        }

        // close our own session
        session_write_close();

        session_name($this->previous_session['name']);
        session_set_cookie_params($this->previous_session['cookie_params']);
        session_id($this->previous_session['id']);
        $this->previous_session = [];
        @session_start();

        /*
         * At this point, we have restored a previously-existing session, so we can't continue to use our session here.
         * Therefore, we need to load our session again in case we need it. We remove this handler from the parent
         * class so that the handler is initialized again if we ever need to do something with the session.
         */
        parent::$sessionHandler = null;
    }


    /**
     * Create a new session id.
     *
     * @return string The new session id.
     */
    public function newSessionId(): string
    {
        if ($this->hasSessionCookie()) {
            session_regenerate_id(false);
            $sessionId = session_id();
        } else {
            // generate new (secure) session id
            $sid_length = (int) ini_get('session.sid_length');
            $sid_bits_per_char = (int) ini_get('session.sid_bits_per_character');

            if (($sid_length * $sid_bits_per_char) < 128) {
                Logger::warning("Unsafe defaults used for sessionId generation!");
            }

            $sessionId = session_create_id();
        }

        if (!$sessionId) {
            Logger::warning("Secure session ID generation failed, falling back to custom ID generation.");
            $sessionId = bin2hex(openssl_random_pseudo_bytes(16));
        }

        Session::createSession($sessionId);
        return $sessionId;
    }

    /**
     * Retrieve the session ID saved in the session cookie, if there's one.
     *
     * @return string|null The session id saved in the cookie or null if no session cookie was set.
     *
     * @throws \SimpleSAML\Error\Exception If the cookie is marked as secure but we are not using HTTPS.
     */
    public function getCookieSessionId(): ?string
    {
        if (!$this->hasSessionCookie()) {
            // there's no session cookie, can't return ID
            return null;
        }

        if (headers_sent()) {
            // latest versions of PHP don't allow loading a session when output sent, get the ID from the cookie
            return $_COOKIE[$this->cookie_name];
        }

        // do not rely on session_id() as it can return the ID of a previous session. Get it from the cookie instead.
        session_id($_COOKIE[$this->cookie_name]);

        $session_cookie_params = session_get_cookie_params();

        $httpUtils = new Utils\HTTP();
        if ($session_cookie_params['secure'] && !$httpUtils->isHTTPS()) {
            throw new Error\Exception('Session start with secure cookie not allowed on http.');
        }

        @session_start();
        return session_id();
    }


    /**
     * Retrieve the session cookie name.
     *
     * @return string The session cookie name.
     */
    public function getSessionCookieName(): string
    {
        return $this->cookie_name;
    }


    /**
     * Save the current session to the PHP session array.
     *
     * @param \SimpleSAML\Session $session The session object we should save.
     */
    public function saveSession(Session $session): void
    {
        $_SESSION['SimpleSAMLphp_SESSION'] = serialize($session);
    }


    /**
     * Load the session from the PHP session array.
     *
     * @param string|null $sessionId The ID of the session we should load, or null to use the default.
     *
     * @return \SimpleSAML\Session|null The session object, or null if it doesn't exist.
     *
     * @throws \SimpleSAML\Error\Exception If it wasn't possible to disable session cookies or we are trying to load a
     * PHP session with a specific identifier and it doesn't match with the current session identifier.
     */
    public function loadSession(?string $sessionId = null): ?Session
    {
        if ($sessionId !== session_id()) {
            throw new Error\Exception('Cannot load PHP session with a specific ID.');
        } elseif (session_id() === '') {
            $this->getCookieSessionId();
        }

        if (!isset($_SESSION['SimpleSAMLphp_SESSION'])) {
            return null;
        }

        $session = $_SESSION['SimpleSAMLphp_SESSION'];
        Assert::string($session);

        try {
            $session = unserialize($session);
        } catch (\Throwable $e) {
            Logger::warning('Session load failed using unserialize().'
                         .  'If you have just upgraded this might be ok. '
                          . 'If not there might be an issue with your storage. '
                          . $e->getMessage());
            $session = null;  # sometimes deserializing fails, so we throw it away
        }
        return ($session !== false) ? $session : null;
    }


    /**
     * Check whether the session cookie is set.
     *
     * This function will only return false if is is certain that the cookie isn't set.
     *
     * @return boolean True if it was set, false otherwise.
     */
    public function hasSessionCookie(): bool
    {
        return array_key_exists($this->cookie_name, $_COOKIE);
    }


    /**
     * Get the cookie parameters that should be used for session cookies.
     *
     * This function contains some adjustments from the default to provide backwards-compatibility.
     *
     * @return array The cookie parameters for our sessions.
     * @link http://www.php.net/manual/en/function.session-get-cookie-params.php
     *
     * @throws \SimpleSAML\Error\Exception If both 'session.phpsession.limitedpath' and 'session.cookie.path' options
     * are set at the same time in the configuration.
     */
    public function getCookieParams(): array
    {
        $config = Configuration::getInstance();

        $ret = parent::getCookieParams();

        if ($config->hasValue('session.phpsession.limitedpath') && $config->hasValue('session.cookie.path')) {
            throw new Error\Exception(
                'You cannot set both the session.phpsession.limitedpath and session.cookie.path options.',
            );
        } elseif ($config->hasValue('session.phpsession.limitedpath')) {
            $ret['path'] = $config->getOptionalBoolean(
                'session.phpsession.limitedpath',
                false,
            ) ? $config->getBasePath() : '/';
        }

        $ret['httponly'] = $config->getOptionalBoolean('session.phpsession.httponly', true);

        return $ret;
    }


    /**
     * Set a session cookie.
     *
     * @param string $sessionName The name of the session.
     * @param string|null $sessionID The session ID to use. Set to null to delete the cookie.
     * @param array|null $cookieParams Additional parameters to use for the session cookie.
     *
     * @throws \SimpleSAML\Error\CannotSetCookie If we can't set the cookie.
     */
    public function setCookie(string $sessionName, ?string $sessionID, ?array $cookieParams = null): void
    {
        if ($cookieParams === null) {
            $cookieParams = session_get_cookie_params();
        }

        $httpUtils = new Utils\HTTP();
        if ($cookieParams['secure'] && !$httpUtils->isHTTPS()) {
            throw new Error\CannotSetCookie(
                'Setting secure cookie on plain HTTP is not allowed.',
                Error\CannotSetCookie::SECURE_COOKIE,
            );
        }

        if (headers_sent()) {
            throw new Error\CannotSetCookie(
                'Headers already sent.',
                Error\CannotSetCookie::HEADERS_SENT,
            );
        }

        if (session_id() !== '') {
            // session already started, close it
            session_write_close();
        }

        /** @psalm-suppress InvalidArgument */
        session_set_cookie_params($cookieParams);

        session_id(strval($sessionID));
        @session_start();
    }
}
