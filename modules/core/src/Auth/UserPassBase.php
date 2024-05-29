<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth;

use Exception;
use SimpleSAML\{Auth, Configuration, Error, Logger, Module, Utils};
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\Constants as C;
use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Helper class for username/password authentication.
 *
 * This helper class allows for implementations of username/password authentication by
 * implementing a single function: login($username, $password)
 *
 * @package SimpleSAMLphp
 */
abstract class UserPassBase extends Auth\Source
{
    /**
     * The string used to identify our states.
     */
    public const STAGEID = '\SimpleSAML\Module\core\Auth\UserPassBase.state';

    /**
     * The key of the AuthId field in the state.
     */
    public const AUTHID = '\SimpleSAML\Module\core\Auth\UserPassBase.AuthId';

    /**
     * Username we should force.
     *
     * A forced username cannot be changed by the user.
     * If this is NULL, we won't force any username.
     *
     * @var string|null
     */
    private ?string $forcedUsername = null;

    /**
     * Links to pages from login page.
     * From configuration
     *
     * @var array
     */
    protected array $loginLinks = [];

    /**
     * Storage for authsource config option remember.username.enabled
     * /loginuserpass and /loginuserpassorg pages/templates use this option to
     * present users with a checkbox to save their username for the next login request.
     *
     * @var bool
     */
    protected bool $rememberUsernameEnabled = false;

    /**
     * Storage for authsource config option remember.username.checked
     * /loginuserpass and /loginuserpassorg pages/templates use this option
     * to default the remember username checkbox to checked or not.
     *
     * @var bool
     */
    protected bool $rememberUsernameChecked = false;

    /**
     * Storage for general config option session.rememberme.enable.
     * /loginuserpass page/template uses this option to present
     * users with a checkbox to keep their session alive across
     * different browser sessions (that is, closing and opening the
     * browser again).
     *
     * @var bool
     */
    protected bool $rememberMeEnabled = false;

    /**
     * Storage for general config option session.rememberme.checked.
     * /loginuserpass page/template uses this option to default
     * the "remember me" checkbox to checked or not.
     *
     * @var bool
     */
    protected bool $rememberMeChecked = false;


    /**
     * Constructor for this authentication source.
     *
     * All subclasses who implement their own constructor must call this constructor before
     * using $config for anything.
     *
     * @param array $info  Information about this authentication source.
     * @param array &$config  Configuration for this authentication source.
     */
    public function __construct(array $info, array &$config)
    {
        if (isset($config['core:loginpage_links'])) {
            $this->loginLinks = $config['core:loginpage_links'];
        }

        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        // Get the remember username config options
        if (isset($config['remember.username.enabled'])) {
            $this->rememberUsernameEnabled = (bool) $config['remember.username.enabled'];
            unset($config['remember.username.enabled']);
        }
        if (isset($config['remember.username.checked'])) {
            $this->rememberUsernameChecked = (bool) $config['remember.username.checked'];
            unset($config['remember.username.checked']);
        }

        // get the "remember me" config options
        $sspcnf = Configuration::getInstance();
        $this->rememberMeEnabled = $sspcnf->getOptionalBoolean('session.rememberme.enable', false);
        $this->rememberMeChecked = $sspcnf->getOptionalBoolean('session.rememberme.checked', false);
    }


    /**
     * Set forced username.
     *
     * @param string|null $forcedUsername  The forced username.
     */
    public function setForcedUsername(?string $forcedUsername): void
    {
        Assert::nullOrString($forcedUsername);
        $this->forcedUsername = $forcedUsername;
    }

    /**
     * Return login links from configuration
     * @return string[]
     */
    public function getLoginLinks(): array
    {
        return $this->loginLinks;
    }


    /**
     * Getter for the authsource config option remember.username.enabled
     * @return bool
     */
    public function getRememberUsernameEnabled(): bool
    {
        return $this->rememberUsernameEnabled;
    }


    /**
     * Getter for the authsource config option remember.username.checked
     * @return bool
     */
    public function getRememberUsernameChecked(): bool
    {
        return $this->rememberUsernameChecked;
    }


    /**
     * Check if the "remember me" feature is enabled.
     * @return bool TRUE if enabled, FALSE otherwise.
     */
    public function isRememberMeEnabled(): bool
    {
        return $this->rememberMeEnabled;
    }


    /**
     * Check if the "remember me" checkbox should be checked.
     * @return bool TRUE if enabled, FALSE otherwise.
     */
    public function isRememberMeChecked(): bool
    {
        return $this->rememberMeChecked;
    }


    /**
     * Initialize login.
     *
     * This function saves the information about the login, and redirects to a
     * login page.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request  The current request
     * @param array &$state  Information about the current authentication.
     */
    public function authenticate(Request $request, array &$state): ?Response
    {
        /*
         * Save the identifier of this authentication source, so that we can
         * retrieve it later. This allows us to call the login()-function on
         * the current object.
         */
        $state[self::AUTHID] = $this->authId;

        // What username we should force, if any
        if ($this->forcedUsername !== null) {
            /*
             * This is accessed by the login form, to determine if the user
             * is allowed to change the username.
             */
            $state['forcedUsername'] = $this->forcedUsername;
        }

        // ECP requests supply authentication credentials with the AuthnRequest
        // so we validate them now rather than redirecting. The SAML spec
        // doesn't define how the credentials are transferred, but Office 365
        // uses the Authorization header, so we will just use that in lieu of
        // other use cases.
        if (isset($state['saml:Binding']) && $state['saml:Binding'] === C::BINDING_PAOS) {
            if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
                Logger::error("ECP AuthnRequest did not contain Basic Authentication header");
                // TODO Return a SOAP fault instead of using the current binding?
                throw new Error\Error(Error\ErrorCodes::WRONGUSERPASS);
            }

            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];

            if (isset($state['forcedUsername'])) {
                $username = $state['forcedUsername'];
            }

            $attributes = $this->login($username, $password);
            $state['Attributes'] = $attributes;

            return null;
        }

        // Save the $state-array, so that we can restore it after a redirect
        $id = Auth\State::saveState($state, self::STAGEID);

        /*
         * Redirect to the login form. We include the identifier of the saved
         * state array as a parameter to the login form.
         */
        $url = Module::getModuleURL('core/loginuserpass');
        $params = ['AuthState' => $id];

        $httpUtils = new Utils\HTTP();
        return $httpUtils->redirectTrustedURL($url, $params);
    }


    /**
     * Attempt to log in using the given username and password.
     *
     * On a successful login, this function should return the users attributes. On failure,
     * it should throw an exception/error. If the error was caused by the user entering the wrong
     * username or password, a \SimpleSAML\Error\Error(\SimpleSAML\Error\ErrorCodes::WRONGUSERPASS) should be thrown.
     *
     * Note that both the username and the password are UTF-8 encoded.
     *
     * @param string $username  The username the user wrote.
     * @param string $password  The password the user wrote.
     * @return array Associative array with the user's attributes.
     */
    abstract protected function login(string $username, #[\SensitiveParameter] string $password): array;


    /**
     * Handle login request.
     *
     * This function is used by the login form (core/loginuserpass) when the user
     * enters a username and password. On success, it will not return. On wrong
     * username/password failure, and other errors, it will throw an exception.
     *
     * @param string $authStateId  The identifier of the authentication state.
     * @param string $username  The username the user wrote.
     * @param string $password  The password the user wrote.
     */
    public static function handleLogin(
        string $authStateId,
        string $username,
        #[\SensitiveParameter]
        string $password,
    ): Response {
        // Here we retrieve the state array we saved in the authenticate-function.
        $state = Auth\State::loadState($authStateId, self::STAGEID);

        // Retrieve the authentication source we are executing.
        Assert::keyExists($state, self::AUTHID);

        /** @var \SimpleSAML\Module\core\Auth\UserPassBase|null $source */
        $source = Auth\Source::getById($state[self::AUTHID]);
        if ($source === null) {
            throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
        }

        /*
         * $source now contains the authentication source on which authenticate()
         * was called. We should call login() on the same authentication source.
         */

        // Attempt to log in
        try {
            $attributes = $source->login($username, $password);
        } catch (Exception $e) {
            Logger::stats('Unsuccessful login attempt from ' . $_SERVER['REMOTE_ADDR'] . '.');
            throw $e;
        }

        Logger::stats('User \'' . $username . '\' successfully authenticated from ' . $_SERVER['REMOTE_ADDR']);

        // Save the attributes we received from the login-function in the $state-array
        $state['Attributes'] = $attributes;

        // Return control to SimpleSAMLphp after successful authentication.
        return parent::completeAuth($state);
    }
}
