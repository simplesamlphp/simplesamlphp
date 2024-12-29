<?php 


    // An authentication source which can authenticate against SAML 2.0 IdPs.
$metadata['sp2'] = [
        'saml:SP',

        // The entity ID of this SP.
        'entityID' => 'https://sspapp2.example.org/',

        // The entity ID of the IdP this SP should contact.
        // Can be NULL/unset, in which case the user will be shown a list of available IdPs.
        'idp' => 'urn:x-simplesamlphp:sspsmall-idp',

        // The URL to the discovery service.
        // Can be NULL/unset, in which case a builtin discovery service will be used.
        'discoURL' => null,

        'proxymode.passAuthnContextClassRef' => false,
];
