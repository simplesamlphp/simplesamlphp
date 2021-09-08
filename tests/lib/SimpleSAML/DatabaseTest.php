<?php

declare(strict_types=1);

namespace SimpleSAML\Test;

use Exception;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use SimpleSAML\Configuration;
use SimpleSAML\Database;

/**
 * This test ensures that the \SimpleSAML\Database class can properly
 * query a database.
 *
 * It currently uses sqlite to test, but an alternate config.php file
 * should be created for test cases to ensure that it will work
 * in an environment.
 *
 * @covers \SimpleSAML\Database
 *
 * @package SimpleSAMLphp
 */
class DatabaseTest extends TestCase
{
    /**
     * @var \SimpleSAML\Configuration
     */
    protected $config;

    /**
     * @var \SimpleSAML\Database
     */
    protected $db;


    /**
     * Make protected functions available for testing
     *
     * @param string $getMethod The method to get.
     * @return mixed The method itself.
     */
    protected static function getMethod($getMethod)
    {
        $class = new ReflectionClass(Database::class);
        $method = $class->getMethod($getMethod);
        $method->setAccessible(true);
        return $method;
    }


    /**
     */
    public function setUp(): void
    {
        $config = [
            'database.dsn'        => 'sqlite::memory:',
            'database.username'   => null,
            'database.password'   => null,
            'database.prefix'     => 'phpunit_',
            'database.persistent' => true,
            'database.secondaries'     => [],
        ];

        $this->config = new Configuration($config, "test/SimpleSAML/DatabaseTest.php");

        // Ensure that we have a functional configuration class
        $this->assertEquals($config['database.dsn'], $this->config->getString('database.dsn'));

        $this->db = Database::getInstance($this->config);
    }


    /**
     * @test
     */
    public function connectionFailure(): void
    {
        $this->expectException(Exception::class);
        $config = [
            'database.dsn'        => 'mysql:host=localhost;dbname=saml',
            'database.username'   => 'notauser',
            'database.password'   => 'notausersinvalidpassword',
            'database.prefix'     => 'phpunit_',
            'database.persistent' => true,
            'database.secondaries'     => [],
        ];

        $this->config = new Configuration($config, "test/SimpleSAML/DatabaseTest.php");
        Database::getInstance($this->config);
    }


    /**
     * @test
     */
    public function instances(): void
    {
        $config = [
            'database.dsn'        => 'sqlite::memory:',
            'database.username'   => null,
            'database.password'   => null,
            'database.prefix'     => 'phpunit_',
            'database.persistent' => true,
            'database.secondaries'     => [],
        ];
        $config2 = [
            'database.dsn'        => 'sqlite::memory:',
            'database.username'   => null,
            'database.password'   => null,
            'database.prefix'     => 'phpunit2_',
            'database.persistent' => true,
            'database.secondaries'     => [],
        ];

        $config1 = new Configuration($config, "test/SimpleSAML/DatabaseTest.php");
        $config2 = new Configuration($config2, "test/SimpleSAML/DatabaseTest.php");
        $config3 = new Configuration($config, "test/SimpleSAML/DatabaseTest.php");

        $db1 = Database::getInstance($config1);
        $db2 = Database::getInstance($config2);
        $db3 = Database::getInstance($config3);

        $generateInstanceId = self::getMethod('generateInstanceId');

        $instance1 = $generateInstanceId->invokeArgs($db1, [$config1]);
        $instance2 = $generateInstanceId->invokeArgs($db2, [$config2]);
        $instance3 = $generateInstanceId->invokeArgs($db3, [$config3]);

        // Assert that $instance1 and $instance2 have different instance ids
        $this->assertNotEquals(
            $instance1,
            $instance2,
            "Database instances should be different, but returned the same id"
        );
        // Assert that $instance1 and $instance3 have identical instance ids
        $this->assertEquals(
            $instance1,
            $instance3,
            "Database instances should have the same id, but returned different id"
        );

        // Assert that $db1 and $db2 are different instances
        $this->assertNotEquals(
            spl_object_hash($db1),
            spl_object_hash($db2),
            "Database instances should be different, but returned the same spl_object_hash"
        );
        // Assert that $db1 and $db3 are identical instances
        $this->assertEquals(
            spl_object_hash($db1),
            spl_object_hash($db3),
            "Database instances should be the same, but returned different spl_object_hash"
        );
    }


    /**
     * @test
     */
    public function secondaries(): void
    {
        $ref = new ReflectionClass($this->db);
        $dbPrimary = $ref->getProperty('dbPrimary');
        $dbPrimary->setAccessible(true);
        $primary = spl_object_hash($dbPrimary->getValue($this->db));

        $getSecondary = $ref->getMethod('getSecondary');
        $getSecondary->setAccessible(true);
        $secondary = spl_object_hash($getSecondary->invokeArgs($this->db, []));

        $this->assertTrue(($primary == $secondary), "getSecondary should have returned the primary database object");

        $config = [
            'database.dsn'        => 'sqlite::memory:',
            'database.username'   => null,
            'database.password'   => null,
            'database.prefix'     => 'phpunit_',
            'database.persistent' => true,
            'database.secondaries'     => [
                [
                    'dsn'      => 'sqlite::memory:',
                    'username' => null,
                    'password' => null,
                ],
            ],
        ];

        $sspConfiguration = new Configuration($config, "test/SimpleSAML/DatabaseTest.php");
        $msdb = Database::getInstance($sspConfiguration);

        $ref = new ReflectionClass($msdb);
        $dbSecondaries = $ref->getProperty('dbSecondaries');
        $dbSecondaries->setAccessible(true);
        $secondaries = $dbSecondaries->getValue($msdb);

        $getSecondary = $ref->getMethod('getSecondary');
        $getSecondary->setAccessible(true);
        $gotSecondary = spl_object_hash($getSecondary->invokeArgs($msdb, []));

        $this->assertEquals(
            spl_object_hash($secondaries[0]),
            $gotSecondary,
            "getSecondary should have returned a secondary database object"
        );
    }


    /**
     * @test
     */
    public function prefix(): void
    {
        $prefix = $this->config->getString('database.prefix');
        $table = "saml20_idp_hosted";
        $pftable = $this->db->applyPrefix($table);

        $this->assertEquals($prefix . $table, $pftable, "Did not properly apply the table prefix");
    }

    /**
     * @test
     */
    public function testGetDriver(): void
    {
        $this->assertEquals('sqlite', $this->db->getDriver());
    }

    /**
     * @test
     */
    public function querying(): void
    {
        $table = $this->db->applyPrefix("sspdbt");
        $this->assertEquals($this->config->getString('database.prefix') . "sspdbt", $table);

        $this->db->write(
            "CREATE TABLE IF NOT EXISTS $table (ssp_key INT(16) NOT NULL, ssp_value TEXT NOT NULL)"
        );

        $query1 = $this->db->read("SELECT * FROM $table");
        $this->assertEquals(0, $query1->fetch(), "Table $table is not empty when it should be.");

        $ssp_key = time();
        $ssp_value = md5(strval(rand(0, 10000)));
        $stmt = $this->db->write(
            "INSERT INTO $table (ssp_key, ssp_value) VALUES (:ssp_key, :ssp_value)",
            ['ssp_key' => [$ssp_key, PDO::PARAM_INT], 'ssp_value' => $ssp_value]
        );
        $this->assertEquals(1, $stmt, "Could not insert data into $table.");

        $query2 = $this->db->read("SELECT * FROM $table WHERE ssp_key = :ssp_key", ['ssp_key' => $ssp_key]);
        $data = $query2->fetch();
        $this->assertEquals($data['ssp_value'], $ssp_value, "Inserted data doesn't match what is in the database");
    }


    /**
     * @test
     */
    public function readFailure(): void
    {
        $this->expectException(Exception::class);
        $table = $this->db->applyPrefix("sspdbt");
        $this->assertEquals($this->config->getString('database.prefix') . "sspdbt", $table);

        $this->db->read("SELECT * FROM $table");
    }


    /**
     * @test
     */
    public function noSuchTable(): void
    {
        $this->expectException(Exception::class);
        $this->db->write("DROP TABLE phpunit_nonexistent");
    }


    /**
     */
    public function tearDown(): void
    {
        $table = $this->db->applyPrefix("sspdbt");
        $this->db->write("DROP TABLE IF EXISTS $table");

        unset($this->config);
        unset($this->db);
    }
}
