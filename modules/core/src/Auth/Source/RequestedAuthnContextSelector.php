<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Source;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\SAML2\Exception\Protocol\NoAuthnContextException;

use function array_key_exists;
use function is_array;
use function sprintf;

/**
 * Authentication source which delegates authentication to secondary
 * authentication sources based on the RequestedAuthnContext
 *
 * @package simplesamlphp/simplesamlphp
 */
class RequestedAuthnContextSelector extends AbstractSourceSelector
{
    /**
     * The key of the AuthId field in the state.
     */
    public const AUTHID = '\SimpleSAML\Module\core\Auth\Source\RequestedAuthnContextSelector.AuthId';

    /**
     * The string used to identify our states.
     */
    public const STAGEID = '\SimpleSAML\Module\core\Auth\Source\RequestedAuthnContextSelector.StageId';

    /**
     * The key where the sources is saved in the state.
     */
    public const SOURCESID = '\SimpleSAML\Module\core\Auth\Source\RequestedAuthnContextSelector.SourceId';


    /**
     * @var string  The default authentication source to use when no RequestedAuthnContext is passed
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected string $defaultSource;

    /**
     * @var array<int, array>  An array of AuthnContexts, indexed by a numeric key.
     *   Each entry is in the format of:
     *   `loa` => [`identifier` => 'identifier', `source` => 'source']
     *
     *   i.e.:
     *
     *   10 => [
     *       'identifier' => 'urn:x-simplesamlphp:loa1',
     *       'source' => 'exampleauth',
     *   ],
     *
     *   20 => [
     *       'identifier' => 'urn:x-simplesamlphp:loa2',
     *       'source' => 'exampleauth-mfa',
     *   ]
     */
    protected array $contexts = [];


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

        Assert::keyExists($config, 'contexts');
        Assert::keyExists($config['contexts'], 'default');

        if (!is_array($config['contexts']['default'])) {
            Assert::stringNotEmpty($config['contexts']['default']);
            $this->defaultSource = $config['contexts']['default'];
            unset($config['contexts']['default']);
        }

        foreach ($config['contexts'] as $key => $context) {
            ($key !== 'default') && Assert::natural($key);

            if (!array_key_exists('identifier', $context)) {
                throw new Exception(sprintf("Incomplete context '%d' due to missing `identifier` key.", $key));
            } elseif (!array_key_exists('source', $context)) {
                throw new Exception(sprintf("Incomplete context '%d' due to missing `source` key.", $key));
            }

            Assert::stringNotEmpty($context['identifier']);
            Assert::stringNotEmpty($context['source']);
            $this->contexts[$key] = $context;
        }
    }


    /**
     * Decide what authsource to use.
     *
     * @param array &$state Information about the current authentication.
     * @return string
     */
    protected function selectAuthSource(array &$state): string
    {
        $requestedContexts = $state['saml:RequestedAuthnContext'] ?? null;
        if (
            $requestedContexts === null
            || !array_key_exists('AuthnContextClassRef', $requestedContexts)
            || $requestedContexts['AuthnContextClassRef'] === null
        ) {
            Logger::info(
                "core:RequestedAuthnContextSelector:  no RequestedAuthnContext provided; selecting default authsource",
            );

            if (array_key_exists('default', $this->contexts)) {
                $state['saml:AuthnContextClassRef'] = $this->contexts['default']['identifier'];
                return $this->contexts['default']['source'];
            }

            return $this->defaultSource;
        }

        Assert::isArray($requestedContexts['AuthnContextClassRef']);
        $comparison = $requestedContexts['Comparison'] ?? 'exact';
        Assert::oneOf($comparison, ['exact', 'minimum', 'maximum', 'better']);

        /**
         * The set of supplied references MUST be evaluated as an ordered set, where the first element
         * is the most preferred authentication context class or declaration.
         */
        foreach ($requestedContexts['AuthnContextClassRef'] as $requestedContext) {
            switch ($comparison) {
                case 'exact':
                    foreach ($this->contexts as $index => $context) {
                        if ($context['identifier'] === $requestedContext) {
                            $state['saml:AuthnContextClassRef'] = $context['identifier'];
                            return $context['source'];
                        }
                    }
                    break 1;
                case 'minimum':
                case 'maximum':
                case 'better':
                    // Not implemented
                    throw new Exception('Not implemented.');
            }
        }

        throw new NoAuthnContextException();
    }
}
