<?php 


// A hosted SP
$metadata['https://sspapp2.example.org/'] = [

    // Simple name used in UI
    'authid' => 'sp2',

    // The entity ID of the IdP this SP should contact.
    // Can be NULL/unset, in which case the user will be shown a list of available IdPs.
    'idp' => 'urn:x-simplesamlphp:sspsmall-idp',
    
];
