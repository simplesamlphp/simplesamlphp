# Redis module for simpleSAMLphp
## Introduction
The Redis module implements the simpleSAMLphp data store API, so Redis can be
used for backend storage, i.e. session storage.

## Prerequisites
This module requires the following
* simpleSAMLphp v. 1.14.11 (Works with older version, but you should update)
* Redis server, See https://redis.io/ for more information

## Installation
First thing to do is to set up your Redis server(s). This is out side the scope
of this documentation.

Next you must install this module either by either obtaining the tarball or by
installing it via composer. The latter is recommended

    composer.phar require colourbox/simplesamlphp-module-redis

This will automatically install "predis/predis" as a dependency for ther module.
If you downloaded the module yourself, remember to add predis/predis as a
dependency in your composer.json.

See https://github.com/simplesamlphp/composer-module-installer for more
information on how to install simpleSAMLphp modules via composer.

You can now enable the module by

    touch /var/simplesamlphp/modules/redis/enable

Create `/var/simplesamlphp/config/module_redis.php` and set appropriate options
for your Redis server. A configuration file template can be found in the

Redis is used as session store for simpleSAMLphp by setting the following
options in config.php

    'store.type' => 'redis:Redis'

## Configuration options
* `parameters` Connection parameters for the underlying predis client. See 
[connection parameters](https://github.com/nrk/predis/wiki/Connection-Parameters) for details
* `options` Client options for the underlying predis client. See [options](https://github.com/nrk/predis/wiki/Client-Options) for details
* `prefix` Key prefix for all keys stored in Redis
* `lifetime` Default lifetime for non-expiring keys in Redis
* `oldHost` configuration for old Redis host when doing rollover
  * `parameters` Connection parameters for the underlying predis client
  * `options` Client options for the underlying predis client

### Example
```
$config = [
    // Predis client parameters
    'parameters' => 'tcp://localhost:6379',

    // Predis client options
    'options' => null,

    // Key prefix
    'prefix' => 'simpleSAMLphp',

    // Lifitime for all non expiring keys
    'lifetime' => 288000
];
```

## Rollover to new server
The module has build in support for doing rolling update to a new Redis host.
All writes are only done to the new host, but all reads will fall back to the old host if
the value is not found on new host.

### How-to
* Start new Redis host
* Add new host to config file (`parameters` and `options`) and add the old host to `oldHost` option
* Wait until max session lifetime have expired
* Remove `oldHost` config
* Shut down old Redis host

### Configuration example
```
$config = [
    // Predis client parameters
    'parameters' => 'tcp://newhost:6379',

    // Predis client options
    'options' => null,

    // Old host
    'oldHost' => [
        // Predis client parameters
        'parameters' => 'tcp://oldhost:6379',

        // Predis client options
        'options' => null,
    ],

    // Key prefix
    'prefix' => 'simpleSAMLphp',

    // Lifitime for all non expiring keys
    'lifetime' => 288000
];
```
