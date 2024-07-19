<?php

declare(strict_types=1);

namespace SimpleSAML\Module\testsauthsource\Auth\Source;

use SimpleSAML\Error;
use SimpleSAML\Error\ErrorCodes;
use SimpleSAML\Module\core\Auth\UserPassBase;
use SimpleSAML\Module\testsauthsource\Auth\Source\CustomError;



/**
 * testing authentication source - can throw a variety of custom error codes
 * or a normal error code. This range is useful for the test suite and can
 * be extended with the errorType configuration value.
 *
 * @package SimpleSAMLphp
 */

class ThrowCustomErrorCode extends UserPassBase
{
    private string $errorType = '';

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

        if (isset($config['errorType'])) {
            $this->errorType = $config['errorType'];
        }
    }


    /**
     * The login will always fail with an error of some kind
     *
     * @param string $username  The username the user wrote.
     * @param string $password  The password the user wrote.
     * @return array  Associative array with the users attributes.
     */
    protected function login(string $username, string $password): array
    {
        /**
         * The ErrorCodes subclass is intentionally placed here as
         * an annonymous class. This is to test that any lookup of
         * the classname during redirections will still work and
         * find this class. Tests can sesarch for a title or description
         * on the login page to see if the error is presenting as expected.
         */
        $customErrorCodes = new class extends ErrorCodes
        {
            public const BIND_SEARCH_CONNECT_ERROR = 'BIND_SEARCH_CONNECT_ERROR';
            public static string $customTitle = 'ThrowCustomErrorCode: title for bind search error';
            public static string $customDescription = 'ThrowCustomErrorCode: description for bind search error';

            public static function getCustomErrorCodeTitles(): array
            {
                return [self::BIND_SEARCH_CONNECT_ERROR => self::$customTitle];
            }
            public static function getCustomErrorCodeDescriptions(): array
            {
                return [self::BIND_SEARCH_CONNECT_ERROR => self::$customDescription];
            }
        };

        if ($this->errorType == 'NORMAL') {
            throw new Error\Error(Error\ErrorCodes::WRONGUSERPASS);
        } else {
            throw new CustomError($customErrorCodes::BIND_SEARCH_CONNECT_ERROR, null, null, $customErrorCodes);
        }
    }
}
