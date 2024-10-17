<?php

declare(strict_types=1);

/*
 * Source of multiple hosted IdPs. Can be used to test scenarios of having multiple IdPs defined.
 */

$metadata['urn:x-simplesamlphp:example-idp-1'] = [
    'host' => '__DEFAULT__',
    'auth' => 'example-userpass',
];

$metadata['urn:x-simplesamlphp:example-idp-2'] = [
    'host' => 'idp.example.org',
    'auth' => 'example-userpass',
    'SingleSignOnService' => 'https://idp.example.org/ssos',
    'SingleLogoutService' => 'https://idp.example.org/slos',
];
