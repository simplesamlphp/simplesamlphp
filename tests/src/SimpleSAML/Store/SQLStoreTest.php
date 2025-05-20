<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Store;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Store;

use function str_repeat;

/**
 * Tests for the SQL store.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @package simplesamlphp/simplesamlphp
 */
#[CoversClass(Store\SQLStore::class)]
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
     */
    public function testSQLInstance(): void
    {
        $this->assertInstanceOf(Store\SQLStore::class, $this->store);
    }


    /**
     */
    public function testKvstoreTableVersion(): void
    {
        $version = $this->store->getTableVersion('kvstore');

        $this->assertEquals(2, $version);
    }


    /**
     */
    public function testNewTableVersion(): void
    {
        $version = $this->store->getTableVersion('test');

        $this->assertEquals(0, $version);
    }


    /**
     */
    public function testSetTableVersion(): void
    {
        $this->store->setTableVersion('kvstore', 2);
        $version = $this->store->getTableVersion('kvstore');

        $this->assertEquals(2, $version);
    }


    /**
     * @throws \SimpleSAML\Error\CriticalConfigurationError
     */
    public function testGetEmptyData(): void
    {
        $value = $this->store->get('test', 'foo');

        $this->assertNull($value);
    }


    /**
     * @throws \SimpleSAML\Error\CriticalConfigurationError
     */
    public function testInsertData(): void
    {
        $this->store->set('test', 'foo', 'bar');
        $value = $this->store->get('test', 'foo');

        $this->assertEquals('bar', $value);
    }


    /**
     * @throws \SimpleSAML\Error\CriticalConfigurationError
     */
    public function testOverwriteData(): void
    {
        $this->store->set('test', 'foo', 'bar');
        $this->store->set('test', 'foo', 'baz');
        $value = $this->store->get('test', 'foo');

        $this->assertEquals('baz', $value);
    }


    /**
     * @throws \SimpleSAML\Error\CriticalConfigurationError
     */
    public function testDeleteData(): void
    {
        $this->store->set('test', 'foo', 'bar');
        $this->store->delete('test', 'foo');
        $value = $this->store->get('test', 'foo');

        $this->assertNull($value);
    }


    /**
     * @throws \SimpleSAML\Error\CriticalConfigurationError
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
