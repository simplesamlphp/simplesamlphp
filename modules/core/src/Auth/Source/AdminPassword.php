<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Source;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module\core\Auth\UserPassBase;
use SimpleSAML\Utils;

/**
 * Authentication source which verifies the password against
 * the 'auth.adminpassword' configuration option.
 *
 * @package SimpleSAMLphp
 */

class AdminPassword extends UserPassBase
{
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

        $this->setForcedUsername("admin");
    }


    /**
     * Attempt to log in using the given username and password.
     *
     * On a successful login, this function should return the users attributes. On failure,
     * it should throw an exception. If the error was caused by the user entering the wrong
     * username or password, a \SimpleSAML\Error\Error('WRONGUSERPASS') should be thrown.
     *
     * Note that both the username and the password are UTF-8 encoded.
     *
     * @param string $username  The username the user wrote.
     * @param string $password  The password the user wrote.
     * @return array  Associative array with the users attributes.
     */
    protected function login(string $username, string $password): array
    {
        $config = Configuration::getInstance();
        $adminPassword = $config->getOptionalString('auth.adminpassword', '123');
        if ($adminPassword === '123') {
            // We require that the user changes the password
            throw new Error\Error('NOTSET');
        }

        if ($username !== "admin") {
            throw new Error\Error('WRONGUSERPASS');
        }

        $cryptoUtils = new Utils\Crypto();
        if (!$cryptoUtils->pwValid($adminPassword, $password)) {
            throw new Error\Error('WRONGUSERPASS');
        }
        return ['user' => ['admin']];
    }
}
