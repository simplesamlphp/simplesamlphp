<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth;

use Exception;
use SimpleSAML\{Auth, Error, Logger, Module, Utils};
use SimpleSAML\Assert\Assert;
use Symfony\Component\HttpFoundation\{Request, Response};

use function count;
use function explode;

/**
 * Helper class for username/password/organization authentication.
 *
 * This helper class allows for implementations of username/password/organization
 * authentication by implementing two functions:
 * - login($username, $password, $organization)
 * - getOrganizations()
 *
 * @package SimpleSAMLphp
 */
abstract class UserPassOrgBase extends Auth\Source
{
    /**
     * The string used to identify our states.
     */
    public const STAGEID = '\SimpleSAML\Module\core\Auth\UserPassOrgBase.state';

    /**
     * The key of the AuthId field in the state.
     */
    public const AUTHID = '\SimpleSAML\Module\core\Auth\UserPassOrgBase.AuthId';

    /**
     * The key of the OrgId field in the state, identifies which org was selected.
     */
    public const ORGID = '\SimpleSAML\Module\core\Auth\UserPassOrgBase.SelectedOrg';

    /**
     * What way do we handle the organization as part of the username.
     * Three values:
     *  'none': Force the user to select the correct organization from the dropdown box.
     *  'allow': Allow the user to enter the organization as part of the username.
     *  'force': Remove the dropdown box.
     *
     * @var string
     */
    private string $usernameOrgMethod;

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
     * Storage for authsource config option remember.organization.enabled
     * /loginuserpassorg page/template use this option to present users
     * with a checkbox to save their organization choice for the next login request.
     *
     * @var bool
     */
    protected bool $rememberOrganizationEnabled = false;

    /**
     * Storage for authsource config option remember.organization.checked
     * /loginuserpassorg page/template use this option to
     * default the remember organization checkbox to checked or not.
     *
     * @var bool
     */
    protected bool $rememberOrganizationChecked = false;


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
        // Get the remember organization config options
        if (isset($config['remember.organization.enabled'])) {
            $this->rememberOrganizationEnabled = (bool) $config['remember.organization.enabled'];
            unset($config['remember.organization.enabled']);
        }
        if (isset($config['remember.organization.checked'])) {
            $this->rememberOrganizationChecked = (bool) $config['remember.organization.checked'];
            unset($config['remember.organization.checked']);
        }

        $this->usernameOrgMethod = 'none';
    }


    /**
     * Configure the way organizations as part of the username is handled.
     *
     * There are three possible values:
     * - 'none': Force the user to select the correct organization from the dropdown box.
     * - 'allow': Allow the user to enter the organization as part of the username.
     * - 'force': Remove the dropdown box.
     *
     * If unconfigured, the default is 'none'.
     *
     * @param string $usernameOrgMethod  The method which should be used.
     */
    protected function setUsernameOrgMethod(string $usernameOrgMethod): void
    {
        Assert::oneOf($usernameOrgMethod, ['none', 'allow', 'force']);

        $this->usernameOrgMethod = $usernameOrgMethod;
    }


    /**
     * Retrieve the way organizations as part of the username should be handled.
     *
     * There are three possible values:
     * - 'none': Force the user to select the correct organization from the dropdown box.
     * - 'allow': Allow the user to enter the organization as part of the username.
     * - 'force': Remove the dropdown box.
     *
     * @return string  The method which should be used.
     */
    public function getUsernameOrgMethod(): string
    {
        return $this->usernameOrgMethod;
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
     * Getter for the authsource config option remember.organization.enabled
     * @return bool
     */
    public function getRememberOrganizationEnabled(): bool
    {
        return $this->rememberOrganizationEnabled;
    }


    /**
     * Getter for the authsource config option remember.organization.checked
     * @return bool
     */
    public function getRememberOrganizationChecked(): bool
    {
        return $this->rememberOrganizationChecked;
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
        // We are going to need the authId in order to retrieve this authentication source later
        $state[self::AUTHID] = $this->authId;

        $id = Auth\State::saveState($state, self::STAGEID);

        $url = Module::getModuleURL('core/loginuserpassorg');
        $params = ['AuthState' => $id];

        $httpUtils = new Utils\HTTP();
        return $httpUtils->redirectTrustedURL($url, $params);
    }


    /**
     * Attempt to log in using the given username, password and organization.
     *
     * On a successful login, this function should return the users attributes. On failure,
     * it should throw an exception/error. If the error was caused by the user entering the wrong
     * username or password, a \SimpleSAML\Error\Error(\SimpleSAML\Error\ErrorCodes::WRONGUSERPASS) should be thrown.
     *
     * Note that both the username and the password are UTF-8 encoded.
     *
     * @param string $username  The username the user wrote.
     * @param string $password  The password the user wrote.
     * @param string $organization  The id of the organization the user chose.
     * @return array  Associative array with the user's attributes.
     */
    abstract protected function login(
        string $username,
        #[\SensitiveParameter]
        string $password,
        string $organization,
    ): array;


    /**
     * Retrieve list of organizations.
     *
     * The list of organizations is an associative array. The key of the array is the
     * id of the organization, and the value is the description. The value can be another
     * array, in which case that array is expected to contain language-code to
     * description mappings.
     *
     * @return array  Associative array with the organizations.
     */
    abstract protected function getOrganizations(): array;


    /**
     * Handle login request.
     *
     * This function is used by the login form (core/loginuserpassorg) when the user
     * enters a username and password. On success, it will not return. On wrong
     * username/password failure, and other errors, it will throw an exception.
     *
     * @param string $authStateId  The identifier of the authentication state.
     * @param string $username  The username the user wrote.
     * @param string $password  The password the user wrote.
     * @param string $organization  The id of the organization the user chose.
     */
    public static function handleLogin(
        string $authStateId,
        string $username,
        #[\SensitiveParameter]
        string $password,
        string $organization,
    ): Response {
        /* Retrieve the authentication state. */
        $state = Auth\State::loadState($authStateId, self::STAGEID);

        /* Find authentication source. */
        Assert::keyExists($state, self::AUTHID);

        /** @var \SimpleSAML\Module\core\Auth\UserPassOrgBase|null $source */
        $source = Auth\Source::getById($state[self::AUTHID]);
        if ($source === null) {
            throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
        }

        $orgMethod = $source->getUsernameOrgMethod();
        if ($orgMethod !== 'none') {
            $tmp = explode('@', $username, 2);
            if (count($tmp) === 2) {
                $username = $tmp[0];
                $organization = $tmp[1];
            } else {
                if ($orgMethod === 'force') {
                    /* The organization should be a part of the username, but isn't. */
                    throw new Error\Error(Error\ErrorCodes::WRONGUSERPASS);
                }
            }
        }

        /* Attempt to log in. */
        try {
            $attributes = $source->login($username, $password, $organization);
        } catch (Exception $e) {
            Logger::stats('Unsuccessful login attempt from ' . $_SERVER['REMOTE_ADDR'] . '.');
            throw $e;
        }

        Logger::stats(
            'User \'' . $username . '\' at \'' . $organization
            . '\' successfully authenticated from ' . $_SERVER['REMOTE_ADDR'],
        );

        // Add the selected Org to the state
        $state[self::ORGID] = $organization;
        $state['PersistentAuthData'][] = self::ORGID;

        $state['Attributes'] = $attributes;
        return parent::completeAuth($state);
    }


    /**
     * Get available organizations.
     *
     * This function is used by the login form to get the available organizations.
     *
     * @param string $authStateId  The identifier of the authentication state.
     * @return array|null  Array of organizations. NULL if the user must enter the
     *         organization as part of the username.
     */
    public static function listOrganizations(string $authStateId): ?array
    {
        /* Retrieve the authentication state. */
        $state = Auth\State::loadState($authStateId, self::STAGEID);

        /* Find authentication source. */
        Assert::keyExists($state, self::AUTHID);

        /** @var \SimpleSAML\Module\core\Auth\UserPassOrgBase|null $source */
        $source = Auth\Source::getById($state[self::AUTHID]);
        if ($source === null) {
            throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
        }

        $orgMethod = $source->getUsernameOrgMethod();
        if ($orgMethod === 'force') {
            return null;
        }

        return $source->getOrganizations();
    }
}
