<?php
/**
 * This test ensures that the SimpleSAML_Database class can properly
 * query a database.
 *
 * It currently uses sqlite to test, but an alternate config.php file
 * should be created for test cases to ensure that it will work
 * in an environment.
 *
 * @author Tyler Antonio, University of Alberta. <tantonio@ualberta.ca>
 * @package simpleSAMLphp
 */

class SimpleSAML_DatabaseTest extends PHPUnit_Framework_TestCase
{
	protected $config;
	protected $db;

	/**
	 * Make protected functions available for testing
	 * @requires PHP 5.3.2
	 */
	protected static function getMethod($getMethod) {
		$class = new ReflectionClass('SimpleSAML_Database');
		$method = $class->getMethod($getMethod);
		$method->setAccessible(true);
		return $method;
	}

	/**
	 * @covers SimpleSAML_Database::getInstance
	 * @covers SimpleSAML_Database::__construct
	 * @covers SimpleSAML_Database::connect
	 */
	public function setUp()
	{
		$config = array(
			'database.dsn' => 'sqlite::memory:',
			'database.username' => null,
			'database.password' => null,
			'database.prefix' => 'phpunit_',
			'database.persistent' => true,
			'database.slaves' => array(),
		);

		$this->config = new SimpleSAML_Configuration($config, "test/SimpleSAML/DatabaseTest.php");

		// Ensure that we have a functional configuration class.
		$this->assertInstanceOf('SimpleSAML_Configuration', $this->config);
		$this->assertEquals($config['database.dsn'], $this->config->getValue('database.dsn'));

		$this->db = SimpleSAML_Database::getInstance($this->config);

		// Ensure that we have a functional database class.
		$this->assertInstanceOf('SimpleSAML_Database', $this->db);
	}

	/**
	 * @covers SimpleSAML_Database::getInstance
	 * @covers SimpleSAML_Database::__construct
	 * @covers SimpleSAML_Database::connect
	 * @test
	 */
	public function Instances()
	{
		$config = array(
			'database.dsn' => 'sqlite::memory:',
			'database.username' => null,
			'database.password' => null,
			'database.prefix' => 'phpunit_',
			'database.persistent' => true,
			'database.slaves' => array(),
		);
		$config2 = array(
			'database.dsn' => 'sqlite::memory:',
			'database.username' => null,
			'database.password' => null,
			'database.prefix' => 'phpunit2_',
			'database.persistent' => true,
			'database.slaves' => array(),
		);

		$config1 = new SimpleSAML_Configuration($config, "test/SimpleSAML/DatabaseTest.php");
		$config2 = new SimpleSAML_Configuration($config2, "test/SimpleSAML/DatabaseTest.php");
		$config3 = new SimpleSAML_Configuration($config, "test/SimpleSAML/DatabaseTest.php");

		$db1 = SimpleSAML_Database::getInstance($config1);
		$db2 = SimpleSAML_Database::getInstance($config2);
		$db3 = SimpleSAML_Database::getInstance($config3);

		// Assert that $db1 and $db2 are different instances
		$this->assertFalse((spl_object_hash($db1) == spl_object_hash($db2)), "Database instances should be different, but returned the same spl_object_hash");
		// Assert that $db1 and $db3 are identical instances
		$this->assertTrue((spl_object_hash($db1) == spl_object_hash($db3)), "Database instances should be the same, but returned different spl_object_hash");
	}

	/**
	 * @covers SimpleSAML_Database::getSlave
	 * @test
	 */
	public function Slaves(){
		$getSlave = self::getMethod('getSlave');
		
		$master = spl_object_hash(PHPUnit_Framework_Assert::readAttribute($this->db, 'dbMaster'));
		$slave = spl_object_hash($getSlave->invokeArgs($this->db, array()));

		$this->assertTrue(($master == $slave), "getSlave should have returned the master database object");
	}

	/**
	 * @covers SimpleSAML_Database::applyPrefix
	 * @test
	 */
	public function prefix(){
		$prefix = $this->config->getValue('database.prefix');
		$table = "saml20_idp_hosted";
		$pftable = $this->db->applyPrefix($table);

		$this->assertEquals($prefix . $table, $pftable, "Did not properly apply the table prefix");
	}

	/**
	 * @covers SimpleSAML_Database::write
	 * @covers SimpleSAML_Database::read
	 * @covers SimpleSAML_Database::exec
	 * @covers SimpleSAML_Database::query
	 * @test
	 */
	public function Querying()
	{
		$table = $this->db->applyPrefix("sspdbt");
		$this->assertEquals($this->config->getValue('database.prefix') . "sspdbt", $table);

		$this->db->write("CREATE TABLE IF NOT EXISTS $table (ssp_key VARCHAR(255) NOT NULL, ssp_value TEXT NOT NULL)", false);

		$query1 = $this->db->read("SELECT * FROM $table");
		$this->assertEquals(0, $query1->fetch(), "Table $table is not empty when it should be.");

		$ssp_key = "test_" . time();
		$ssp_value = md5(rand(0,10000));
		$stmt = $this->db->write("INSERT INTO $table (ssp_key, ssp_value) VALUES (:ssp_key, :ssp_value)", array('ssp_key' => $ssp_key, 'ssp_value' => $ssp_value));
		$this->assertEquals(1, $stmt->rowCount(), "Could not insert data into $table.");

		$query2 = $this->db->read("SELECT * FROM $table WHERE ssp_key = :ssp_key", array('ssp_key' => $ssp_key));
		$data = $query2->fetch();
		$this->assertEquals($data['ssp_value'], $ssp_value, "Inserted data doesn't match what is in the database");
	}

	/**
	 * @covers SimpleSAML_Database::write
	 * @expectedException Exception
	 * @test
	 */
	public function NoSuchTable()
	{
		$this->db->write("DROP TABLE phpunit_nonexistant", false);
	}

	public function tearDown()
	{
		$table = $this->db->applyPrefix("sspdbt");
		$this->db->write("DROP TABLE IF EXISTS $table", false);

		unset($this->config);
		unset($this->db);
	}
}