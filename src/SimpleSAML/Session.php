<?php

declare(strict_types=1);

namespace SimpleSAML;

use DOMNodeList;
use Exception;
use SimpleSAML\{Error, Utils};
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\XML\saml\AttributeValue;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function bin2hex;
use function call_user_func;
use function defined;
use function get_object_vars;
use function hash_equals;
use function header_register_callback;
use function intval;
use function is_a;
use function is_callable;
use function is_int;
use function is_string;
use function openssl_random_pseudo_bytes;
use function php_sapi_name;
use function time;
use function var_export;

/**
 * The Session class holds information about a user session, and everything attached to it.
 *
 * The session will have a duration and validity, and also cache information about the different
 * federation protocols, as Shibboleth and SAML 2.0. On the IdP side the Session class holds
 * information about all the currently logged in SPs. This is used when the user initiates a
 * Single-Log-Out.
 *
 * Bear in mind that the session object implements the Serializable interface, and as such,
 * all its contents MUST be serializable. If you need to store something in the session object
 * that is not serializable, make sure to convert it first to a representation that can be
 * serialized.
 *
 * @package SimpleSAMLphp
 */
class Session implements Utils\ClearableState
{
    /**
     * This is a timeout value for setData, which indicates that the data
     * should never be deleted, i.e. lasts the whole session lifetime.
     */
    public const DATA_TIMEOUT_SESSION_END = 'sessionEndTimeout';

    /**
     * The list of loaded session objects.
     *
     * This is an associative array indexed with the session id.
     *
     * @var array
     */
    private static array $sessions = [];

    /**
     * This variable holds the instance of the session - Singleton approach.
     *
     * Warning: do not set the instance manually, call Session::load() instead.
     *
     * @var \SimpleSAML\Session|null
     */
    private static ?Session $instance = null;

    /**
     * The global configuration.
     *
     * @var \SimpleSAML\Configuration|null
     */
    private static ?Configuration $config;

    /**
     * The session ID of this session.
     *
     * @var string|null
     */
    private ?string $sessionId = null;

    /**
     * Transient session flag.
     *
     * @var boolean|false
     */
    private bool $transient = false;

    /**
     * The track id is a new random unique identifier that is generated for each session.
     * This is used in the debug logs and error messages to easily track more information
     * about what went wrong.
     *
     * @var string
     */
    private string $trackid;

    /**
     * @var integer|null
     */
    private ?int $rememberMeExpire = null;

    /**
     * Marks a session as modified, and therefore needs to be saved before destroying
     * this object.
     *
     * @var bool
     */
    private bool $dirty = false;

    /**
     * Tells the session object that the save callback has been registered and there's no need to register it again.
     *
     * @var bool
     */
    private bool $callback_registered = false;

    /**
     * This is an array of objects which will expire automatically after a set time. It is used
     * where one needs to store some information - for example a logout request, but doesn't
     * want it to be stored forever.
     *
     * The data store contains three levels of nested associative arrays. The first is the data type, the
     * second is the identifier, and the third contains the expire time of the data and the data itself.
     *
     * @var array
     */
    private array $dataStore = [];

    /**
     * The list of IdP-SP associations.
     *
     * This is an associative array with the IdP id as the key, and the list of
     * associations as the value.
     *
     * @var array
     */
    private array $associations = [];

    /**
     * The authentication token.
     *
     * This token is used to prevent session fixation attacks.
     *
     * @var string|null
     */
    private ?string $authToken = null;

    /**
     * Authentication data.
     *
     * This is an array with authentication data for the various authsources.
     *
     * @var array  Associative array of associative arrays.
     */
    private array $authData = [];


    /**
     * Private constructor that restricts instantiation to either getSessionFromRequest() for the current session or
     * getSession() for a specific one.
     *
     * @param boolean $transient Whether to create a transient session or not.
     */
    private function __construct(bool $transient = false)
    {
        $this->setConfiguration(Configuration::getInstance());

        if (php_sapi_name() === 'cli' || defined('STDIN')) {
            $this->trackid = 'CL' . bin2hex(openssl_random_pseudo_bytes(4));
            Logger::setTrackId($this->trackid);
            $this->transient = $transient;
            return;
        }

        if ($transient) {
            // transient session
            $this->trackid = 'TR' . bin2hex(openssl_random_pseudo_bytes(4));
            Logger::setTrackId($this->trackid);
            $this->transient = true;
        } else {
            // regular session
            $sh = SessionHandler::getSessionHandler();
            $this->sessionId = $sh->newSessionId();
            $sh->setCookie($sh->getSessionCookieName(), $this->sessionId, $sh->getCookieParams());


            $this->trackid = bin2hex(openssl_random_pseudo_bytes(5));
            Logger::setTrackId($this->trackid);

            $this->markDirty();

            // initialize data for session check function if defined
            $checkFunction = self::$config->getOptionalValue('session.check_function', null);
            if (is_callable($checkFunction)) {
                call_user_func($checkFunction, $this, true);
            }
        }
    }


    /**
     * Set the configuration we should use.
     *
     * @param Configuration $config
     */
    public function setConfiguration(Configuration $config): void
    {
        self::$config = $config;
    }


    /**
     * Serialize this Session.
     *
     * This method will be invoked by any calls to serialize().
     *
     * @return array The serialized representation of this Session.
     */
    public function __serialize(): array
    {
        return get_object_vars($this);
    }


    /**
     * Unserialize a session object and load it..
     *
     * This method will be invoked by any calls to unserialize(), allowing us to restore any data that might not
     * be serializable in its original form (e.g.: DOM objects).
     *
     * @param array $serialized The serialized representation of a session that we want to restore.
     */
    public function __unserialize($serialized): void
    {
        foreach ($serialized as $k => $v) {
            $this->$k = $v;
        }
        self::$config = Configuration::getInstance();

        // look for any raw attributes and load them in the 'Attributes' array
        foreach ($this->authData as $authority => $parameters) {
            if (!array_key_exists('RawAttributes', $parameters)) {
                continue;
            }

            foreach ($parameters['RawAttributes'] as $attribute => $values) {
                foreach ($values as $idx => $value) {
                    // this should be originally a DOMNodeList
                    /* @var \SimpleSAML\SAML2\XML\saml\AttributeValue $value */
                    $this->authData[$authority]['Attributes'][$attribute][$idx] = $value->getElement()->childNodes;
                }
            }
        }
    }


    /**
     * Retrieves the current session. Creates a new session if there's not one.
     *
     * @return \SimpleSAML\Session The current session.
     * @throws \Exception When session couldn't be initialized and the session fallback is disabled by configuration.
     */
    public static function getSessionFromRequest(): Session
    {
        // check if we already have initialized the session
        /** @psalm-suppress RedundantCondition */
        if (self::$instance !== null) {
            return self::$instance;
        }

        // check if we have stored a session stored with the session handler
        try {
            $session = self::getSession();
        } catch (Exception $e) {
            /*
             * For some reason, we were unable to initialize this session. Note that this error might be temporary, and
             * it's possible that we can recover from it in subsequent requests, so we should not try to create a new
             * session here. Therefore, use just a transient session and throw the exception for someone else to handle
             * it.
             */
            self::useTransientSession();
            Logger::error('Error loading session: ' . $e->getMessage());
            if ($e instanceof Error\Exception) {
                $cause = $e->getCause();
                if ($cause instanceof Exception) {
                    throw $cause;
                }
            }
            throw $e;
        }

        // if getSession() found it, use it
        if ($session instanceof Session) {
            return self::load($session);
        }

        /*
         * We didn't have a session loaded when we started, but we have it now. At this point, getSession() failed but
         * it must have triggered the creation of a session at some point during the process (e.g. while logging an
         * error message). This means we don't need to create a new session again, we can use the one that's loaded now
         * instead.
         */

        /** @psalm-suppress TypeDoesNotContainType */
        if (self::$instance !== null) {
            return self::$instance;
        }

        // try to create a new session
        try {
            self::load(new Session());
        } catch (Error\CannotSetCookie $e) {
            // can't create a regular session because we can't set cookies. Use transient.
            $c = Configuration::getInstance();
            self::useTransientSession();

            if ($e->getCode() === Error\CannotSetCookie::SECURE_COOKIE) {
                throw new Error\CriticalConfigurationError(
                    $e->getMessage(),
                    null,
                    $c->toArray(),
                );
            }
            Logger::error('Error creating session: ' . $e->getMessage());
        }

        // we must have a session now, either regular or transient
        /** @var \SimpleSAML\Session */
        return self::$instance;
    }


    /**
     * Get a session from the session handler.
     *
     * @param string|null $sessionId The session we should get, or null to get the current session.
     *
     * @return \SimpleSAML\Session|null The session that is stored in the session handler,
     *   or null if the session wasn't found.
     */
    public static function getSession(?string $sessionId = null): ?Session
    {
        $sh = SessionHandler::getSessionHandler();

        if ($sessionId === null) {
            $checkToken = true;
            $sessionId = $sh->getCookieSessionId();
            if ($sessionId === null) {
                return null;
            }
        } else {
            $checkToken = false;
        }

        if (array_key_exists($sessionId, self::$sessions)) {
            return self::$sessions[$sessionId];
        }

        $session = $sh->loadSession($sessionId);
        if ($session === null) {
            return null;
        }

        if ($checkToken) {
            $globalConfig = Configuration::getInstance();

            if ($session->authToken !== null) {
                $authTokenCookieName = $globalConfig->getOptionalString(
                    'session.authtoken.cookiename',
                    'SimpleSAMLAuthToken',
                );
                if (!isset($_COOKIE[$authTokenCookieName])) {
                    Logger::warning('Missing AuthToken cookie.');
                    return null;
                }
                if (!hash_equals($session->authToken, $_COOKIE[$authTokenCookieName])) {
                    Logger::warning('Invalid AuthToken cookie.');
                    return null;
                }
            }

            // run session check function if defined
            $checkFunction = $globalConfig->getOptionalValue('session.check_function', null);
            if (is_callable($checkFunction)) {
                $check = call_user_func($checkFunction, $session);
                if ($check !== true) {
                    Logger::warning('Session did not pass check function.');
                    return null;
                }
            }
        }

        self::$sessions[$sessionId] = $session;

        return $session;
    }


    /**
     * Load a given session as the current one.
     *
     * This method will also set the track ID in the logger to the one in the given session.
     *
     * Warning: never set self::$instance yourself, call this method instead.
     *
     * @param \SimpleSAML\Session $session The session to load.
     * @return \SimpleSAML\Session The session we just loaded, just for convenience.
     */
    private static function load(Session $session): Session
    {
        Logger::setTrackId($session->getTrackID());
        self::$instance = $session;
        return self::$instance;
    }


    /**
     * Use a transient session.
     *
     * Create a session that should not be saved at the end of the request.
     * Subsequent calls to getInstance() will return this transient session.
     *
     */
    public static function useTransientSession(): void
    {
        /** @psalm-suppress RedundantCondition */
        if (self::$instance !== null) {
            // we already have a session, don't bother with a transient session
            return;
        }

        self::load(new Session(true));
    }


    /**
     * Create a new session and cache it.
     *
     * @param string $sessionId The new session we should create.
     */
    public static function createSession(string $sessionId): void
    {
        self::$sessions[$sessionId] = null;
    }


    /**
     * Save the session to the store.
     *
     * This method saves the session to the session handler in case it has been marked as dirty.
     *
     * WARNING: please do not use this method directly unless you really need to and know what you are doing. Use
     * markDirty() instead.
     *
     */
    public function save(): void
    {
        // clean out old data
        $this->expireData();

        if (!$this->dirty) {
            // session hasn't changed, don't bother saving it
            return;
        }

        $this->dirty = false;
        $this->callback_registered = false;

        $sh = SessionHandler::getSessionHandler();

        try {
            $sh->saveSession($this);
        } catch (Exception $e) {
            if (!($e instanceof Error\Exception)) {
                $e = new Error\UnserializableException($e);
            }
            Logger::error('Unable to save session.');
            $e->logError();
        }
    }


    /**
     * Save the current session and clean any left overs that could interfere with the normal application behaviour.
     *
     * Use this method if you are using PHP sessions in your application *and* in SimpleSAMLphp, *after* you are done
     * using SimpleSAMLphp and before trying to access your application's session again.
     *
     */
    public function cleanup(): void
    {
        $this->save();
        $sh = SessionHandler::getSessionHandler();
        if ($sh instanceof SessionHandlerPHP) {
            $sh->restorePrevious();
        }
    }


    /**
     * Mark this session as dirty.
     *
     * This method will register a callback to save the session right before any output is sent to the browser.
     *
     */
    public function markDirty(): void
    {
        if ($this->isTransient()) {
            return;
        }

        $this->dirty = true;

        if ($this->callback_registered) {
            // we already have a shutdown callback registered for this object, no need to add another one
            return;
        }
        $this->callback_registered = header_register_callback([$this, 'save']);
    }


    /**
     * Destroy the session.
     *
     * Destructor for this class. It will save the session to the session handler
     * in case the session has been marked as dirty. Do nothing otherwise.
     */
    public function __destruct()
    {
        $this->save();
    }


    /**
     * Retrieve the session ID of this session.
     *
     * @return string|null  The session ID, or NULL for transient sessions.
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }


    /**
     * Retrieve if session is transient.
     *
     * @return boolean The session transient flag.
     */
    public function isTransient(): bool
    {
        return $this->transient;
    }


    /**
     * Get a unique ID that will be permanent for this session.
     * Used for debugging and tracing log files related to a session.
     *
     * @return string The unique ID.
     */
    public function getTrackID(): string
    {
        return $this->trackid;
    }


    /**
     * Get remember me expire time.
     *
     * @return integer|null The remember me expire time.
     */
    public function getRememberMeExpire(): ?int
    {
        return $this->rememberMeExpire;
    }


    /**
     * Set remember me expire time.
     *
     * @param int $lifetime Number of seconds after when remember me session cookies expire.
     */
    public function setRememberMeExpire(?int $lifetime = null): void
    {
        if ($lifetime === null) {
            $lifetime = self::$config->getOptionalInteger('session.rememberme.lifetime', 14 * 86400);
        }
        $this->rememberMeExpire = time() + $lifetime;

        $cookieParams = ['lifetime' => $lifetime];
        $this->updateSessionCookies($cookieParams);
    }


    /**
     * Marks the user as logged in with the specified authority.
     *
     * If the user already has logged in, the user will be logged out first.
     *
     * @param string     $authority The authority the user logged in with.
     * @param array      $data The authentication data for this authority.
     *
     * @throws Error\CannotSetCookie If the authentication token cannot be set for some reason.
     */
    public function doLogin(string $authority, array $data = []): void
    {
        Logger::debug('Session: doLogin("' . $authority . '")');

        $this->markDirty();

        if (isset($this->authData[$authority])) {
            // we are already logged in, log the user out first
            $this->doLogout($authority);
        }

        $data['Authority'] = $authority;

        if (!isset($data['AuthnInstant'])) {
            $data['AuthnInstant'] = time();
        }

        $maxSessionExpire = time() + self::$config->getOptionalInteger('session.duration', 8 * 60 * 60);
        if (!isset($data['Expire']) || $data['Expire'] > $maxSessionExpire) {
            // unset, or beyond our session lifetime. Clamp it to our maximum session lifetime
            $data['Expire'] = $maxSessionExpire;
        }

        // check if we have non-serializable attribute values
        foreach ($data['Attributes'] as $attribute => $values) {
            foreach ($values as $idx => $value) {
                if (is_string($value) || is_int($value)) {
                    continue;
                }

                // at this point, this should be a DOMNodeList object...
                if (!is_a($value, DOMNodeList::class)) {
                    continue;
                }

                /* @var \DOMNodeList $value */
                if ($value->length === 0) {
                    continue;
                }

                /** @psalm-var \DOMNode $node   We made sure value has at least 1 item in the check above */
                $node = $value->item(0);

                // create an AttributeValue object and save it to 'RawAttributes', using same attribute name and index
                $attrval = new AttributeValue($node->parentNode);
                $data['RawAttributes'][$attribute][$idx] = $attrval;
            }
        }

        $this->authData[$authority] = $data;

        $randomUtils = new Utils\Random();
        $this->authToken = $randomUtils->generateID();
        $sessionHandler = SessionHandler::getSessionHandler();

        if (
            !$this->transient
            && (!empty($data['RememberMe'])
            || $this->rememberMeExpire !== null)
            && self::$config->getOptionalBoolean('session.rememberme.enable', false)
        ) {
            $this->setRememberMeExpire();
        } else {
            $httpUtils = new Utils\HTTP();
            try {
                $httpUtils->setCookie(
                    self::$config->getOptionalString('session.authtoken.cookiename', 'SimpleSAMLAuthToken'),
                    $this->authToken,
                    $sessionHandler->getCookieParams(),
                );
            } catch (Error\CannotSetCookie $e) {
                /*
                 * Something went wrong when setting the auth token. We cannot recover from this, so we better log a
                 * message and throw an exception. The user is not properly logged in anyway, so clear all login
                 * information from the session.
                 */
                unset($this->authToken);
                unset($this->authData[$authority]);
                Logger::error('Cannot set authentication token cookie: ' . $e->getMessage());
                throw $e;
            }
        }
    }


    /**
     * Marks the user as logged out.
     *
     * This function will call any registered logout handlers before marking the user as logged out.
     *
     * @param string $authority The authentication source we are logging out of.
     */
    public function doLogout(string $authority): void
    {
        Logger::debug('Session: doLogout(' . var_export($authority, true) . ')');

        if (!isset($this->authData[$authority])) {
            Logger::debug('Session: Already logged out of ' . $authority . '.');
            return;
        }

        $this->markDirty();

        $this->callLogoutHandlers($authority);
        unset($this->authData[$authority]);

        if (!$this->isValid($authority) && $this->rememberMeExpire !== null) {
            $this->rememberMeExpire = null;
            $this->updateSessionCookies();
        }
    }


    /**
     * This function calls all registered logout handlers.
     *
     * @param string $authority The authentication source we are logging out from.
     *
     * @throws \Exception If the handler is not a valid function or method.
     */
    private function callLogoutHandlers(string $authority): void
    {
        Assert::notNull($this->authData[$authority]);

        if (empty($this->authData[$authority]['LogoutHandlers'])) {
            return;
        }
        foreach ($this->authData[$authority]['LogoutHandlers'] as $handler) {
            // verify that the logout handler is a valid function
            if (!is_callable($handler)) {
                $classname = $handler[0];
                $functionname = $handler[1];

                throw new Exception(
                    'Logout handler is not a valid function: ' . $classname . '::' .
                    $functionname,
                );
            }

            // call the logout handler
            call_user_func($handler);
        }

        // we require the logout handlers to register themselves again if they want to be called later
        unset($this->authData[$authority]['LogoutHandlers']);
    }


    /**
     * Is the session representing an authenticated user, and is the session still alive.
     * This function will return false after the user has timed out.
     *
     * @param string $authority The authentication source that the user should be authenticated with.
     *
     * @return bool True if the user has a valid session, false if not.
     */
    public function isValid(string $authority): bool
    {
        if (!isset($this->authData[$authority])) {
            Logger::debug(
                'Session: ' . var_export($authority, true) .
                ' not valid because we are not authenticated.',
            );
            return false;
        }

        if ($this->authData[$authority]['Expire'] <= time()) {
            Logger::debug('Session: ' . var_export($authority, true) . ' not valid because it is expired.');
            return false;
        }

        Logger::debug('Session: Valid session found with ' . var_export($authority, true) . '.');

        return true;
    }


    /**
     * Update session cookies.
     *
     * @param array $params The parameters for the cookies.
     */
    public function updateSessionCookies(array $params = []): void
    {
        $sessionHandler = SessionHandler::getSessionHandler();
        $params = array_merge($sessionHandler->getCookieParams(), $params);

        if ($this->sessionId !== null) {
            $sessionHandler->setCookie($sessionHandler->getSessionCookieName(), $this->sessionId, $params);
        }

        if ($this->authToken !== null) {
            $httpUtils = new Utils\HTTP();
            $httpUtils->setCookie(
                self::$config->getOptionalString('session.authtoken.cookiename', 'SimpleSAMLAuthToken'),
                $this->authToken,
                $params,
            );
        }
    }


    /**
     * Set the lifetime for authentication source.
     *
     * @param string $authority The authentication source we are setting expire time for.
     * @param int    $expire The number of seconds authentication source is valid.
     */
    public function setAuthorityExpire(string $authority, ?int $expire = null): void
    {
        $this->markDirty();

        if ($expire === null) {
            $expire = time() + self::$config->getOptionalInteger('session.duration', 8 * 60 * 60);
        }

        $this->authData[$authority]['Expire'] = $expire;
    }


    /**
     * This function registers a logout handler.
     *
     * @param string $authority The authority for which register the handler.
     * @param string $classname The class which contains the logout handler.
     * @param string $functionname The logout handler function.
     *
     * @throws \Exception If the handler is not a valid function or method.
     */
    public function registerLogoutHandler(string $authority, string $classname, string $functionname): void
    {
        Assert::notNull($this->authData[$authority]);

        $logout_handler = [$classname, $functionname];

        if (!is_callable($logout_handler)) {
            throw new Exception(
                'Logout handler is not a valid function: ' . $classname . '::' .
                $functionname,
            );
        }

        $this->authData[$authority]['LogoutHandlers'][] = $logout_handler;
        $this->markDirty();
    }


    /**
     * Delete data from the data store.
     *
     * This function immediately deletes the data with the given type and id from the data store.
     *
     * @param string $type The type of the data.
     * @param string $id The identifier of the data.
     */
    public function deleteData(string $type, string $id): void
    {
        if (!array_key_exists($type, $this->dataStore)) {
            return;
        }

        unset($this->dataStore[$type][$id]);
        $this->markDirty();
    }


    /**
     * This function stores data in the data store.
     *
     * The timeout value can be Session::DATA_TIMEOUT_SESSION_END, which indicates
     * that the data should never be deleted.
     *
     * @param string   $type The type of the data. This is checked when retrieving data from the store.
     * @param string   $id The identifier of the data.
     * @param mixed    $data The data.
     * @param int|string|null $timeout The number of seconds this data should be stored after its last access.
     * This parameter is optional. The default value is set in 'session.datastore.timeout',
     * and the default is 4 hours.
     *
     * @throws \Exception If the data couldn't be stored.
     *
     */
    public function setData(string $type, string $id, mixed $data, int|string|null $timeout = null): void
    {
        if (is_string($timeout)) {
            Assert::same($timeout, self::DATA_TIMEOUT_SESSION_END);
        }

        if ($timeout === null) {
            // use the default timeout
            $timeout = self::$config->getOptionalInteger('session.datastore.timeout', null);
            if ($timeout !== null) {
                if ($timeout <= 0) {
                    throw new Exception(
                        'The value of the session.datastore.timeout' .
                        ' configuration option should be a positive integer.',
                    );
                }
            }
        }

        if ($timeout === self::DATA_TIMEOUT_SESSION_END) {
            $expires = self::DATA_TIMEOUT_SESSION_END;
        } else {
            $expires = time() + intval($timeout);
        }

        $dataInfo = [
            'expires' => $expires,
            'timeout' => $timeout,
            'data'    => $data,
        ];

        if (!array_key_exists($type, $this->dataStore)) {
            $this->dataStore[$type] = [];
        }

        $this->dataStore[$type][$id] = $dataInfo;

        $this->markDirty();
    }


    /**
     * This function removes expired data from the data store.
     *
     */
    private function expireData(): void
    {
        $ct = time();

        foreach ($this->dataStore as &$typedData) {
            foreach ($typedData as $id => $info) {
                if ($info['expires'] === self::DATA_TIMEOUT_SESSION_END) {
                    // this data never expires
                    continue;
                }

                if ($ct > $info['expires']) {
                    unset($typedData[$id]);
                    $this->markDirty();
                }
            }
        }
    }


    /**
     * This function retrieves data from the data store.
     *
     * Note that this will not change when the data stored in the data store will expire. If that is required,
     * the data should be written back with setData.
     *
     * @param string      $type The type of the data. This must match the type used when adding the data.
     * @param string|null $id The identifier of the data. Can be null, in which case null will be returned.
     * @param bool        $allowExpired Whether to fetch or not an expired Session entry. Default's to everything.
     *
     * @return mixed The data of the given type with the given id or null if the data doesn't exist in the data store.
     */
    public function getData(string $type, ?string $id, bool $allowExpired = true): mixed
    {
        if ($id === null) {
            return null;
        }

        if (!array_key_exists($type, $this->dataStore)) {
            return null;
        }

        if (!array_key_exists($id, $this->dataStore[$type])) {
            return null;
        }

        if (
            !$allowExpired
            // If 'expire' is a string then it will last for the entire Session.
            && !is_string($this->dataStore[$type][$id]['expires'])
            && $this->dataStore[$type][$id]['expires'] < time()
        ) {
            return null;
        }

        return $this->dataStore[$type][$id]['data'];
    }


    /**
     * This function retrieves all data of the specified type from the data store.
     *
     * The data will be returned as an associative array with the id of the data as the key, and the data
     * as the value of each key. The value will be stored as a copy of the original data. setData must be
     * used to update the data.
     *
     * An empty array will be returned if no data of the given type is found.
     *
     * @param string $type The type of the data.
     *
     * @return array An associative array with all data of the given type.
     */
    public function getDataOfType(string $type): array
    {
        if (!array_key_exists($type, $this->dataStore)) {
            return [];
        }

        $ret = [];
        foreach ($this->dataStore[$type] as $id => $info) {
            $ret[$id] = $info['data'];
        }

        return $ret;
    }


    /**
     * Get the current persistent authentication state.
     *
     * @param string $authority The authority to retrieve the data from.
     *
     * @return array|null  The current persistent authentication state, or null if not authenticated.
     */
    public function getAuthState(string $authority): ?array
    {
        if (!isset($this->authData[$authority])) {
            return null;
        }

        return $this->authData[$authority];
    }


    /**
     * Check whether the session cookie is set.
     *
     * This function will only return false if is is certain that the cookie isn't set.
     *
     * @return bool  true if it was set, false if not.
     */
    public function hasSessionCookie(): bool
    {
        $sh = SessionHandler::getSessionHandler();
        return $sh->hasSessionCookie();
    }


    /**
     * Add an SP association for an IdP.
     *
     * This function is only for use by the IdP class.
     *
     * @param string $idp The IdP id.
     * @param array  $association The association we should add.
     */
    public function addAssociation(string $idp, array $association): void
    {
        Assert::notNull($association['id']);
        Assert::notNull($association['Handler']);

        if (!isset($this->associations)) {
            $this->associations = [];
        }

        if (!isset($this->associations[$idp])) {
            $this->associations[$idp] = [];
        }

        $this->associations[$idp][$association['id']] = $association;

        $this->markDirty();
    }


    /**
     * Retrieve the associations for an IdP.
     *
     * This function is only for use by the IdP class.
     *
     * @param string $idp The IdP id.
     *
     * @return array  The IdP associations.
     */
    public function getAssociations(string $idp): array
    {
        if (!isset($this->associations)) {
            $this->associations = [];
        }

        if (!isset($this->associations[$idp])) {
            return [];
        }

        foreach ($this->associations[$idp] as $id => $assoc) {
            if (!isset($assoc['Expires'])) {
                continue;
            }
            if ($assoc['Expires'] >= time()) {
                continue;
            }

            unset($this->associations[$idp][$id]);
        }

        return $this->associations[$idp];
    }


    /**
     * Remove an SP association for an IdP.
     *
     * This function is only for use by the IdP class.
     *
     * @param string $idp The IdP id.
     * @param string $associationId The id of the association.
     */
    public function terminateAssociation(string $idp, string $associationId): void
    {
        if (!isset($this->associations)) {
            return;
        }

        if (!isset($this->associations[$idp])) {
            return;
        }

        unset($this->associations[$idp][$associationId]);

        $this->markDirty();
    }


    /**
     * Retrieve authentication data.
     *
     * @param string $authority The authentication source we should retrieve data from.
     * @param string $name The name of the data we should retrieve.
     *
     * @return mixed  The value, or null if the value wasn't found.
     */
    public function getAuthData(string $authority, string $name): mixed
    {
        if (!isset($this->authData[$authority][$name])) {
            return null;
        }
        return $this->authData[$authority][$name];
    }


    /**
     * Retrieve a list of authorities (authentication sources) that are currently valid within
     * this session.
     *
     * @return string[] An array containing every authority currently valid. Empty if none available.
     */
    public function getAuthorities(): array
    {
        $authorities = [];
        foreach (array_keys($this->authData) as $authority) {
            if ($this->isValid($authority)) {
                $authorities[] = $authority;
            }
        }
        return $authorities;
    }


    /**
     * Clear any configuration information cached
     */
    public static function clearInternalState(): void
    {
        self::$config = null;
        self::$instance = null;
        self::$sessions = [];
    }
}
