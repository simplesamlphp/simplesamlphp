<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Store;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SimpleSAML\Configuration;
use SimpleSAML\Store;

/**
 * Tests for the SQL store.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @covers \SimpleSAML\Store\SQL
 *
 * @package simplesamlphp/simplesamlphp
 */
class SQLTest extends TestCase
{
    /**
     */
    protected function setUp(): void
    {
        Configuration::loadFromArray([
            'store.type'                    => 'sql',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');
    }


    /**
     * @test
     */
    public function SQLInstance(): void
    {
        $store = Store::getInstance();

        $this->assertInstanceOf(Store\SQL::class, $store);
    }


    /**
     * @test
     */
    public function kvstoreTableVersion(): void
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $version = $store->getTableVersion('kvstore');

        $this->assertEquals(2, $version);
    }


    /**
     * @test
     */
    public function newTableVersion(): void
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $version = $store->getTableVersion('test');

        $this->assertEquals(0, $version);
    }


    /**
     * @test
     */
    public function testSetTableVersion(): void
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $store->setTableVersion('kvstore', 2);
        $version = $store->getTableVersion('kvstore');

        $this->assertEquals(2, $version);
    }


    /**
     * @test
     */
    public function testGetEmptyData(): void
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $value = $store->get('test', 'foo');

        $this->assertNull($value);
    }


    /**
     * @test
     */
    public function testInsertData(): void
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $store->set('test', 'foo', 'bar');
        $value = $store->get('test', 'foo');

        $this->assertEquals('bar', $value);
    }


    /**
     * @test
     */
    public function testOverwriteData(): void
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $store->set('test', 'foo', 'bar');
        $store->set('test', 'foo', 'baz');
        $value = $store->get('test', 'foo');

        $this->assertEquals('baz', $value);
    }


    /**
     * @test
     */
    public function testDeleteData(): void
    {
        /** @var \SimpleSAML\Store\SQL $store */
        $store = Store::getInstance();

        $store->set('test', 'foo', 'bar');
        $store->delete('test', 'foo');
        $value = $store->get('test', 'foo');

        $this->assertNull($value);
    }


    /**
     * @test
     */
    public function testVeryLongKey(): void
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
     */
    protected function tearDown(): void
    {
        $config = Configuration::getInstance();

        /** @var \SimpleSAML\Store\SQL $store */
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
