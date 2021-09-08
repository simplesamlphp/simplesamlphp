<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use ReflectionObject;
use SAML2\AuthnRequest;
use SAML2\Binding;
use SAML2\LogoutRequest;
use SimpleSAML\Configuration;
use SimpleSAML\Module\saml\Auth\Source\SP;

/**
 * Wrap the SSP \SimpleSAML\Module\saml\Auth\Source\SP class
 * - Use introspection to make startSSO2Test available
 * - Override sendSAML2AuthnRequest() to catch the AuthnRequest being sent
 */
class SpTester extends SP
{
    /**
     * @param array $info
     * @param array $config
     */
    public function __construct(array $info, array $config)
    {
        parent::__construct($info, $config);
    }


    /**
     */
    public function startSSO2Test(Configuration $idpMetadata, array $state): void
    {
        $reflector = new ReflectionObject($this);
        $method = $reflector->getMethod('startSSO2');
        $method->setAccessible(true);
        $method->invoke($this, $idpMetadata, $state);
    }


    /**
     * override the method that sends the request to avoid sending anything
     */
    public function sendSAML2AuthnRequest(Binding $binding, AuthnRequest $ar): void
    {
        // Exit test. Continuing would mean running into a assert(FALSE)
        throw new ExitTestException(
            [
                'binding' => $binding,
                'ar'      => $ar,
            ]
        );
    }


    /**
     * override the method that sends the request to avoid sending anything
     */
    public function sendSAML2LogoutRequest(Binding $binding, LogoutRequest $lr): void
    {
        // Exit test. Continuing would mean running into a assert(FALSE)
        throw new ExitTestException(
            [
                'binding' => $binding,
                'lr'      => $lr,
            ]
        );
    }
}
