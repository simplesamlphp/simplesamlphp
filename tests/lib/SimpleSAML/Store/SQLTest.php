<?php

namespace SimpleSAML\Test\Store;

use \SimpleSAML_Configuration as Configuration;
use \SimpleSAML\Store;

/**
 * Tests for the SQL store.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @author Sergio Gómez <sergio@uco.es>
 * @package simplesamlphp/simplesamlphp
 */
class SQLTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        \SimpleSAML_Configuration::loadFromArray(array(
            'store.type'                    => 'sql',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ), '[ARRAY]', 'simplesaml');
    }

    /**
     * @covers \SimpleSAML\Store::getInstance
     * @covers \SimpleSAML\Store\SQL::__construct
     * @test
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
     */
    public function kvstoreTableVersion()
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $version = $store->getTableVersion('kvstore');

        $this->assertEquals(1, $version);
    }

    /**
     * @covers \SimpleSAML\Store\SQL::getTableVersion
     * @test
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
     */
    public function testGetEmptyData()
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $value = $store->get('test', 'foo');

        $this->assertEquals(null, $value);
    }

    /**
     * @covers \SimpleSAML\Store\SQL::get
     * @covers \SimpleSAML\Store\SQL::set
     * @covers \SimpleSAML\Store\SQL::insertOrUpdate
     * @test
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
     */
    public function testDeleteData()
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $store->set('test', 'foo', 'bar');
        $store->delete('test', 'foo');
        $value = $store->get('test', 'foo');

        $this->assertEquals(null, $value);
    }

    /**
     * @covers \SimpleSAML\Store\SQL::get
     * @covers \SimpleSAML\Store\SQL::set
     * @covers \SimpleSAML\Store\SQL::insertOrUpdate
     * @covers \SimpleSAML\Store\SQL::delete
     * @test
     */
    public function testVeryLongKey()
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $key = str_repeat('x', 100);
        $store->set('test', $key, 'bar');
        $store->delete('test', $key);
        $value = $store->get('test', $key);

        $this->assertEquals(null, $value);
    }

    protected function tearDown()
    {
        $config = Configuration::getInstance();
        $store = Store::getInstance();

        $this->clearInstance($config, '\SimpleSAML_Configuration');
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
