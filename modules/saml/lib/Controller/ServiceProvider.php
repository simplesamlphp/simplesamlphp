<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Controller;

use Exception;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Http\RunnableResponse;
use SimpleSAML\Module\saml\Auth\Source\SP;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller class for the saml module.
 *
 * This class serves the different views available in the module.
 *
 * @package simplesamlphp/simplesamlphp
 */
class ServiceProvider
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /**
     * @var \SimpleSAML\Auth\State|string
     * @psalm-var \SimpleSAML\Auth\State|class-string
     */
    protected $authState = Auth\State::class;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     */
    public function __construct(
        Configuration $config
    ) {
        $this->config = $config;
    }


    /**
     * Inject the \SimpleSAML\Auth\State dependency.
     *
     * @param \SimpleSAML\Auth\State $authState
     */
    public function setAuthState(Auth\State $authState): void
    {
        $this->authState = $authState;
    }


    /**
     * Handler for response from IdP discovery service.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\Http\RunnableResponse
     */
    public function discoResponse(Request $request): RunnableResponse
    {
        if (!$request->query->has('AuthID')) {
            throw new Error\BadRequest('Missing AuthID to discovery service response handler');
        }
        $authId = $request->query->get('AuthID');

        if (!$request->query->has('idpentityid')) {
            throw new Error\BadRequest('Missing idpentityid to discovery service response handler');
        }
        $idpEntityId = $request->query->get('idpentityid');

        $state = $this->authState::loadState($authId, 'saml:sp:sso');

        // Find authentication source
        Assert::keyExists($state, 'saml:sp:AuthId');
        $sourceId = $state['saml:sp:AuthId'];

        $source = Auth\Source::getById($sourceId);
        if ($source === null) {
            throw new Exception('Could not find authentication source with id ' . $sourceId);
        }

        if (!($source instanceof SP)) {
            throw new Error\Exception('Source type changed?');
        }

        return new RunnableResponse([$source, 'startSSO'], [$idpEntityId, $state]);
    }


    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\XHTML\Template
     */
    public function wrongAuthnContextClassRef(Request $request): Template
    {
        return new Template($this->config, 'saml:sp/wrong_authncontextclassref.twig');
    }
}
