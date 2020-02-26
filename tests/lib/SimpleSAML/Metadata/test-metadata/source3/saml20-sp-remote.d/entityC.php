<?php

$metadata['entityC'] = [
    'entityid' => 'entityC',
    'name' =>
        [
            'en' => 'entityC SP from source3',
        ],
    'metadata-set' => 'saml20-sp-remote',
    'AssertionConsumerService' =>
        [
            0 =>
                [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                    'Location' => 'https://entityC.example.org/Shibboleth.sso/SAML2/POST',
                    'index' => 1,
                    'isDefault' => true,
                ],
        ]
];

