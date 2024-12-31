<?php 


// A hosted SP
$metadata['urn:x-simplesamlphp:sp2'] = [

    // Simple name used in UI
    'authid' => 'sp2',

    'idp' => null,
    'discoURL' => null,
    'proxymode.passAuthnContextClassRef' => false,
    'ForceAuthn' => true,
    'certificate' => 'nothing',
    'privatekey' => 'example.key',
    'privatekey_pass' => 'secretpassword',        
    'description' => [
        'en' => 'A service',
        'no' => 'En tjeneste',
    ],        
    'contacts' => [
        [
            'contactType'       => 'support',
            'emailAddress'      => 'support@example.org',
            'givenName'         => 'John',
            'surName'           => 'Doe',
            'telephoneNumber'   => '+31(0)12345678',
            'company'           => 'Example Inc.',
        ],
    ],
    'name' => [
        'en' => 'A service name',
        'no' => 'En tjeneste name',
    ],
    'signature.algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512',
];
