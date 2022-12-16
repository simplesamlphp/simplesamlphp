<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Store;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Client;
use SimpleSAML\Configuration;
use SimpleSAML\Store;
use SimpleSAML\Store\StoreFactory;

/**
 * Tests for the Redis store.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @covers \SimpleSAML\Store\RedisStore
 * @package simplesamlphp/simplesamlphp
 */
class RedisStoreTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected MockObject $mocked_redis;

    /** @var \SimpleSAML\Store\RedisStore */
    protected Store\RedisStore $store;

    /** @var array */
    protected array $config;


    /**
     */
    protected function setUp(): void
    {
        $this->config = [];

        $this->mocked_redis = $this->getMockBuilder(Client::class)
                                   ->setMethods(['get', 'set', 'setex', 'del', 'disconnect', '__destruct'])
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $this->mocked_redis->method('get')
                           ->will($this->returnCallback([$this, 'getMocked']));

        $this->mocked_redis->method('set')
                           ->will($this->returnCallback([$this, 'setMocked']));

        $this->mocked_redis->method('setex')
                           ->will($this->returnCallback([$this, 'setexMocked']));

        $this->mocked_redis->method('del')
                           ->will($this->returnCallback([$this, 'delMocked']));

        $this->store = new Store\RedisStore($this->mocked_redis);
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
    public function setMocked(string $key, $value): void
    {
        $this->config[$key] = $value;
    }


    /**
     * @param string $key
     * @param int $expire
     * @param mixed $value
     */
    public function setexMocked(string $key, int $expire, $value): void
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
     * @test
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
     * @test
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
     * @test
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
     * @test
     */
    public function testRedisSentinelInstance(): void
    {
        $config = Configuration::loadFromArray(array(
            'store.type' => 'redis',
            'store.redis.prefix' => 'phpunit_',
            'store.redis.mastergroup' => 'phpunit_mastergroup',
            'store.redis.sentinels' => array('tcp://sentinel1', 'tcp://sentinel2', 'tcp://sentinel3'),
        ), '[ARRAY]', 'simplesaml');
        $this->assertInstanceOf(Store\RedisStore::class, $this->store);
    }

    /**
     * @test
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
     * @test
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
     * @test
     */
    public function testGetEmptyData(): void
    {
        $res = $this->store->get('test', 'key');

        $this->assertNull($res);
    }


    /**
     * @test
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
     * @test
     */
    public function testDeleteData(): void
    {
        $this->store->set('test', 'key', 'TEST');
        $this->store->delete('test', 'key');
        $res = $this->store->get('test', 'key');

        $this->assertNull($res);
    }
}
