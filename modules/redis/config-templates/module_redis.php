<?php
/**
 * Configuration template for the Redis module for simpleSAMLphp
 */
$config = [
    // Predis client parameters
    'parameters' => 'tcp://localhost:6379',

    // Predis client options
    'options' => null,

    // Old host
    /*
    'oldHost' => [
        // Predis client parameters
        'parameters' => 'tcp://localhost:6379',

        // Predis client options
        'options' => null,
    ],
     */

    // Key prefix
    'prefix' => 'simpleSAMLphp',

    // Lifitime for all non expiring keys
    'lifetime' => 288000
];
