<?php

namespace SimpleSAML\Store;

use \SimpleSAML_Configuration as Configuration;
use \SimpleSAML\Store;

/**
 * A data store using Redis to keep the data.
 *
 * @package SimpleSAMLphp
 */
class Redis extends Store
{
    /**
     * Initialize the Redis data store.
     */
    public function __construct($redis = null)
    {
        assert('is_null($redis) || is_subclass_of($redis, "Predis\\Client")');

        if (!class_exists('\Predis\Client')) {
            throw new \SimpleSAML\Error\CriticalConfigurationError('predis/predis is not available.');
        }

        if (is_null($redis)) {
            $config = Configuration::getInstance();

            $host = $config->getString('store.redis.host', 'localhost');
            $port = $config->getInteger('store.redis.port', 6379);
            $prefix = $config->getString('store.redis.prefix', 'SimpleSAMLphp');

            $redis = new \Predis\Client(
                array(
                    'scheme' => 'tcp',
                    'host' => $host,
                    'post' => $port,
                ),
                array(
                    'prefix' => $prefix,
                )
            );
        }

        $this->redis = $redis;
    }

    /**
     * Deconstruct the Redis data store.
     */
    public function __destruct()
    {
        if (method_exists($this->redis, 'disconnect')) {
            $this->redis->disconnect();
        }
    }

    /**
     * Retrieve a value from the data store.
     *
     * @param string $type The type of the data.
     * @param string $key The key to retrieve.
     *
     * @return mixed|null The value associated with that key, or null if there's no such key.
     */
    public function get($type, $key)
    {
        assert('is_string($type)');
        assert('is_string($key)');

        $result = $this->redis->get("{$type}.{$key}");

        if ($result === false) {
            return null;
        }

        return unserialize($result);
    }

    /**
     * Save a value in the data store.
     *
     * @param string $type The type of the data.
     * @param string $key The key to insert.
     * @param mixed $value The value itself.
     * @param int|null $expire The expiration time (unix timestamp), or null if it never expires.
     */
    public function set($type, $key, $value, $expire = null)
    {
        assert('is_string($type)');
        assert('is_string($key)');
        assert('is_null($expire) || (is_int($expire) && $expire > 2592000)');

        $serialized = serialize($value);

        if (is_null($expire)) {
            $this->redis->set("{$type}.{$key}", $serialized);
        } else {
            $this->redis->setex("{$type}.{$key}", $expire, $serialized);
        }
    }

    /**
     * Delete an entry from the data store.
     *
     * @param string $type The type of the data
     * @param string $key The key to delete.
     */
    public function delete($type, $key)
    {
        assert('is_string($type)');
        assert('is_string($key)');

        $this->redis->del("{$type}.{$key}");
    }
}
