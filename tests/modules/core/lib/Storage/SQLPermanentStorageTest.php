<?php

use PHPUnit\Framework\TestCase;

/**
 * Test for the SQLPermanentStorage class.
 */
class Test_Core_Storage_SQLPermanentStorage extends TestCase
{
    private static $sql;

    public static function setUpBeforeClass()
    {
        // Create instance
        $config = \SimpleSAML_Configuration::loadFromArray([
            'datadir' => sys_get_temp_dir(),
        ]);
        self::$sql = new sspmod_core_Storage_SQLPermanentStorage('test', $config);
    }

    public static function tearDownAfterClass()
    {
        self::$sql = null;
        unlink(sys_get_temp_dir().'/sqllite/test.sqlite');
    }

    public function testSet()
    {
        // Set a new value
        self::$sql->set('testtype', 'testkey1', 'testkey2', 'testvalue', 2);

        // Test getCondition
        $result = self::$sql->get();
        $this->assertEquals('testvalue', $result['value']);
    }

    public function testSetOverwrite()
    {
        // Overwrite existing value
        self::$sql->set('testtype', 'testkey1', 'testkey2', 'testvaluemodified', 2);

        // Test that the value was actually overwriten
        $result = self::$sql->getValue('testtype', 'testkey1', 'testkey2');
        $this->assertEquals('testvaluemodified', $result);

        $result = self::$sql->getList('testtype', 'testkey1', 'testkey2');
        $this->assertEquals('testvaluemodified', $result[0]['value']);
    }

    public function testNonexistentKey()
    {
        // Test that getting some non-existing key will return null
        $result = self::$sql->getValue('testtype_nonexistent', 'testkey1_nonexistent', 'testkey2_nonexistent');
        $this->assertNull($result);
        $result = self::$sql->getList('testtype_nonexistent', 'testkey1_nonexistent', 'testkey2_nonexistent');
        $this->assertNull($result);
        $result = self::$sql->get('testtype_nonexistent', 'testkey1_nonexistent', 'testkey2_nonexistent');
        $this->assertNull($result);
    }

    public function testExpiration()
    {
        // Make sure the earlier created entry has expired now
        sleep(3);

        // Make sure we can't get the expired entry anymore
        $result = self::$sql->getValue('testtype', 'testkey1', 'testkey2');
        $this->assertNull($result);

        // Now add a second entry that never expires
        self::$sql->set('testtype', 'testkey1_nonexpiring', 'testkey2_nonexpiring', 'testvalue_nonexpiring', null);

        // Expire entries and verify that only the second one is still there
        self::$sql->removeExpired();
        $result = self::$sql->getValue('testtype', 'testkey1_nonexpiring', 'testkey2_nonexpiring');
        $this->assertEquals('testvalue_nonexpiring', $result);
    }

    public function testRemove()
    {
        // Now remove the nonexpiring entry and make sure it's gone
        self::$sql->remove('testtype', 'testkey1_nonexpiring', 'testkey2_nonexpiring');
        $result = self::$sql->getValue('testtype', 'testkey1_nonexpiring', 'testkey2_nonexpiring');
        $this->assertNull($result);
    }
}
