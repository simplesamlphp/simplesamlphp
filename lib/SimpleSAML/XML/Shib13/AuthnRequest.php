<?php

/**
 * The Shibboleth 1.3 Authentication Request. Not part of SAML 1.1,
 * but an extension using query paramters no XML.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\XML\Shib13;

class AuthnRequest
{
    /** @var string|null */
    private $issuer = null;

    /** @var string|null */
    private $relayState = null;


    /**
     * @param string|null $relayState
     * @return void
     */
    public function setRelayState($relayState)
    {
        $this->relayState = $relayState;
    }
    

    /**
     * @return string|null
     */
    public function getRelayState()
    {
        return $this->relayState;
    }
    

    /**
     * @param string|null $issuer
     * @return void
     */
    public function setIssuer($issuer)
    {
        $this->issuer = $issuer;
    }


    /**
     * @return string|null
     */
    public function getIssuer()
    {
        return $this->issuer;
    }


    /**
     * @param string $destination
     * @param string $shire
     * @return string|null
     */
    public function createRedirect($destination, $shire)
    {
        $metadata = \SimpleSAML\Metadata\MetaDataStorageHandler::getMetadataHandler();
        $idpmetadata = $metadata->getMetaDataConfig($destination, 'shib13-idp-remote');

        $desturl = $idpmetadata->getDefaultEndpoint(
            'SingleSignOnService',
            ['urn:mace:shibboleth:1.0:profiles:AuthnRequest']
        );
        $desturl = $desturl['Location'];

        $target = $this->getRelayState();
        
        $url = $desturl.'?'.
            'providerId='.urlencode($this->getIssuer()).
            '&shire='.urlencode($shire).
            (isset($target) ? '&target='.urlencode($target) : '');
        return $url;
    }
}
