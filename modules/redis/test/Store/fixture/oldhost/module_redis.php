<?php
$config = [
    // Predis client parameters
    'parameters' => ['key1' => 'value1'],

    // Predis client options
    'options' => ['okey1' => 'ovalue1'],

    // Old host
    'oldHost' => [
        // Predis client parameters
        'parameters' => ['key2' => 'value2'],

        // Predis client options
        'options' => ['okey2' => 'ovalue2'],
    ],

    // Key prefix
    'prefix' => 'unittest',

    // Lifitime for all non expiring keys
    'lifetime' => 288000
];
