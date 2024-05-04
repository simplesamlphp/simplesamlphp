<?php

declare(strict_types=1);

namespace SimpleSAML\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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
 * @package simplesamlphp/simplesamlphp
 */
#[CoversClass(StoreFactory::class)]
class StoreFactoryTest extends TestCase
{
    /**
     */
    public function testDefaultStore(): void
    {
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');

        $config = Configuration::getInstance();
        $storeType = $config->getOptionalString('store.type', 'phpsession');

        $store = StoreFactory::getInstance($storeType);
        $this->assertFalse($store);
    }


    /**
     */
    public function testPhpSessionStore(): void
    {
        Configuration::loadFromArray([
            'store.type' => 'phpsession',
        ], '[ARRAY]', 'simplesaml');

        $config = Configuration::getInstance();
        $storeType = $config->getString('store.type');

        $store = StoreFactory::getInstance($storeType);
        $this->assertFalse($store);
    }


    /**
     */
    public function testMemcacheStore(): void
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
     */
    public function testRedisStore(): void
    {
        Configuration::loadFromArray([
            'store.type'                    => 'redis',
            'store.redis.prefix'            => 'phpunit_',
            'store.redis.sentinels'         => [],
        ], '[ARRAY]', 'simplesaml');

        $config = Configuration::getInstance();
        $storeType = $config->getString('store.type');

        /** @psalm-var \SimpleSAML\Store\RedisStore $store */
        $store = StoreFactory::getInstance($storeType);

        $this->assertInstanceOf(Store\RedisStore::class, $store);
    }


    /**
     */
    public function testSqlStore(): void
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
     */
    public function testPathStore(): void
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
     */
    public function testNotFoundStoreException(): void
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
        if ($service instanceof Configuration) {
            $reflectedClass->setStaticPropertyValue('instance', []);
        } else {
            $reflectedClass->setStaticPropertyValue('instance', null);
        }
    }
}
