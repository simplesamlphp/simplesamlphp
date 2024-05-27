<?php

declare(strict_types=1);

namespace SimpleSAML\Store;

use Exception;
use SimpleSAML\{Configuration, Error, Module, Utils};

/**
 * Base class for data stores.
 *
 * @package simplesamlphp/simplesamlphp
 */
abstract class StoreFactory implements Utils\ClearableState
{
    /**
     * Our singleton instance.
     *
     * This is false if the data store isn't enabled, and null if we haven't attempted to initialize it.
     *
     * @var \SimpleSAML\Store\StoreInterface|false|null
     */
    private static $instance = null;


    /**
     * Retrieve our singleton instance.
     *
     * @param string $storeType The type of store we need to instantiate
     * @return \SimpleSAML\Store\StoreInterface|false The data store, or false if it isn't enabled.
     *
     * @throws \SimpleSAML\Error\CriticalConfigurationError
     */
    public static function getInstance(string $storeType)
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        switch ($storeType) {
            case 'phpsession':
                // we cannot support advanced features with the PHP session store
                self::$instance = false;
                break;
            case 'memcache':
                self::$instance = new MemcacheStore();
                break;
            case 'sql':
                self::$instance = new SQLStore();
                break;
            case 'redis':
                self::$instance = new RedisStore();
                break;
            default:
                // datastore from module
                try {
                    $className = Module::resolveClass($storeType, 'StoreInterface');
                } catch (Exception $e) {
                    $config = Configuration::getInstance();
                    $c = $config->toArray();
                    $c['store.type'] = 'phpsession';
                    throw new Error\CriticalConfigurationError(
                        "Invalid 'store.type' configuration option. Cannot find store '$storeType'.",
                        null,
                        $c,
                    );
                }
                /** @var \SimpleSAML\Store\StoreInterface|false */
                self::$instance = new $className();
        }

        return self::$instance;
    }


    /**
     * Clear any SSP specific state, such as SSP environmental variables or cached internals.
     */
    public static function clearInternalState(): void
    {
        self::$instance = null;
    }
}
