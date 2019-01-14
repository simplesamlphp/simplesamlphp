<?php

namespace SimpleSAML\Test;

use PHPUnit\Framework\TestCase;
use \SimpleSAML\Configuration;
use \SimpleSAML\Store;

/**
 * Tests for the Store abstract class.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @author Sergio Gómez <sergio@uco.es>
 * @package simplesamlphp/simplesamlphp
 */
class StoreTest extends TestCase
{
    /**
     * @covers \SimpleSAML\Store::getInstance
     * @test
     */
    public function defaultStore()
    {
        Configuration::loadFromArray([
        ], '[ARRAY]', 'simplesaml');

        $store = Store::getInstance();

        $this->assertFalse($store);
    }


    /**
     * @covers \SimpleSAML\Store::getInstance
     * @test
     */
    public function phpSessionStore()
    {
        Configuration::loadFromArray([
        ], '[ARRAY]', 'simplesaml');

        $store = Store::getInstance();

        $this->assertFalse($store);
    }


    /**
     * @covers \SimpleSAML\Store::getInstance
     * @test
     */
    public function memcacheStore()
    {
        Configuration::loadFromArray([
            'store.type'                    => 'memcache',
        ], '[ARRAY]', 'simplesaml');

        $store = Store::getInstance();

        $this->assertInstanceOf('\SimpleSAML\Store\Memcache', $store);
    }


    /**
     * @covers \SimpleSAML\Store::getInstance
     * @test
     */
    public function sqlStore()
    {
        Configuration::loadFromArray([
            'store.type'                    => 'sql',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');

        $store = Store::getInstance();

        $this->assertInstanceOf('SimpleSAML\Store\SQL', $store);
    }


    /**
     * @covers \SimpleSAML\Store::getInstance
     * @test
     */
    public function pathStore()
    {
        Configuration::loadFromArray([
            'store.type'                    => '\SimpleSAML\Store\SQL',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');

        $store = Store::getInstance();

        $this->assertInstanceOf('SimpleSAML\Store\SQL', $store);
    }


    /**
     * @covers \SimpleSAML\Store::getInstance
     * @expectedException \SimpleSAML\Error\CriticalConfigurationError
     * @test
     */
    public function notFoundStoreException()
    {
        Configuration::loadFromArray([
            'store.type'                    => '\Test\SimpleSAML\Store\Dummy',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');

        Store::getInstance();
    }


    protected function tearDown()
    {
        $config = Configuration::getInstance();
        $store = Store::getInstance();

        $this->clearInstance($config, '\SimpleSAML\Configuration');
        $this->clearInstance($store, '\SimpleSAML\Store');
    }


    protected function clearInstance($service, $className)
    {
        $reflectedClass = new \ReflectionClass($className);
        $reflectedInstance = $reflectedClass->getProperty('instance');
        $reflectedInstance->setAccessible(true);
        $reflectedInstance->setValue($service, null);
        $reflectedInstance->setAccessible(false);
    }
}
