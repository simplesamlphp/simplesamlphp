<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Store;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use SimpleSAML\Configuration;
use SimpleSAML\Store;

/**
 * Tests for the Redis store.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @package simplesamlphp/simplesamlphp
 */
#[CoversClass(Store\RedisStore::class)]
class RedisStoreTest extends TestCase
{
    /** @var \Predis\Client */
    protected Client $client;

    /** @var \SimpleSAML\Store\RedisStore */
    protected Store\RedisStore $store;

    /** @var array */
    protected array $config;


    /**
     */
    protected function setUp(): void
    {
        $this->config = [];

        $this->client = new class ($this) extends Client
        {
            public function __construct(
                protected RedisStoreTest $unitTest,
            ) {
            }

            public function __deconstruct()
            {
            }

            public function disconnect(): void
            {
            }

            public function get(string $str): ?string
            {
                return $this->unitTest->getMocked($str);
            }

            public function set(string $str, mixed $value): void
            {
                $this->unitTest->setMocked($str, $value);
            }

            public function setEx(string $str, int $expire, mixed $value): void
            {
                $this->unitTest->setExMocked($str, $expire, $value);
            }

            public function del(string $str): void
            {
                $this->unitTest->delMocked($str);
            }
        };

        $this->store = new Store\RedisStore($this->client);
    }


    /**
     * @param string $key
     * @return string|null
     */
    public function getMocked(string $key): ?string
    {
        return array_key_exists($key, $this->config) ? $this->config[$key] : null;
    }


    /**
     * @param string $key
     * @param mixed $value
     */
    public function setMocked(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }


    /**
     * @param string $key
     * @param int $expire
     * @param mixed $value
     */
    public function setexMocked(string $key, int $expire, mixed $value): void
    {
        // Testing expiring data is more trouble than it's worth for now
        $this->setMocked($key, $value);
    }


    /**
     * @param string $key
     */
    public function delMocked(string $key): void
    {
        unset($this->config[$key]);
    }


    /**
     */
    public function testRedisInstance(): void
    {
        $config = Configuration::loadFromArray([
            'store.type' => 'redis',
            'store.redis.prefix' => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');

        $this->assertInstanceOf(Store\RedisStore::class, $this->store);
    }

    /**
     */
    public function testRedisInstanceWithInsecureTLS(): void
    {
        $config = Configuration::loadFromArray([
            'store.type' => 'redis',
            'store.redis.prefix' => 'phpunit_',
            'store.redis.tls' => true,
            'store.redis.insecure' => true,
        ], '[ARRAY]', 'simplesaml');

        $this->assertInstanceOf(Store\RedisStore::class, $this->store);
    }

    /**
     */
    public function testRedisInstanceWithSecureTLS(): void
    {
        $config = Configuration::loadFromArray([
            'store.type' => 'redis',
            'store.redis.prefix' => 'phpunit_',
            'store.redis.tls' => true,
            'store.redis.ca_certificate' => '/tmp/ssl/pki_roots.crt.pem',
            'store.redis.certificate' => '/tmp/ssl/phpunit.crt.pem',
            'store.redis.privatekey' => '/tmp/ssl/phpunit.key.pem',
        ], '[ARRAY]', 'simplesaml');

        $this->assertInstanceOf(Store\RedisStore::class, $this->store);
    }

    /**
     */
    public function testRedisInstanceWithPassword(): void
    {
        $config = Configuration::loadFromArray([
            'store.type' => 'redis',
            'store.redis.prefix' => 'phpunit_',
            'store.redis.password' => 'password',
        ], '[ARRAY]', 'simplesaml');

        $this->assertInstanceOf(Store\RedisStore::class, $this->store);
    }

    /**
     */
    public function testRedisInstanceWithPasswordAndUsername(): void
    {
        $config = Configuration::loadFromArray([
            'store.type' => 'redis',
            'store.redis.prefix' => 'phpunit_',
            'store.redis.password' => 'password',
            'store.redis.username' => 'username',
        ], '[ARRAY]', 'simplesaml');

        $this->assertInstanceOf(Store\RedisStore::class, $this->store);
    }

    /**
     */
    public function testRedisSentinelInstance(): void
    {
        $config = Configuration::loadFromArray([
            'store.type' => 'redis',
            'store.redis.prefix' => 'phpunit_',
            'store.redis.mastergroup' => 'phpunit_mastergroup',
            'store.redis.sentinels' => ['tcp://sentinel1', 'tcp://sentinel2', 'tcp://sentinel3'],
        ], '[ARRAY]', 'simplesaml');
        $this->assertInstanceOf(Store\RedisStore::class, $this->store);
    }

    /**
     */
    public function testInsertData(): void
    {
        $value = 'TEST';

        $this->store->set('test', 'key', $value);
        $res = $this->store->get('test', 'key');
        $expected = $value;

        $this->assertEquals($expected, $res);
    }


    /**
     */
    public function testInsertExpiringData(): void
    {
        $value = 'TEST';

        $this->store->set('test', 'key', $value, $expire = 80808080);
        $res = $this->store->get('test', 'key');
        $expected = $value;

        $this->assertEquals($expected, $res);
    }


    /**
     */
    public function testGetEmptyData(): void
    {
        $res = $this->store->get('test', 'key');

        $this->assertNull($res);
    }


    /**
     */
    public function testOverwriteData(): void
    {
        $value1 = 'TEST1';
        $value2 = 'TEST2';

        $this->store->set('test', 'key', $value1);
        $this->store->set('test', 'key', $value2);
        $res = $this->store->get('test', 'key');
        $expected = $value2;

        $this->assertEquals($expected, $res);
    }


    /**
     */
    public function testDeleteData(): void
    {
        $this->store->set('test', 'key', 'TEST');
        $this->store->delete('test', 'key');
        $res = $this->store->get('test', 'key');

        $this->assertNull($res);
    }
}
