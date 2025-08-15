<?php

declare(strict_types=1);

namespace SimpleSAML\Module\exampleauth\Auth\Source;

use Exception;
use SAML2\Constants;
use SimpleSAML\Auth;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Error;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Utils;
use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Profileauth authentication source.
 *
 * This class is an authentication source which stores all users in an array,
 * and authenticates users by clicking on their name.
 *
 * @package SimpleSAMLphp
 */

class UserClick extends Auth\Source
{
    /**
     * The string used to identify our states.
     */
    public const STAGEID = '\SimpleSAML\Module\exampleauth\Auth\UserClick.state';

    /**
     * The key of the AuthId field in the state.
     */
    public const AUTHID = '\SimpleSAML\Module\exampleauth\Auth\UserClick.AuthId';

    /**
     * Our users, stored in an associative array. The key of the array is "<id>",
     * while the value of each element is a new array with the attributes for each user.
     *
     * @var array
     */
    public array $users = [];

    protected function getUsers($config) {
        $users = [];

        // Validate and parse our configuration
        foreach ($config as $id => $attributes) {
            $attrUtils = new Utils\Attributes();

            try {
                $attributes = $attrUtils->normalizeAttributesArray($attributes);
            } catch (Exception $e) {
                throw new Exception('Invalid attributes for user ' . $id .
                    ' in authentication source ' . $this->authId . ': ' . $e->getMessage());
            }
            $users[$id] = $attributes;
        }
        return $users;
    }

    /**
     * Constructor for this authentication source.
     *
     * @param array $info  Information about this authentication source.
     * @param array $config  Configuration.
     */
    public function __construct(array $info, array $config)
    {
        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        if (array_key_exists('users', $config)) {
            $users = $config['users'];
        } else {
            Logger::warning("Module exampleauth:UserClick misconfigured." .
                "Put users in 'users' key in your authsource.");
            throw new Error\Error(Error\ErrorCodes::WRONGUSERPASS);
        }

        $this->users = $this->getUsers($users);

    }

    /**
     * Initialize login.
     *
     * This function saves the information about the login, and redirects to a
     * login page.
     *
     * @param array &$state  Information about the current authentication.
     */
    public function authenticate(array &$state): void
    {
        /*
         * Save the identifier of this authentication source, so that we can
         * retrieve it later. This allows us to call the login()-function on
         * the current object.
         */
        $state[self::AUTHID] = $this->authId;

        // Save the $state-array, so that we can restore it after a redirect
        $id = Auth\State::saveState($state, self::STAGEID);

        /*
         * If there is only one user configured, skip the persona chooser
         */
        if (count($this->users) == 1) {
            $this->handleLogin($id, 0);
        }

        /*
         * Redirect to the login form. We include the identifier of the saved
         * state array as a parameter to the login form.
         */
        $url = Module::getModuleURL('exampleauth/profileauth');
        $params = ['AuthState' => $id];
        $httpUtils = new Utils\HTTP();
        $httpUtils->redirectTrustedURL($url, $params);

        // The previous function never returns, so this code is never executed.
        assert::true(false);
    }


    /**
     * Attempt to log in using the given user id.
     *
     * On a successful login, this function should return the users attributes. On failure,
     * it should throw an exception. If the error was caused by the user entering the wrong
     * username or password, a \SimpleSAML\Error\Error(\SimpleSAML\Error\ErrorCodes::WRONGUSERPASS) should be thrown.
     *
     * Note that both the username and the password are UTF-8 encoded.
     *
     * @param int $id  The username the user wrote.
     * @param string $password  The password the user wrote.
     * @return array  Associative array with the users attributes.
     */
    protected function login(int $id): array
    {
        if (!array_key_exists($id, $this->users)) {
            throw new Error\Error(Error\ErrorCodes::WRONGUSERPASS);
        }

        return $this->users[$id];
    }

    /**
     * Handle login request.
     *
     * This function is used by the login form (exampleauth/login) when the user
     * enters a username and password. On success, it will not return. On wrong
     * username/password failure, and other errors, it will throw an exception.
     *
     * @param string $authStateId  The identifier of the authentication state.
     * @param string $id  The username the user wrote.
     */
    public static function handleLogin(string $authStateId, int $id): void
    {
        // Here we retrieve the state array we saved in the authenticate-function.
        $state = Auth\State::loadState($authStateId, self::STAGEID);

        // Retrieve the authentication source we are executing.
        Assert::keyExists($state, self::AUTHID);

        /** @var \SimpleSAML\Module\exampleauth\Auth\Source\UserClick|null $source */
        $source = Auth\Source::getById($state[self::AUTHID]);
        if ($source === null) {
            throw new \Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
        }

        /*
         * $source now contains the authentication source on which authenticate()
         * was called. We should call login() on the same authentication source.
         */

        // Attempt to log in
        try {
            $attributes = $source->login($id);
        } catch (\Exception $e) {
            Logger::stats('Unsuccessful login attempt from ' . $_SERVER['REMOTE_ADDR'] . '.');
            throw $e;
        }

        Logger::stats('User \'' . $id . '\' successfully authenticated from ' . $_SERVER['REMOTE_ADDR']);

        // Save the attributes we received from the login-function in the $state-array
        $state['Attributes'] = $attributes;

        // Return control to SimpleSAMLphp after successful authentication.
        Auth\Source::completeAuth($state);
    }


}
