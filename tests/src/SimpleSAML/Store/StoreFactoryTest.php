<?php

declare(strict_types=1);

namespace SimpleSAML\Test;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use ReflectionClass;
use SimpleSAML\Configuration;
use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\Store;
use SimpleSAML\Store\StoreFactory;

/**
 * Tests for the StoreFactory class.
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 *
 * @covers \SimpleSAML\Store\StoreFactory
 * @package simplesamlphp/simplesamlphp
 */
class StoreFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function defaultStore(): void
    {
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');

        $config = Configuration::getInstance();
        $storeType = $config->getOptionalString('store.type', 'phpsession');

        /** @var false $store */
        $store = StoreFactory::getInstance($storeType);

        $this->assertFalse($store);
    }


    /**
     * @test
     */
    public function phpSessionStore(): void
    {
        Configuration::loadFromArray([
            'store.type' => 'phpsession',
        ], '[ARRAY]', 'simplesaml');

        $config = Configuration::getInstance();
        $storeType = $config->getString('store.type');

        /** @var false $store */
        $store = StoreFactory::getInstance($storeType);

        $this->assertFalse($store);
    }


    /**
     * @test
     */
    public function memcacheStore(): void
    {
        Configuration::loadFromArray([
            'store.type' => 'memcache',
        ], '[ARRAY]', 'simplesaml');

        $config = Configuration::getInstance();
        $storeType = $config->getString('store.type');

        $store = StoreFactory::getInstance($storeType);

        $this->assertInstanceOf(Store\MemcacheStore::class, $store);
    }


    /**
     * @test
     */
    public function redisStore(): void
    {
        Configuration::loadFromArray([
            'store.type'                    => 'redis',
            'store.redis.prefix'            => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');

        $config = Configuration::getInstance();
        $storeType = $config->getString('store.type');

        /** @psalm-var \SimpleSAML\Store\RedisStore $store */
        $store = StoreFactory::getInstance($storeType);
        $store->redis = $this->getMockBuilder(Client::class)
                                   ->setMethods(['get', 'set', 'setex', 'del', 'disconnect', '__destruct'])
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $this->assertInstanceOf(Store\RedisStore::class, $store);
    }


    /**
     * @test
     */
    public function sqlStore(): void
    {
        Configuration::loadFromArray([
            'store.type'                    => 'sql',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');

        $config = Configuration::getInstance();
        $storeType = $config->getString('store.type');

        $store = StoreFactory::getInstance($storeType);

        $this->assertInstanceOf(Store\SQLStore::class, $store);
    }


    /**
     * @test
     */
    public function pathStore(): void
    {
        Configuration::loadFromArray([
            'store.type'                    => '\SimpleSAML\Store\SQLStore',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');

        $config = Configuration::getInstance();
        $storeType = $config->getString('store.type');

        $store = StoreFactory::getInstance($storeType);

        $this->assertInstanceOf(Store\SQLStore::class, $store);
    }


    /**
     * @test
     */
    public function notFoundStoreException(): void
    {
        $this->expectException(CriticalConfigurationError::class);
        Configuration::loadFromArray([
            'store.type'                    => '\Test\SimpleSAML\Store\Dummy',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');

        $config = Configuration::getInstance();
        $storeType = $config->getString('store.type');

        StoreFactory::getInstance($storeType);
    }


    /**
     */
    protected function tearDown(): void
    {
        $config = Configuration::getInstance();
        $storeType = $config->getOptionalString('store.type', 'phpsession');

        /** @var \SimpleSAML\Store\StoreInterface $store */
        $store = StoreFactory::getInstance($storeType);

        $this->clearInstance($config, Configuration::class);
        $this->clearInstance($store, StoreFactory::class);
    }


    /**
     * @param \SimpleSAML\Configuration|\SimpleSAML\Store\StoreInterface $service
     * @param class-string $className
     */
    protected function clearInstance($service, string $className): void
    {
        $reflectedClass = new ReflectionClass($className);
        $reflectedInstance = $reflectedClass->getProperty('instance');
        $reflectedInstance->setAccessible(true);
        if ($service instanceof Configuration) {
            $reflectedInstance->setValue($service, []);
        } else {
            $reflectedInstance->setValue($service, null);
        }
        $reflectedInstance->setAccessible(false);
    }
}
