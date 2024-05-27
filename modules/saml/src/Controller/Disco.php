<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Controller;

use SimpleSAML\Configuration;
use SimpleSAML\XHTML\IdPDisco;
use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Controller class for the saml module.
 *
 * This class serves the different views available in the module.
 *
 * @package simplesamlphp/simplesamlphp
 */
class Disco
{
    /**
     * Controller constructor.
     *
     * It initializes the global configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     */
    public function __construct(
        protected Configuration $config,
    ) {
    }


    /**
     * Built-in IdP discovery service
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function disco(Request $request): Response
    {
        $disco = new IdPDisco($request, ['saml20-idp-remote'], 'saml');
        return $disco->handleRequest();
    }
}
