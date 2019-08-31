<?php

namespace SimpleSAML\Module\saml\IdP;

use SimpleSAML\Bindings\Shib13\HTTPPost;
use SimpleSAML\Utils\Config\Metadata;
use SimpleSAML\Utils\Crypto;
use SimpleSAML\Utils\HTTP;

/**
 * IdP implementation for SAML 1.1 protocol.
 *
 * @package SimpleSAMLphp
 */

class SAML1
{

    /**
     * Retrieve the metadata of a hosted SAML 1.1 IdP.
     *
     * @param string $entityid The entity ID of the hosted SAML 1.1 IdP whose metadata we want.
     *
     * @return array
     * @throws \SimpleSAML\Error\Exception
     * @throws \SimpleSAML\Error\MetadataNotFound
     * @throws \SimpleSAML_Error_Exception
     */
    public static function getHostedMetadata($entityid)
    {
        $handler = \SimpleSAML\Metadata\MetaDataStorageHandler::getMetadataHandler();
        $config = $handler->getMetaDataConfig($entityid, 'shib13-idp-hosted');

        $metadata = [
            'metadata-set' => 'shib13-idp-hosted',
            'entityid' => $entityid,
            'SignleSignOnService' => $handler->getGenerated('SingleSignOnService', 'shib13-idp-hosted'),
            'NameIDFormat' => $config->getArrayizeString('NameIDFormat', 'urn:mace:shibboleth:1.0:nameIdentifier'),
            'contacts' => [],
        ];

        // add certificates
        $keys = [];
        $certInfo = Crypto::loadPublicKey($config, false, 'new_');
        $hasNewCert = false;
        if ($certInfo !== null) {
            $keys[] = [
                'type' => 'X509Certificate',
                'signing' => true,
                'encryption' => true,
                'X509Certificate' => $certInfo['certData'],
                'prefix' => 'new_',
            ];
            $hasNewCert = true;
        }

        $certInfo = Crypto::loadPublicKey($config, true);
        $keys[] = [
            'type' => 'X509Certificate',
            'signing' => true,
            'encryption' => $hasNewCert === false,
            'X509Certificate' => $certInfo['certData'],
            'prefix' => '',
        ];
        $metadata['keys'] = $keys;

        // add organization information
        if ($config->hasValue('OrganizationName')) {
            $metadata['OrganizationName'] = $config->getLocalizedString('OrganizationName');
            $metadata['OrganizationDisplayName'] = $config->getLocalizedString(
                'OrganizationDisplayName',
                $metadata['OrganizationName']
            );

            if (!$config->hasValue('OrganizationURL')) {
                throw new \SimpleSAMl\Error\Exception('If OrganizationName is set, OrganizationURL must also be set.');
            }
            $metadata['OrganizationURL'] = $config->getLocalizedString('OrganizationURL');
        }

        // add scope
        if ($config->hasValue('scope')) {
            $metadata['scope'] = $config->getArray('scope');
        }

        // add extensions
        if ($config->hasValue('EntityAttributes')) {
            $metadata['EntityAttributes'] = $config->getArray('EntityAttributes');

            // check for entity categories
            if (Metadata::isHiddenFromDiscovery($metadata)) {
                $metadata['hide.from.discovery'] = true;
            }
        }

        if ($config->hasValue('UIInfo')) {
            $metadata['UIInfo'] = $config->getArray('UIInfo');
        }

        if ($config->hasValue('DiscoHints')) {
            $metadata['DiscoHints'] = $config->getArray('DiscoHints');
        }

        if ($config->hasValue('RegistrationInfo')) {
            $metadata['RegistrationInfo'] = $config->getArray('RegistrationInfo');
        }

        // add contact information
        $globalConfig = \SimpleSAML\Configuration::getInstance();
        $email = $globalConfig->getString('technicalcontact_email', false);
        if ($email && $email !== 'na@example.org') {
            $contact = [
                'emailAddress' => $email,
                'name' => $globalConfig->getString('technicalcontact_name', null),
                'contactType' => 'technical',
            ];
            $metadata['contacts'][] = Metadata::getContact($contact);
        }

        return $metadata;
    }


    /**
     * Send a response to the SP.
     *
     * @param array $state  The authentication state.
     */
    public static function sendResponse(array $state)
    {
        assert(isset($state['Attributes']));
        assert(isset($state['SPMetadata']));
        assert(isset($state['saml:shire']));
        assert(array_key_exists('saml:target', $state)); // Can be NULL

        $spMetadata = $state["SPMetadata"];
        $spEntityId = $spMetadata['entityid'];
        $spMetadata = \SimpleSAML\Configuration::loadFromArray(
            $spMetadata,
            '$metadata['.var_export($spEntityId, true).']'
        );

        \SimpleSAML\Logger::info('Sending SAML 1.1 Response to '.var_export($spEntityId, true));

        $attributes = $state['Attributes'];
        $shire = $state['saml:shire'];
        $target = $state['saml:target'];

        $idp = \SimpleSAML\IdP::getByState($state);

        $idpMetadata = $idp->getConfig();

        $config = \SimpleSAML\Configuration::getInstance();
        $metadata = \SimpleSAML\Metadata\MetaDataStorageHandler::getMetadataHandler();

        $statsData = [
            'spEntityID' => $spEntityId,
            'idpEntityID' => $idpMetadata->getString('entityid'),
            'protocol' => 'saml1',
        ];
        if (isset($state['saml:AuthnRequestReceivedAt'])) {
            $statsData['logintime'] = microtime(true) - $state['saml:AuthnRequestReceivedAt'];
        }
        \SimpleSAML\Stats::log('saml:idp:Response', $statsData);

        // Generate and send response.
        $ar = new \SimpleSAML\XML\Shib13\AuthnResponse();
        $authnResponseXML = $ar->generate($idpMetadata, $spMetadata, $shire, $attributes);

        $httppost = new HTTPPost($config, $metadata);
        $httppost->sendResponse($authnResponseXML, $idpMetadata, $spMetadata, $target, $shire);
    }


    /**
     * Receive an authentication request.
     *
     * @param \SimpleSAML\IdP $idp  The IdP we are receiving it for.
     */
    public static function receiveAuthnRequest(\SimpleSAML\IdP $idp)
    {
        if (isset($_REQUEST['cookieTime'])) {
            $cookieTime = (int) $_REQUEST['cookieTime'];
            if ($cookieTime + 5 > time()) {
                /*
                 * Less than five seconds has passed since we were
                 * here the last time. Cookies are probably disabled.
                 */
                HTTP::checkSessionCookie(HTTP::getSelfURL());
            }
        }

        if (!isset($_REQUEST['providerId'])) {
            throw new \SimpleSAML\Error\BadRequest('Missing providerId parameter.');
        }
        $spEntityId = (string) $_REQUEST['providerId'];

        if (!isset($_REQUEST['shire'])) {
            throw new \SimpleSAML\Error\BadRequest('Missing shire parameter.');
        }
        $shire = (string) $_REQUEST['shire'];

        if (isset($_REQUEST['target'])) {
            $target = $_REQUEST['target'];
        } else {
            $target = null;
        }

        \SimpleSAML\Logger::info(
            'Shib1.3 - IdP.SSOService: Got incoming Shib authnRequest from '.var_export($spEntityId, true).'.'
        );

        $metadata = \SimpleSAML\Metadata\MetaDataStorageHandler::getMetadataHandler();
        $spMetadata = $metadata->getMetaDataConfig($spEntityId, 'shib13-sp-remote');

        $found = false;
        foreach ($spMetadata->getEndpoints('AssertionConsumerService') as $ep) {
            if ($ep['Binding'] !== 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post') {
                continue;
            }
            if ($ep['Location'] !== $shire) {
                continue;
            }
            $found = true;
            break;
        }
        if (!$found) {
            throw new \Exception(
                'Invalid AssertionConsumerService for SP '.var_export($spEntityId, true).': '.var_export($shire, true)
            );
        }

        \SimpleSAML\Stats::log(
            'saml:idp:AuthnRequest',
            [
                'spEntityID' => $spEntityId,
                'protocol' => 'saml1',
            ]
        );

        $sessionLostURL = HTTP::addURLParameters(
            HTTP::getSelfURL(),
            ['cookieTime' => time()]
        );

        $state = [
            'Responder' => ['\SimpleSAML\Module\saml\IdP\SAML1', 'sendResponse'],
            'SPMetadata' => $spMetadata->toArray(),
            \SimpleSAML\Auth\State::RESTART => $sessionLostURL,
            'saml:shire' => $shire,
            'saml:target' => $target,
            'saml:AuthnRequestReceivedAt' => microtime(true),
        ];

        $idp->handleAuthenticationRequest($state);
    }
}
