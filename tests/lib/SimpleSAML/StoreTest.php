<?php
/*
 * This file is part of the sgomezsimplesamlphp.
 *
 * (c) Sergio Gómez <sergio@uco.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


class StoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \SimpleSAML\Store::getInstance
     * @test
     */
    public function defaultStore()
    {
        \SimpleSAML_Configuration::loadFromArray(array(
        ), '[ARRAY]', 'simplesaml');

        $store = \SimpleSAML\Store::getInstance();

        $this->assertEquals(false, $store);
    }

    /**
     * @covers \SimpleSAML\Store::getInstance
     * @test
     */
    public function phpSessionStore()
    {
        \SimpleSAML_Configuration::loadFromArray(array(
        ), '[ARRAY]', 'simplesaml');

        $store = \SimpleSAML\Store::getInstance();

        $this->assertEquals(false, $store);
    }

    /**
     * @covers \SimpleSAML\Store::getInstance
     * @test
     */
    public function memcacheStore()
    {
        \SimpleSAML_Configuration::loadFromArray(array(
            'store.type'                    => 'memcache',
        ), '[ARRAY]', 'simplesaml');

        $store = \SimpleSAML\Store::getInstance();

        $this->assertInstanceOf('\SimpleSAML\Store\Memcache', $store);
    }

    /**
     * @covers SimpleSAML\Store::getInstance
     * @test
     */
    public function sqlStore()
    {
        \SimpleSAML_Configuration::loadFromArray(array(
            'store.type'                    => 'sql',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ), '[ARRAY]', 'simplesaml');

        $store = \SimpleSAML\Store::getInstance();

        $this->assertInstanceOf('SimpleSAML\Store\SQL', $store);
    }

    /**
     * @covers SimpleSAML\Store::getInstance
     * @test
     */
    public function pathStore()
    {
        \SimpleSAML_Configuration::loadFromArray(array(
            'store.type'                    => '\SimpleSAML\Store\SQL',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ), '[ARRAY]', 'simplesaml');

        $store = \SimpleSAML\Store::getInstance();

        $this->assertInstanceOf('SimpleSAML\Store\SQL', $store);
    }

    /**
     * @covers SimpleSAML\Store::getInstance
     * @expectedException SimpleSAML\Error\CriticalConfigurationError
     * @test
     */
    public function notFoundStoreException()
    {
        \SimpleSAML_Configuration::loadFromArray(array(
            'store.type'                    => '\Test\SimpleSAML\Store\Dummy',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ), '[ARRAY]', 'simplesaml');

        $store = \SimpleSAML\Store::getInstance();
    }
    
    protected function tearDown()
    {
        $config = SimpleSAML_Configuration::getInstance();
        $store = \SimpleSAML\Store::getInstance();

        $this->clearInstance($config, '\SimpleSAML_Configuration');
        $this->clearInstance($store, '\SimpleSAML\Store');
    }

    protected function clearInstance($service, $className)
    {
        $reflectedClass = new ReflectionClass($className);
        $reflectedInstance = $reflectedClass->getProperty('instance');
        $reflectedInstance->setAccessible(true);
        $reflectedInstance->setValue($service, null);
        $reflectedInstance->setAccessible(false);
    }
}