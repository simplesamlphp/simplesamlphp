<?php

declare(strict_types=1);

namespace SimpleSAML\Store;

use Predis\Client;
use SimpleSAML\Assert\Assert;
use SimpleSAML\{Configuration, Error, Utils};

use function class_exists;
use function serialize;
use function time;
use function unserialize;

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
    public function __construct(?Client $redis = null)
    {
        if (!class_exists(Client::class)) {
            throw new Error\CriticalConfigurationError('predis/predis is not available.');
        }

        Assert::nullOrIsInstanceOf($redis, Client::class);

        if ($redis === null) {
            $config = Configuration::getInstance();

            $host = $config->getOptionalString('store.redis.host', 'localhost');
            $port = $config->getOptionalInteger('store.redis.port', 6379);
            $prefix = $config->getOptionalString('store.redis.prefix', 'SimpleSAMLphp');
            $password = $config->getOptionalString('store.redis.password', null);
            $username = $config->getOptionalString('store.redis.username', null);
            $database = $config->getOptionalInteger('store.redis.database', 0);
            $tls = $config->getOptionalBoolean('store.redis.tls', false);
            $scheme = $tls ? 'tls' : 'tcp';
            $ssl = [];

            $sentinels = $config->getOptionalArray('store.redis.sentinels', []);

            if ($tls) {
                $configUtils = new Utils\Config();

                if ($config->getOptionalBoolean('store.redis.insecure', false)) {
                    $ssl['verify_peer'] = false;
                    $ssl['verify_peer_name'] = false;
                } else {
                    $ca = $config->getOptionalString('store.redis.ca_certificate', null);

                    if ($ca !== null) {
                        $ssl['cafile'] = $configUtils->getCertPath($ca);
                    }
                }

                $cert = $config->getOptionalString('store.redis.certificate', null);
                $key = $config->getOptionalString('store.redis.privatekey', null);

                if ($cert !== null && $key !== null) {
                    $ssl['local_cert'] = $configUtils->getCertPath($cert);
                    $ssl['local_pk'] = $configUtils->getCertPath($key);
                }
            }

            if (empty($sentinels)) {
                $redis = new Client(
                    [
                        'scheme' => $scheme,
                        'host' => $host,
                        'port' => $port,
                        'database' => $database,
                    ]
                    + (!empty($ssl) ? ['ssl' => $ssl] : [])
                    + (!empty($username) ? ['username' => $username] : [])
                    + (!empty($password) ? ['password' => $password] : []),
                    [
                        'prefix' => $prefix,
                    ],
                );
            } else {
                $mastergroup = $config->getOptionalString('store.redis.mastergroup', 'mymaster');
                $redis = new Client(
                    $sentinels,
                    [
                        'replication' => 'sentinel',
                        'service' => $mastergroup,
                        'prefix' => $prefix,
                        'parameters' => [
                            'database' => $database,
                        ]
                        + (!empty($ssl) ? ['ssl' => $ssl] : [])
                        + (!empty($username) ? ['username' => $username] : [])
                        + (!empty($password) ? ['password' => $password] : []),
                    ],
                );
            }
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
    public function get(string $type, string $key): mixed
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
    public function set(string $type, string $key, mixed $value, ?int $expire = null): void
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
