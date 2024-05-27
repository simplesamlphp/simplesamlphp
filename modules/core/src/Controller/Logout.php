<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Controller;

use Exception as BuiltinException;
use SimpleSAML\{Auth, Configuration, Error, IdP, Logger, Stats, Utils};
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\saml\Message;
use SimpleSAML\SAML2\Binding;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\XHTML\Template;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\{Request, Response};

use function call_user_func;
use function in_array;
use function method_exists;
use function sha1;
use function substr;
use function time;
use function urldecode;
use function var_export;

/**
 * Controller class for the core module.
 *
 * This class serves the different views available in the module.
 *
 * @package simplesamlphp/simplesamlphp
 */
class Logout
{
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
        protected Configuration $config,
    ) {
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
     * Log the user out of a given authentication source.
     *
     * @param Request $request The request that lead to this logout operation.
     * @param string $as The name of the auth source.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \SimpleSAML\Error\CriticalConfigurationError
     */
    public function logout(Request $request, string $as): Response
    {
        $auth = new Auth\Simple($as);
        $returnTo = $this->getReturnPath($request);
        return $auth->logout($returnTo);
    }


    /**
     * Searches for a valid and allowed ReturnTo URL parameter,
     * otherwise give the base installation page as a return point.
     */
    private function getReturnPath(Request $request): string
    {
        $httpUtils = new Utils\HTTP();

        $returnTo = $request->query->get('ReturnTo', false);
        if ($returnTo !== false) {
            $returnTo = $httpUtils->checkURLAllowed($returnTo);
        }

        if (empty($returnTo)) {
            return $this->config->getBasePath();
        }

        return $returnTo;
    }


    /**
     * @param Request $request The request that lead to this logout operation.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logoutIframeDone(Request $request): Response
    {
        if (!$request->query->has('id')) {
            throw new Error\BadRequest('Missing required parameter: id');
        }
        $id = $request->query->get('id');

        $state = $this->authState::loadState($id, 'core:Logout-IFrame');
        $idp = IdP::getByState($this->config, $state);

        $associations = $idp->getAssociations();

        if (!$request->query->has('cancel')) {
            Logger::stats('slo-iframe done');
            Stats::log('core:idp:logout-iframe:page', ['type' => 'done']);
            $SPs = $state['core:Logout-IFrame:Associations'];
        } else {
            // user skipped global logout
            Logger::stats('slo-iframe skip');
            Stats::log('core:idp:logout-iframe:page', ['type' => 'skip']);
            $SPs = []; // no SPs should have been logged out
            $state['core:Failed'] = true; // mark as partial logout
        }

        // find the status of all SPs
        foreach ($SPs as $assocId => &$sp) {
            $spId = 'logout-iframe-' . sha1($assocId);

            if ($request->query->has($spId)) {
                $spStatus = $request->query->get($spId);
                if ($spStatus === 'completed' || $spStatus === 'failed') {
                    $sp['core:Logout-IFrame:State'] = $spStatus;
                }
            }

            if (!isset($associations[$assocId])) {
                $sp['core:Logout-IFrame:State'] = 'completed';
            }
        }


        // terminate the associations
        foreach ($SPs as $assocId => $sp) {
            if ($sp['core:Logout-IFrame:State'] === 'completed') {
                $idp->terminateAssociation($assocId);
            } else {
                Logger::warning('Unable to terminate association with ' . var_export($assocId, true) . '.');
                if (isset($sp['saml:entityID'])) {
                    $spId = $sp['saml:entityID'];
                } else {
                    $spId = $assocId;
                }
                Logger::stats('slo-iframe-fail ' . $spId);
                Stats::log('core:idp:logout-iframe:spfail', ['sp' => $spId]);
                $state['core:Failed'] = true;
            }
        }

        // we are done
        return $idp->finishLogout($state);
    }


    /**
     * @param \Symfony\Component\HttpFoundation\Request $request The request that lead to this logout operation.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logoutIframePost(Request $request): Response
    {
        if (!$request->query->has('idp')) {
            throw new Error\BadRequest('Missing required parameter: idp');
        }

        $idp = IdP::getById($this->config, $request->query->get('idp'));

        if (!$request->query->has('association')) {
            throw new Error\BadRequest('Missing required parameter: association');
        }
        $assocId = urldecode($request->query->get('association'));

        $relayState = null;
        if ($request->query->has('RelayState')) {
            $relayState = $request->query->get('RelayState');
        }

        $associations = $idp->getAssociations();
        if (!isset($associations[$assocId])) {
            throw new Error\BadRequest('Invalid association id.');
        }
        $association = $associations[$assocId];

        $metadata = MetaDataStorageHandler::getMetadataHandler($this->config);
        $idpMetadata = $idp->getConfig();
        $spMetadata = $metadata->getMetaDataConfig($association['saml:entityID'], 'saml20-sp-remote');

        $lr = Message::buildLogoutRequest($idpMetadata, $spMetadata);
        $lr->setSessionIndex($association['saml:SessionIndex']);
        $lr->setNameId($association['saml:NameID']);

        $assertionLifetime = $spMetadata->getOptionalInteger('assertion.lifetime', null);
        if ($assertionLifetime === null) {
            $assertionLifetime = $idpMetadata->getOptionalInteger('assertion.lifetime', 300);
        }
        $lr->setNotOnOrAfter(time() + $assertionLifetime);

        $encryptNameId = $spMetadata->getOptionalBoolean('nameid.encryption', null);
        if ($encryptNameId === null) {
            $encryptNameId = $idpMetadata->getOptionalBoolean('nameid.encryption', false);
        }
        if ($encryptNameId) {
            $lr->encryptNameId(Message::getEncryptionKey($spMetadata));
        }

        Stats::log('saml:idp:LogoutRequest:sent', [
            'spEntityID'  => $association['saml:entityID'],
            'idpEntityID' => $idpMetadata->getString('entityid'),
        ]);

        $bindings = [C::BINDING_HTTP_POST];

        $dst = $spMetadata->getDefaultEndpoint('SingleLogoutService', $bindings);
        $binding = Binding::getBinding($dst['Binding']);
        $lr->setDestination($dst['Location']);
        $lr->setRelayState($relayState);

        $psrResponse = $binding->send($lr);
        $httpFoundationFactory = new HttpFoundationFactory();
        return $httpFoundationFactory->createResponse($psrResponse);
    }


    /**
     * @param Request $request The request that lead to this logout operation.
     * @return \SimpleSAML\XHTML\Template
     */
    public function logoutIframe(Request $request): Template
    {
        if (!$request->query->has('id')) {
            throw new Error\BadRequest('Missing required parameter: id');
        }
        $id = $request->query->get('id');

        $type = 'init';
        if ($request->query->has('type')) {
            $type = $request->query->get('type');
            if (!in_array($type, ['init', 'js', 'nojs', 'embed'], true)) {
                throw new Error\BadRequest('Invalid value for type.');
            }
        }

        if ($type !== 'embed') {
            Logger::stats('slo-iframe ' . $type);
            Stats::log('core:idp:logout-iframe:page', ['type' => $type]);
        }

        $state = $this->authState::loadState($id, 'core:Logout-IFrame');
        $idp = IdP::getByState($this->config, $state);
        $mdh = MetaDataStorageHandler::getMetadataHandler($this->config);

        if ($type !== 'init') {
            // update association state
            foreach ($state['core:Logout-IFrame:Associations'] as $assocId => &$sp) {
                $spId = sha1($assocId);

                // move SPs from 'onhold' to 'inprogress'
                if ($sp['core:Logout-IFrame:State'] === 'onhold') {
                    $sp['core:Logout-IFrame:State'] = 'inprogress';
                }

                // check for update through request
                if ($request->query->has($spId)) {
                    $s = $request->query->get($spId);
                    if ($s == 'completed' || $s == 'failed') {
                        $sp['core:Logout-IFrame:State'] = $s;
                    }
                }

                // check for timeout
                if (isset($sp['core:Logout-IFrame:Timeout']) && $sp['core:Logout-IFrame:Timeout'] < time()) {
                    if ($sp['core:Logout-IFrame:State'] === 'inprogress') {
                        $sp['core:Logout-IFrame:State'] = 'failed';
                    }
                }

                // update the IdP
                if ($sp['core:Logout-IFrame:State'] === 'completed') {
                    $idp->terminateAssociation($assocId);
                }

                if (!isset($sp['core:Logout-IFrame:Timeout'])) {
                    if (method_exists($sp['Handler'], 'getAssociationConfig')) {
                        $assocIdP = IdP::getByState($this->config, $sp);
                        $assocConfig = call_user_func([$sp['Handler'], 'getAssociationConfig'], $assocIdP, $sp);
                        $timeout = $assocConfig->getOptionalInteger('core:logout-timeout', 5);
                        $sp['core:Logout-IFrame:Timeout'] = $timeout + time();
                    } else {
                        $sp['core:Logout-IFrame:Timeout'] = time() + 5;
                    }
                }
            }
        }

        $associations = $idp->getAssociations();
        foreach ($state['core:Logout-IFrame:Associations'] as $assocId => &$sp) {
            // in case we are refreshing a page
            if (!isset($associations[$assocId])) {
                $sp['core:Logout-IFrame:State'] = 'completed';
            }

            try {
                $assocIdP = IdP::getByState($this->config, $sp);
                $url = call_user_func([$sp['Handler'], 'getLogoutURL'], $assocIdP, $sp, null);
                $sp['core:Logout-IFrame:URL'] = $url;
            } catch (BuiltinException $e) {
                $sp['core:Logout-IFrame:State'] = 'failed';
            }
        }

        // get the metadata of the service that initiated logout, if any
        $terminated = null;
        if ($state['core:TerminatedAssocId'] !== null) {
            $mdset = 'saml20-sp-remote';

            if (substr($state['core:TerminatedAssocId'], 0, 4) === 'adfs') {
                $mdset = 'adfs-sp-remote';
            }

            $terminated = $mdh->getMetaDataConfig($state['saml:SPEntityId'], $mdset)->toArray();
        }

        // build an array with information about all services currently logged in
        $remaining = [];
        foreach ($state['core:Logout-IFrame:Associations'] as $association) {
            $key = sha1($association['id']);
            $mdset = 'saml20-sp-remote';

            if (substr($association['id'], 0, 4) === 'adfs') {
                $mdset = 'adfs-sp-remote';
            }

            if ($association['core:Logout-IFrame:State'] === 'completed') {
                continue;
            }

            $remaining[$key] = [
                'id' => $association['id'],
                'expires_on' => $association['Expires'],
                'entityID' => $association['saml:entityID'],
                'subject' => $association['saml:NameID'],
                'status' => $association['core:Logout-IFrame:State'],
                'metadata' => $mdh->getMetaDataConfig($association['saml:entityID'], $mdset)->toArray(),
            ];

            if (isset($association['core:Logout-IFrame:URL'])) {
                $remaining[$key]['logoutURL'] = $association['core:Logout-IFrame:URL'];
            }

            if (isset($association['core:Logout-IFrame:Timeout'])) {
                $remaining[$key]['timeout'] = $association['core:Logout-IFrame:Timeout'];
            }
        }

        if ($type === 'nojs') {
            $t = new Template($this->config, 'core:logout-iframe-wrapper.twig');
        } else {
            $t = new Template($this->config, 'core:logout-iframe.twig');
        }

        $t->data['auth_state'] = Auth\State::saveState($state, 'core:Logout-IFrame');
        $t->data['type'] = $type;
        $t->data['terminated_service'] = $terminated;
        $t->data['remaining_services'] = $remaining;

        return $t;
    }


    /**
     * @param Request $request The request that lead to this logout operation.
     */
    public function resumeLogout(Request $request): Response
    {
        if (!$request->query->has('id')) {
            throw new Error\BadRequest('Missing required parameter: id');
        }
        $id = $request->query->get('id');

        $state = $this->authState::loadState($id, 'core:Logout:afterbridge');
        $idp = IdP::getByState($this->config, $state);

        $assocId = $state['core:TerminatedAssocId'];
        $logoutHandler = $idp->getLogoutHandler();
        return $logoutHandler->startLogout($state, $assocId);
    }
}
