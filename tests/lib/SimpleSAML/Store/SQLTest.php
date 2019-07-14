<?php

namespace SimpleSAML\Test\Store;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Store;

/**
 * Tests for the SQL store.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @author Sergio Gómez <sergio@uco.es>
 * @package simplesamlphp/simplesamlphp
 */
class SQLTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp()
    {
        Configuration::loadFromArray([
            'store.type'                    => 'sql',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');
    }


    /**
     * @covers \SimpleSAML\Store::getInstance
     * @covers \SimpleSAML\Store\SQL::__construct
     * @test
     * @return void
     */
    public function SQLInstance()
    {
        $store = Store::getInstance();

        $this->assertInstanceOf('SimpleSAML\Store\SQL', $store);
    }


    /**
     * @covers \SimpleSAML\Store\SQL::initTableVersionTable
     * @covers \SimpleSAML\Store\SQL::initKVTable
     * @test
     * @return void
     */
    public function kvstoreTableVersion()
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $version = $store->getTableVersion('kvstore');

        $this->assertEquals(2, $version);
    }


    /**
     * @covers \SimpleSAML\Store\SQL::getTableVersion
     * @test
     * @return void
     */
    public function newTableVersion()
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $version = $store->getTableVersion('test');

        $this->assertEquals(0, $version);
    }


    /**
     * @covers \SimpleSAML\Store\SQL::setTableVersion
     * @covers \SimpleSAML\Store\SQL::insertOrUpdate
     * @test
     * @return void
     */
    public function testSetTableVersion()
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $store->setTableVersion('kvstore', 2);
        $version = $store->getTableVersion('kvstore');

        $this->assertEquals(2, $version);
    }


    /**
     * @covers \SimpleSAML\Store\SQL::get
     * @test
     * @return void
     */
    public function testGetEmptyData()
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $value = $store->get('test', 'foo');

        $this->assertNull($value);
    }


    /**
     * @covers \SimpleSAML\Store\SQL::get
     * @covers \SimpleSAML\Store\SQL::set
     * @covers \SimpleSAML\Store\SQL::insertOrUpdate
     * @test
     * @return void
     */
    public function testInsertData()
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $store->set('test', 'foo', 'bar');
        $value = $store->get('test', 'foo');

        $this->assertEquals('bar', $value);
    }


    /**
     * @covers \SimpleSAML\Store\SQL::get
     * @covers \SimpleSAML\Store\SQL::set
     * @covers \SimpleSAML\Store\SQL::insertOrUpdate
     * @test
     * @return void
     */
    public function testOverwriteData()
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $store->set('test', 'foo', 'bar');
        $store->set('test', 'foo', 'baz');
        $value = $store->get('test', 'foo');

        $this->assertEquals('baz', $value);
    }


    /**
     * @covers \SimpleSAML\Store\SQL::get
     * @covers \SimpleSAML\Store\SQL::set
     * @covers \SimpleSAML\Store\SQL::insertOrUpdate
     * @covers \SimpleSAML\Store\SQL::delete
     * @test
     * @return void
     */
    public function testDeleteData()
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $store->set('test', 'foo', 'bar');
        $store->delete('test', 'foo');
        $value = $store->get('test', 'foo');

        $this->assertNull($value);
    }


    /**
     * @covers \SimpleSAML\Store\SQL::get
     * @covers \SimpleSAML\Store\SQL::set
     * @covers \SimpleSAML\Store\SQL::insertOrUpdate
     * @covers \SimpleSAML\Store\SQL::delete
     * @test
     * @return void
     */
    public function testVeryLongKey()
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $key = str_repeat('x', 100);
        $store->set('test', $key, 'bar');
        $store->delete('test', $key);
        $value = $store->get('test', $key);

        $this->assertNull($value);
    }


    /**
     * @return void
     */
    protected function tearDown()
    {
        $config = Configuration::getInstance();
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $this->clearInstance($config, '\SimpleSAML\Configuration');
        $this->clearInstance($store, '\SimpleSAML\Store');
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
