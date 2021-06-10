<?php

namespace SimpleSAML\Store;

use \SimpleSAML_Configuration as Configuration;
use \SimpleSAML\Store\Redis;

/**
 * A data store using Redis Sentinel.
 *
 * @package SimpleSAMLphp
 */
class RedisSentinel extends Redis
{
    /**
     * Initialize the RedisSentinel data store.
     */
    public function __construct($redis = null)
    {
        assert('is_null($redis) || is_subclass_of($redis, "Predis\\Client")');

        if (!class_exists('\Predis\Client')) {
            throw new \SimpleSAML\Error\CriticalConfigurationError('predis/predis is not available.');
        }

        if (is_null($redis)) {
            $config = Configuration::getInstance();

            $sentinels = $config->getArray('store.redissentinel.sentinels', array());
            $mastergroup = $config->getString('store.redissentinel.mastergroup', 'mymaster');
            $prefix = $config->getString('store.redissentinel.prefix', 'SimpleSAMLphp');

            $redis = new \Predis\Client(
                $sentinels,
                array(
                    'replication' => 'sentinel',
                    'service' => $mastergroup,
                    'prefix' => $prefix,
                )
            );
        }

        $this->redis = $redis;
    }
}
