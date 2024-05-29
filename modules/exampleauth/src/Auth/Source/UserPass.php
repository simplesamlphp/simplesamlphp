<?php

declare(strict_types=1);

namespace SimpleSAML\Module\exampleauth\Auth\Source;

use Exception;
use SimpleSAML\Assert\Assert;
use SimpleSAML\{Error, Logger, Utils};
use SimpleSAML\Module\core\Auth\UserPassBase;

use function array_key_exists;
use function count;
use function explode;
use function is_string;

/**
 * Example authentication source - username & password.
 *
 * This class is an example authentication source which stores all username/passwords in an array,
 * and authenticates users against this array.
 *
 * @package SimpleSAMLphp
 */

class UserPass extends UserPassBase
{
    /**
     * Our users, stored in an associative array. The key of the array is "<username>:<password>",
     * while the value of each element is a new array with the attributes for each user.
     *
     * @var array
     */
    private array $users;


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

        $this->users = [];

        Assert::keyExists($config, 'users');
        $config_users = $config['users'];

        // Validate and parse our configuration
        foreach ($config_users as $userpass => $attributes) {
            if (!is_string($userpass)) {
                throw new Exception(
                    'Invalid <username>:<password> for authentication source ' . $this->authId . ': ' . $userpass,
                );
            }

            $userpass = explode(':', $userpass, 2);
            if (count($userpass) !== 2) {
                throw new Exception(
                    'Invalid <username>:<password> for authentication source ' . $this->authId . ': ' . $userpass[0],
                );
            }
            $username = $userpass[0];
            $password = $userpass[1];

            $attrUtils = new Utils\Attributes();

            try {
                $attributes = $attrUtils->normalizeAttributesArray($attributes);
            } catch (Exception $e) {
                throw new Exception('Invalid attributes for user ' . $username .
                    ' in authentication source ' . $this->authId . ': ' . $e->getMessage());
            }
            $this->users[$username . ':' . $password] = $attributes;
        }
    }


    /**
     * Attempt to log in using the given username and password.
     *
     * On a successful login, this function should return the users attributes. On failure,
     * it should throw an exception. If the error was caused by the user entering the wrong
     * username or password, a \SimpleSAML\Error\Error(\SimpleSAML\Error\ErrorCodes::WRONGUSERPASS) should be thrown.
     *
     * Note that both the username and the password are UTF-8 encoded.
     *
     * @param string $username  The username the user wrote.
     * @param string $password  The password the user wrote.
     * @return array  Associative array with the users attributes.
     */
    protected function login(
        string $username,
        #[\SensitiveParameter]
        string $password,
    ): array {
        $userpass = $username . ':' . $password;
        if (!array_key_exists($userpass, $this->users)) {
            throw new Error\Error(Error\ErrorCodes::WRONGUSERPASS);
        }

        return $this->users[$userpass];
    }
}
