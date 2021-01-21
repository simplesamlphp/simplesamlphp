<?php

declare(strict_types=1);

namespace SimpleSAML\Test;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SimpleSAML\Configuration;
use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\Store;

/**
 * Tests for the Store abstract class.
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 *
 * @covers \SimpleSAML\Store
 *
 * @package simplesamlphp/simplesamlphp
 */
class StoreTest extends TestCase
{
    /**
     * @test
     */
    public function defaultStore(): void
    {
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');

        /** @var false $store */
        $store = Store::getInstance();

        $this->assertFalse($store);
    }


    /**
     * @test
     */
    public function phpSessionStore(): void
    {
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');

        /** @var false $store */
        $store = Store::getInstance();

        $this->assertFalse($store);
    }


    /**
     * @test
     */
    public function memcacheStore(): void
    {
        Configuration::loadFromArray([
            'store.type'                    => 'memcache',
        ], '[ARRAY]', 'simplesaml');

        $store = Store::getInstance();

        $this->assertInstanceOf(Store\Memcache::class, $store);
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

        $store = Store::getInstance();

        $this->assertInstanceOf(Store\SQL::class, $store);
    }


    /**
     * @test
     */
    public function pathStore(): void
    {
        Configuration::loadFromArray([
            'store.type'                    => '\SimpleSAML\Store\SQL',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');

        $store = Store::getInstance();

        $this->assertInstanceOf(Store\SQL::class, $store);
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

        Store::getInstance();
    }


    /**
     */
    protected function tearDown(): void
    {
        $config = Configuration::getInstance();
        /** @var \SimpleSAML\Store $store */
        $store = Store::getInstance();

        $this->clearInstance($config, Configuration::class);
        $this->clearInstance($store, Store::class);
    }


    /**
     * @param \SimpleSAML\Configuration|\SimpleSAML\Store $service
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
