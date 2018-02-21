<?php
/**
 * Configuration template for the Redis moduel for simpleSAMLphp
 */
$config = [
    // Predis client parameters
    'parameters' => ['key1' => 'value1'],

    // Predis client options
    'options' => ['okey1' => 'ovalue1'],

    // Key prefix
    'prefix' => 'unittest',

    // Lifitime for all non expiring keys
    'lifetime' => 288000
];
