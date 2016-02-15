<?php


/**
 * This file is part of SimpleSAMLphp. See the file COPYING in the root of the distribution for licence information.
 *
 * This file defines a session handler which uses the default php session handler for storage.
 *
 * @author Olav Morken, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */
class SimpleSAML_SessionHandlerPHP extends SimpleSAML_SessionHandler
{

    /**
     * This variable contains the session cookie name.
     *
     * @var string
     */
    protected $cookie_name;


    /**
     * Initialize the PHP session handling. This constructor is protected because it should only be called from
     * SimpleSAML_SessionHandler::createSessionHandler(...).
     */
    protected function __construct()
    {
        // call the parent constructor in case it should become necessary in the future
        parent::__construct();

        /* Initialize the php session handling.
         *
         * If session_id() returns a blank string, then we need to call session start. Otherwise the session is already
         * started, and we should avoid calling session_start().
         */
        if (session_id() === '') {
            $config = SimpleSAML_Configuration::getInstance();

            $params = $this->getCookieParams();

            session_set_cookie_params(
                $params['lifetime'],
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );

            $this->cookie_name = $config->getString('session.phpsession.cookiename', null);
            if (!empty($this->cookie_name)) {
                session_name($this->cookie_name);
            } else {
                $this->cookie_name = session_name();
            }

            $savepath = $config->getString('session.phpsession.savepath', null);
            if (!empty($savepath)) {
                session_save_path($savepath);
            }
        }
    }


    /**
     * Create and set new session id.
     *
     * @return string The new session id.
     *
     * @throws SimpleSAML_Error_Exception If the cookie is marked as secure but we are not using HTTPS, or the headers
     * were already sent and therefore we cannot set the cookie.
     */
    public function newSessionId()
    {
        $session_cookie_params = session_get_cookie_params();

        if ($session_cookie_params['secure'] && !\SimpleSAML\Utils\HTTP::isHTTPS()) {
            throw new SimpleSAML_Error_Exception('Session start with secure cookie not allowed on http.');
        }

        if (headers_sent()) {
            throw new SimpleSAML_Error_Exception('Cannot create new session - headers already sent.');
        }

        // generate new (secure) session id
        $sessionId = bin2hex(openssl_random_pseudo_bytes(16));
        SimpleSAML_Session::createSession($sessionId);

        if (session_id() !== '') {
            // session already started, close it
            session_write_close();
        }

        session_id($sessionId);
        session_start();

        return session_id();
    }


    /**
     * Retrieve the session ID saved in the session cookie, if there's one.
     *
     * @return string|null The session id saved in the cookie or null if no session cookie was set.
     *
     * @throws SimpleSAML_Error_Exception If the cookie is marked as secure but we are not using HTTPS.
     */
    public function getCookieSessionId()
    {
        if (session_id() === '') {
            if (!self::hasSessionCookie()) {
                return null;
            }

            $session_cookie_params = session_get_cookie_params();

            if ($session_cookie_params['secure'] && !\SimpleSAML\Utils\HTTP::isHTTPS()) {
                throw new SimpleSAML_Error_Exception('Session start with secure cookie not allowed on http.');
            }

            $cacheLimiter = session_cache_limiter();
            if (headers_sent()) {
                /*
                 * session_start() tries to send HTTP headers depending on the configuration, according to the
                 * documentation:
                 *
                 *      http://php.net/manual/en/function.session-start.php
                 *
                 * If headers have been already sent, it will then trigger an error since no more headers can be sent.
                 * Being unable to send headers does not mean we cannot recover the session by calling session_start(),
                 * so we still want to call it. In this case, though, we want to avoid session_start() to send any
                 * headers at all so that no error is generated, so we clear the cache limiter temporarily (no headers
                 * sent then) and restore it after successfully starting the session.
                 */
                session_cache_limiter('');
            }
            session_start();
            session_cache_limiter($cacheLimiter);
        }

        return session_id();
    }


    /**
     * Retrieve the session cookie name.
     *
     * @return string The session cookie name.
     */
    public function getSessionCookieName()
    {
        return $this->cookie_name;
    }


    /**
     * Save the current session to the PHP session array.
     *
     * @param SimpleSAML_Session $session The session object we should save.
     */
    public function saveSession(SimpleSAML_Session $session)
    {
        $_SESSION['SimpleSAMLphp_SESSION'] = serialize($session);
    }


    /**
     * Load the session from the PHP session array.
     *
     * @param string|null $sessionId The ID of the session we should load, or null to use the default.
     *
     * @return SimpleSAML_Session|null The session object, or null if it doesn't exist.
     *
     * @throws SimpleSAML_Error_Exception If it wasn't possible to disable session cookies or we are trying to load a
     * PHP session with a specific identifier and it doesn't match with the current session identifier.
     */
    public function loadSession($sessionId = null)
    {
        assert('is_string($sessionId) || is_null($sessionId)');

        if ($sessionId !== null) {
            if (session_id() === '') {
                // session not initiated with getCookieSessionId(), start session without setting cookie
                $ret = ini_set('session.use_cookies', '0');
                if ($ret === false) {
                    throw new SimpleSAML_Error_Exception('Disabling PHP option session.use_cookies failed.');
                }

                session_id($sessionId);
                session_start();
            } elseif ($sessionId !== session_id()) {
                throw new SimpleSAML_Error_Exception('Cannot load PHP session with a specific ID.');
            }
        } elseif (session_id() === '') {
            self::getCookieSessionId();
        }

        if (!isset($_SESSION['SimpleSAMLphp_SESSION'])) {
            return null;
        }

        $session = $_SESSION['SimpleSAMLphp_SESSION'];
        assert('is_string($session)');

        $session = unserialize($session);
        assert('$session instanceof SimpleSAML_Session');

        return $session;
    }


    /**
     * Check whether the session cookie is set.
     *
     * This function will only return false if is is certain that the cookie isn't set.
     *
     * @return boolean True if it was set, false otherwise.
     */
    public function hasSessionCookie()
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
     * @throws SimpleSAML_Error_Exception If both 'session.phpsession.limitedpath' and 'session.cookie.path' options
     * are set at the same time in the configuration.
     */
    public function getCookieParams()
    {
        $config = SimpleSAML_Configuration::getInstance();

        $ret = parent::getCookieParams();

        if ($config->hasValue('session.phpsession.limitedpath') && $config->hasValue('session.cookie.path')) {
            throw new SimpleSAML_Error_Exception(
                'You cannot set both the session.phpsession.limitedpath and session.cookie.path options.'
            );
        } elseif ($config->hasValue('session.phpsession.limitedpath')) {
            $ret['path'] = $config->getBoolean(
                'session.phpsession.limitedpath',
                false
            ) ? '/'.$config->getBaseURL() : '/';
        }

        $ret['httponly'] = $config->getBoolean('session.phpsession.httponly', true);

        return $ret;
    }
}
