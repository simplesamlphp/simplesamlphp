<?php

namespace SimpleSAML\Test;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\Store;

/**
 * Tests for the Store abstract class.
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 *
 * @author Sergio Gómez <sergio@uco.es>
 * @package simplesamlphp/simplesamlphp
 */
class StoreTest extends TestCase
{
    /**
     * @covers \SimpleSAML\Store::getInstance
     * @test
     * @return void
     */
    public function defaultStore()
    {
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');

        /** @var false $store */
        $store = Store::getInstance();

        $this->assertFalse($store);
    }


    /**
     * @covers \SimpleSAML\Store::getInstance
     * @test
     * @return void
     */
    public function phpSessionStore()
    {
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');

        /** @var false $store */
        $store = Store::getInstance();

        $this->assertFalse($store);
    }


    /**
     * @covers \SimpleSAML\Store::getInstance
     * @test
     * @return void
     */
    public function memcacheStore()
    {
        Configuration::loadFromArray([
            'store.type'                    => 'memcache',
        ], '[ARRAY]', 'simplesaml');

        $store = Store::getInstance();

        $this->assertInstanceOf(Store\Memcache::class, $store);
    }


    /**
     * @covers \SimpleSAML\Store::getInstance
     * @test
     * @return void
     */
    public function sqlStore()
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
     * @covers \SimpleSAML\Store::getInstance
     * @test
     * @return void
     */
    public function pathStore()
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
     * @covers \SimpleSAML\Store::getInstance
     * @test
     * @return void
     */
    public function notFoundStoreException()
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
     * @return void
     */
    protected function tearDown()
    {
        $config = Configuration::getInstance();
        /** @var \SimpleSAML\Store $store */
        $store = Store::getInstance();

        $this->clearInstance($config, Configuration::class);
        $this->clearInstance($store, Store::class);
    }


    /**
     * @param \SimpleSAML\Configuration|\SimpleSAML\Store $service
     * @param string $className
     * @return void
     */
    protected function clearInstance($service, $className)
    {
        $reflectedClass = new \ReflectionClass($className);
        $reflectedInstance = $reflectedClass->getProperty('instance');
        $reflectedInstance->setAccessible(true);
        $reflectedInstance->setValue($service, null);
        $reflectedInstance->setAccessible(false);
    }
}
