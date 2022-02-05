<?php

declare(strict_types=1);

namespace SimpleSAML\Store;

use Predis\Client;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Error;

/**
 * A data store using Redis to keep the data.
 *
 * @package simplesamlphp/simplesamlphp
 */
class RedisStore implements StoreInterface
{
    /** @var \Predis\Client */
    public Client $redis;


    /**
     * Initialize the Redis data store.
     * @param \Predis\Client|null $redis
     */
    public function __construct(Client $redis = null)
    {
        if (!class_exists(Client::class)) {
            throw new Error\CriticalConfigurationError('predis/predis is not available.');
        }

        Assert::nullOrIsInstanceOf($redis, Client::class);

        if ($redis === null) {
            $config = Configuration::getInstance();

            $host = $config->getOptionalString('store.redis.host', 'localhost');
            $port = $config->getInteger('store.redis.port', 6379);
            $prefix = $config->getOptionalString('store.redis.prefix', 'SimpleSAMLphp');
            $password = $config->getOptionalString('store.redis.password', null);
            $database = $config->getInteger('store.redis.database', 0);

            $redis = new Client(
                [
                    'scheme' => 'tcp',
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                ] + (!empty($password) ? ['password' => $password] : []),
                [
                    'prefix' => $prefix,
                ]
            );
        }

        $this->redis = $redis;
    }


    /**
     * Deconstruct the Redis data store.
     */
    public function __destruct()
    {
        $this->redis->disconnect();
    }


    /**
     * Retrieve a value from the data store.
     *
     * @param string $type The type of the data.
     * @param string $key The key to retrieve.
     *
     * @return mixed|null The value associated with that key, or null if there's no such key.
     */
    public function get(string $type, string $key)
    {
        /** @var string|null $result */
        $result = $this->redis->get("{$type}.{$key}");

        if ($result === null) {
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
    public function set(string $type, string $key, $value, ?int $expire = null): void
    {
        Assert::nullOrGreaterThan($expire, 2592000);

        $serialized = serialize($value);

        if ($expire === null) {
            $this->redis->set("{$type}.{$key}", $serialized);
        } else {
            // setex expire time is in seconds (not unix timestamp)
            $this->redis->setex("{$type}.{$key}", $expire - time(), $serialized);
        }
    }


    /**
     * Delete an entry from the data store.
     *
     * @param string $type The type of the data
     * @param string $key The key to delete.
     */
    public function delete(string $type, string $key): void
    {
        $this->redis->del("{$type}.{$key}");
    }
}
