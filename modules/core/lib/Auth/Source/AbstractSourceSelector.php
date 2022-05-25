<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Source;

use Exception;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Session;

/**
 * Authentication source which delegates authentication to secondary
 * authentication sources based on policy decision
 *
 * @package SimpleSAMLphp
 */
abstract class AbstractSourceSelector extends Auth\Source
{
    /**
     * @var array  The names of all the configured auth sources
     */
    protected array $validSources;


    /**
     * Constructor for this authentication source.
     *
     * @param array $info Information about this authentication source.
     * @param array $config Configuration.
     */
    public function __construct(array $info, array $config)
    {
        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        $authsources = Configuration::getConfig('authsources.php');
        $this->validSources = array_keys($authsources->toArray());
    }


    /**
     * Process a request.
     *
     * If an authentication source returns from this function, it is assumed to have
     * authenticated the user, and should have set elements in $state with the attributes
     * of the user.
     *
     * If the authentication process requires additional steps which make it impossible to
     * complete before returning from this function, the authentication source should
     * save the state, and at a later stage, load the state, update it with the authentication
     * information about the user, and call completeAuth with the state array.
     *
     * @param array &$state Information about the current authentication.
     */
    public function authenticate(array &$state): void
    {
        $source = $this->selectAuthSource();
        $as = Auth\Source::getById($source);

        if ($as === null || !in_array($source, $this->validSources, true)) {
            throw new Exception('Invalid authentication source: ' . $source);
        }

        static::doAuthentication($as, $state);
    }


    /**
     * @param \SimpleSAML\Auth\Source $as
     * @param array $state
     * @return void
     */
    public static function doAuthentication(Auth\Source $as, array $state): void
    {
        try {
            $as->authenticate($state);
        } catch (Error\Exception $e) {
            Auth\State::throwException($state, $e);
        } catch (Exception $e) {
            $e = new Error\UnserializableException($e);
            Auth\State::throwException($state, $e);
        }

        Auth\Source::completeAuth($state);
    }


    /**
     * Decide what authsource to use.
     *
     * @param array &$state Information about the current authentication.
     * @return string
     */
    abstract protected function selectAuthSource(): string;
}
