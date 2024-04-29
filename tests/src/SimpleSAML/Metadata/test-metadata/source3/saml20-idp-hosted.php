<?php

declare(strict_types=1);

/*
 * This set uses the example entityID from metadata-templates
 * and is deliberately expected to generate an exception
 * when loaded. It shouldn't be used in other tests.
 */

$metadata['urn:x-simplesamlphp:example-idp'] = [
    'entityID' => 'urn:x-simplesamlphp:example-idp',
    'auth' => 'phpunit',
];
