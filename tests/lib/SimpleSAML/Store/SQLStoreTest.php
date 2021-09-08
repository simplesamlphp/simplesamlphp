<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Store;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Store;
use SimpleSAML\Store\StoreFactory;

/**
 * Tests for the SQL store.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @covers \SimpleSAML\Store\SQLStore
 * @package simplesamlphp/simplesamlphp
 */
class SQLStoreTest extends TestCase
{
    /** @var \SimpleSAML\Store\SQLStore $store */
    private Store\SQLStore $store;


    /**
     */
    protected function setUp(): void
    {
        Configuration::loadFromArray([
            'store.type'                    => 'sql',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');

        $this->store = new Store\SQLStore();
    }


    /**
     * @test
     */
    public function SQLInstance(): void
    {
        $this->assertInstanceOf(Store\SQLStore::class, $this->store);
    }


    /**
     * @test
     */
    public function kvstoreTableVersion(): void
    {
        $version = $this->store->getTableVersion('kvstore');

        $this->assertEquals(2, $version);
    }


    /**
     * @test
     */
    public function newTableVersion(): void
    {
        $version = $this->store->getTableVersion('test');

        $this->assertEquals(0, $version);
    }


    /**
     * @test
     */
    public function testSetTableVersion(): void
    {
        $this->store->setTableVersion('kvstore', 2);
        $version = $this->store->getTableVersion('kvstore');

        $this->assertEquals(2, $version);
    }


    /**
     * @test
     */
    public function testGetEmptyData(): void
    {
        $value = $this->store->get('test', 'foo');

        $this->assertNull($value);
    }


    /**
     * @test
     */
    public function testInsertData(): void
    {
        $this->store->set('test', 'foo', 'bar');
        $value = $this->store->get('test', 'foo');

        $this->assertEquals('bar', $value);
    }


    /**
     * @test
     */
    public function testOverwriteData(): void
    {
        $this->store->set('test', 'foo', 'bar');
        $this->store->set('test', 'foo', 'baz');
        $value = $this->store->get('test', 'foo');

        $this->assertEquals('baz', $value);
    }


    /**
     * @test
     */
    public function testDeleteData(): void
    {
        $this->store->set('test', 'foo', 'bar');
        $this->store->delete('test', 'foo');
        $value = $this->store->get('test', 'foo');

        $this->assertNull($value);
    }


    /**
     * @test
     */
    public function testVeryLongKey(): void
    {
        $key = str_repeat('x', 100);
        $this->store->set('test', $key, 'bar');
        $this->store->delete('test', $key);
        $value = $this->store->get('test', $key);

        $this->assertNull($value);
    }
}
