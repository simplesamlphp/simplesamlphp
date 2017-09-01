<?php

class sspmod_adfs_IdP_ADFS
{
    public static function receiveAuthnRequest(SimpleSAML_IdP $idp)
    {
        try {
            parse_str($_SERVER['QUERY_STRING'], $query);

            $requestid = $query['wctx'];
            $issuer = $query['wtrealm'];
            $metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
            $spMetadata = $metadata->getMetaDataConfig($issuer, 'adfs-sp-remote');

            SimpleSAML\Logger::info('ADFS - IdP.prp: Incoming Authentication request: '.$issuer.' id '.$requestid);
        } catch(Exception $exception) {
            throw new SimpleSAML_Error_Error('PROCESSAUTHNREQUEST', $exception);
        }

        $state = array(
            'Responder' => array('sspmod_adfs_IdP_ADFS', 'sendResponse'),
            'SPMetadata' => $spMetadata->toArray(),
            'ForceAuthn' => false,
            'isPassive' => false,
            'adfs:wctx' => $requestid,
        );

        $idp->handleAuthenticationRequest($state);		
    }

    private static function generateResponse($issuer, $target, $nameid, $attributes)
    {
        $issueInstant = SimpleSAML\Utils\Time::generateTimestamp();
        $notBefore = SimpleSAML\Utils\Time::generateTimestamp(time() - 30);
        $assertionExpire = SimpleSAML\Utils\Time::generateTimestamp(time() + 60 * 5);
        $assertionID = SimpleSAML\Utils\Random::generateID();
        $nameidFormat = 'http://schemas.xmlsoap.org/claims/UPN';
        $nameid = htmlspecialchars($nameid);

        $result = <<<MSG
<wst:RequestSecurityTokenResponse xmlns:wst="http://schemas.xmlsoap.org/ws/2005/02/trust">
    <wst:RequestedSecurityToken>
        <saml:Assertion Issuer="$issuer" IssueInstant="$issueInstant" AssertionID="$assertionID" MinorVersion="1" MajorVersion="1" xmlns:saml="urn:oasis:names:tc:SAML:1.0:assertion">
            <saml:Conditions NotOnOrAfter="$assertionExpire" NotBefore="$notBefore">
                <saml:AudienceRestrictionCondition>
                    <saml:Audience>$target</saml:Audience>
                </saml:AudienceRestrictionCondition>
            </saml:Conditions>
            <saml:AuthenticationStatement AuthenticationMethod="urn:oasis:names:tc:SAML:1.0:am:unspecified" AuthenticationInstant="$issueInstant">
                <saml:Subject>
                    <saml:NameIdentifier Format="$nameidFormat">$nameid</saml:NameIdentifier>
                </saml:Subject>
            </saml:AuthenticationStatement>
            <saml:AttributeStatement>
                <saml:Subject>
                    <saml:NameIdentifier Format="$nameidFormat">$nameid</saml:NameIdentifier>
                </saml:Subject>
MSG;

        foreach ($attributes as $name => $values) {
            if ((!is_array($values)) || (count($values) == 0)) {
                continue;
            }

            list($namespace, $name) = SimpleSAML\Utils\Attributes::getAttributeNamespace($name, 'http://schemas.xmlsoap.org/claims');
            foreach ($values as $value) {
                if ((!isset($value)) || ($value === '')) {
                    continue;
                }
                $value = htmlspecialchars($value);

                $result .= <<<MSG
                <saml:Attribute AttributeNamespace="$namespace" AttributeName="$name">
                    <saml:AttributeValue>$value</saml:AttributeValue>
                </saml:Attribute>
MSG;

            }
        }

        $result .= <<<MSG
            </saml:AttributeStatement>
        </saml:Assertion>
   </wst:RequestedSecurityToken>
   <wsp:AppliesTo xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy">
       <wsa:EndpointReference xmlns:wsa="http://schemas.xmlsoap.org/ws/2004/08/addressing">
           <wsa:Address>$target</wsa:Address>
       </wsa:EndpointReference>
   </wsp:AppliesTo>
</wst:RequestSecurityTokenResponse>
MSG;

        return $result;
    }

    private static function signResponse($response, $key, $cert)
    {
        $objXMLSecDSig = new XMLSecurityDSig();
        $objXMLSecDSig->idKeys = array('AssertionID');	
        $objXMLSecDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);	
        $responsedom = \SAML2\DOMDocumentFactory::fromString(str_replace ("\r", "", $response));
        $firstassertionroot = $responsedom->getElementsByTagName('Assertion')->item(0);
        $objXMLSecDSig->addReferenceList(
            array($firstassertionroot), XMLSecurityDSig::SHA1,
            array('http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N),
            array('id_name' => 'AssertionID')
        );
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
        $objKey->loadKey($key, true);
        $objXMLSecDSig->sign($objKey);
        if ($cert) {
            $public_cert = file_get_contents($cert);
            $objXMLSecDSig->add509Cert($public_cert, true);
        }
        $newSig = $responsedom->importNode($objXMLSecDSig->sigNode, true);
        $firstassertionroot->appendChild($newSig);	
        return $responsedom->saveXML();
    }

    private static function postResponse($url, $wresult, $wctx)
    {
        $wresult = htmlspecialchars($wresult);
        $wctx = htmlspecialchars($wctx);

        $post = <<<MSG
    <body onload="document.forms[0].submit()">
        <form method="post" action="$url">
            <input type="hidden" name="wa" value="wsignin1.0">
            <input type="hidden" name="wresult" value="$wresult">
            <input type="hidden" name="wctx" value="$wctx">
            <noscript>
                <input type="submit" value="Continue">
            </noscript>
        </form>
    </body>
MSG;

        echo $post;
        exit;
    }

    public static function sendResponse(array $state)
    {
        $spMetadata = $state["SPMetadata"];
        $spEntityId = $spMetadata['entityid'];
        $spMetadata = SimpleSAML_Configuration::loadFromArray($spMetadata,
            '$metadata[' . var_export($spEntityId, true) . ']');

        $attributes = $state['Attributes'];

        $nameidattribute = $spMetadata->getValue('simplesaml.nameidattribute');
        if (!empty($nameidattribute)) {
            if (!array_key_exists($nameidattribute, $attributes)) {
                throw new Exception('simplesaml.nameidattribute does not exist in resulting attribute set');
            }
            $nameid = $attributes[$nameidattribute][0];
        } else {
            $nameid = SimpleSAML\Utils\Random::generateID();
        }

        $idp = SimpleSAML_IdP::getByState($state);		
        $idpMetadata = $idp->getConfig();
        $idpEntityId = $idpMetadata->getString('entityid');

        $idp->addAssociation(array(
            'id' => 'adfs:' . $spEntityId,
            'Handler' => 'sspmod_adfs_IdP_ADFS',
            'adfs:entityID' => $spEntityId,
        ));

        $response = sspmod_adfs_IdP_ADFS::generateResponse($idpEntityId, $spEntityId, $nameid, $attributes);

        $privateKeyFile = \SimpleSAML\Utils\Config::getCertPath($idpMetadata->getString('privatekey'));
        $certificateFile = \SimpleSAML\Utils\Config::getCertPath($idpMetadata->getString('certificate'));
        $wresult = sspmod_adfs_IdP_ADFS::signResponse($response, $privateKeyFile, $certificateFile);

        $wctx = $state['adfs:wctx'];
        sspmod_adfs_IdP_ADFS::postResponse($spMetadata->getValue('prp'), $wresult, $wctx);
    }

    public static function sendLogoutResponse(SimpleSAML_IdP $idp, array $state)
    {
        // NB:: we don't know from which SP the logout request came from
        $metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
        $idpMetadata = $idp->getConfig();
        \SimpleSAML\Utils\HTTP::redirectTrustedURL($idpMetadata->getValue('redirect-after-logout', \SimpleSAML\Utils\HTTP::getBaseURL()));
    }

    public static function receiveLogoutMessage(SimpleSAML_IdP $idp)
    {
        // if a redirect is to occur based on wreply, we will redirect to url as
        // this implies an override to normal sp notification
        if (isset($_GET['wreply']) && !empty($_GET['wreply'])) {
            $idp->doLogoutRedirect(\SimpleSAML\Utils\HTTP::checkURLAllowed($_GET['wreply']));
            assert('false');
        }

        $state = array(
            'Responder' => array('sspmod_adfs_IdP_ADFS', 'sendLogoutResponse'),
        );
        $assocId = null;
        // TODO: verify that this is really no problem for: 
        //       a) SSP, because there's no caller SP.
        //       b) ADFS SP because caller will be called back..
        $idp->handleLogoutRequest($state, $assocId);
    }

    // accepts an association array, and returns a URL that can be accessed to terminate the association
    public static function getLogoutURL(SimpleSAML_IdP $idp, array $association, $relayState)
    {
        $metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
        $idpMetadata = $idp->getConfig();
        $spMetadata = $metadata->getMetaDataConfig($association['adfs:entityID'], 'adfs-sp-remote');
        $returnTo = SimpleSAML\Module::getModuleURL('adfs/idp/prp.php?assocId=' . urlencode($association["id"]) . '&relayState=' . urlencode($relayState));
        return $spMetadata->getValue('prp') . '?' . 'wa=wsignoutcleanup1.0&wreply=' . urlencode($returnTo);
    }
}
