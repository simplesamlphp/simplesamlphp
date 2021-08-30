<?php

declare(strict_types=1);

namespace SimpleSAML\Store;

use Exception;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module;

/**
 * Base class for data stores.
 *
 * @package simplesamlphp/simplesamlphp
 */
abstract class StoreFactory
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
     * @return \SimpleSAML\Store\StoreInterface|false The data store, or false if it isn't enabled.
     *
     * @throws \SimpleSAML\Error\CriticalConfigurationError
     */
    public static function getInstance(): StoreInterface
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $config = Configuration::getInstance();
        $storeType = $config->getString('store.type', 'phpsession');

        switch ($storeType) {
            case 'phpsession':
                // we cannot support advanced features with the PHP session store
                self::$instance = false;
                break;
            case 'memcache':
                self::$instance = new Store\Memcache();
                break;
            case 'sql':
                self::$instance = new Store\SQL();
                break;
            case 'redis':
                self::$instance = new Store\Redis();
                break;
            default:
                // datastore from module
                try {
                    $className = Module::resolveClass($storeType, 'StoreInterface');
                } catch (Exception $e) {
                    $c = $config->toArray();
                    $c['store.type'] = 'phpsession';
                    throw new Error\CriticalConfigurationError(
                        "Invalid 'store.type' configuration option. Cannot find store '$storeType'.",
                        null,
                        $c
                    );
                }
                /** @var \SimpleSAML\Store|false */
                self::$instance = new $className();
        }

        return self::$instance;
    }
}
