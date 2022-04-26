<?php

declare(strict_types=1);

namespace SimpleSAML\Module\Controller;

use SAML2\Exception\Protocol\UnsupportedBindingException;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\IdP;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Logger;
use SimpleSAML\Metadata;
use SimpleSAML\Module;
use SimpleSAML\Utils;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller class for the Single Logout Profile.
 *
 * This class serves the different views available.
 *
 * @package simplesamlphp/simplesamlphp
 */
class SingleLogout
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
     * This SAML 2.0 endpoint can receive incoming LogoutRequests. It will also send LogoutResponses,
     * and LogoutRequests and also receive LogoutResponses. It is implementing SLO at the SAML 2.0 IdP.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\HTTP\RunnableResponse
     */
    public function singleLogout(Request $request): RunnableResponse
    {
        Logger::info('SAML2.0 - IdP.SingleLogoutService: Accessing SAML 2.0 IdP endpoint SingleLogoutService');

        if (!$this->config->getOptionalBoolean('enable.saml20-idp', false) || !Module::isModuleEnabled('saml')) {
            throw new Error\Error('NOACCESS', null, 403);
        }

        $httpUtils = new Utils\HTTP();
        $metadata = Metadata\MetaDataStorageHandler::getMetadataHandler();
        $idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
        $idp = IdP::getById('saml2:' . $idpEntityId);

        if ($request->request->has('ReturnTo')) {
            return new RunnableResponse(
                [$idp, 'doLogoutRedirect'],
                [$httpUtils->checkURLAllowed($request->request->get('ReturnTo'))]
            );
        } else {
            try {
                return new RunnableResponse([Module\saml\IdP\SAML2::class, 'receiveLogoutMessage'], [$idp]);
            } catch (UnsupportedBindingException $e) {
                throw new Error\Error('SLOSERVICEPARAMS', $e, 400);
            }
        }
        Assert::true(false);
    }


    /**
     * This endpoint will initialize the SLO flow at the SAML 2.0 IdP.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\HTTP\RunnableResponse
     */
    public function initSingleLogout(Request $request): RunnableResponse
    {
        Logger::info('SAML2.0 - IdP.initSLO: Accessing SAML 2.0 IdP endpoint init Single Logout');

        if (!$this->config->getOptionalBoolean('enable.saml20-idp', false) || !Module::isModuleEnabled('saml')) {
            throw new Error\Error('NOACCESS', null, 403);
        }

        $metadata = Metadata\MetaDataStorageHandler::getMetadataHandler();
        $idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
        $idp = IdP::getById('saml2:' . $idpEntityId);

        if (!$request->query->has('RelayState')) {
            throw new Error\Error('NORELAYSTATE');
        }

        $httpUtils = new Utils\HTTP();
        return new RunnableResponse(
            [$idp, 'doLogoutRedirect'],
            [$httpUtils->checkURLAllowed($request->query->get('RelayState')]
        );
    }
}
