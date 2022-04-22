<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Controller;

use SimpleSAML\Configuration;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\XHTML\IdPDisco;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller class for the saml module.
 *
 * This class serves the different views available in the module.
 *
 * @package simplesamlphp/simplesamlphp
 */
class Disco
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;


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
     * Built-in IdP discovery service
     *
     * @return \SimpleSAML\Http\RunnableResponse
     */
    public function disco(): RunnableResponse
    {
        $disco = new IdPDisco(['saml20-idp-remote'], 'saml');
        return new RunnableResponse([$disco, 'handleRequest']);
    }
}
