<?php
namespace SimpleSAML;

/**
 * This file implements functions to read and write to a group of database
 * servers.
 *
 * This database class supports a single database, or a master/slave
 * configuration with as many defined slaves as a user would like.
 * 
 * The goal of this class is to provide a single mechanism to connect to a database
 * that can be reused by any component within SimpleSAMLphp including modules.
 * When using this class, the global configuration should be passed here, but
 * in the case of a module that has a good reason to use a different database,
 * such as sqlauth, an alternative config file can be provided.
 *
 * @author Tyler Antonio, University of Alberta. <tantonio@ualberta.ca>
 * @package SimpleSAMLphp
 */

class Database {

	/**
	 * This variable holds the instance of the session - Singleton approach.
	 */
	private static $instance = array();

	/**
	 * PDO Object for the Master database server
	 */
	private $dbMaster;

	/**
	 * Array of PDO Objects for configured database
	 * slaves
	 */
	private $dbSlaves = array();

	/**
	 * Prefix to apply to the tables
	 */
	private $tablePrefix;

    /**
	 * Retrieves the current database instance. Will create a new one if there isn't an existing connection.
	 *
	 * @param object $altConfig Optional: Instance of a SimpleSAML_Configuration class
	 * @return SimpleSAML_Database The shared database connection.
	 */
	public static function getInstance($altConfig = null) {
		$config = ($altConfig)? $altConfig : \SimpleSAML_Configuration::getInstance();
		$instanceId = self::generateInstanceId($config);

		/* Check if we already have initialized the session. */
		if (isset(self::$instance[$instanceId])) {
			return self::$instance[$instanceId];
		}

		/* Create a new session. */
		self::$instance[$instanceId] = new Database($config);
		return self::$instance[$instanceId];
	}

	/**
	 * Private constructor that restricts instantiation to getInstance().
	 *
	 * @param object $config Instance of the SimpleSAML_Configuration class
	 */
	private function __construct($config) {
		$driverOptions = array();
		if ($config->getBoolean('database.persistent', TRUE)) {
			$driverOptions = array(\PDO::ATTR_PERSISTENT => TRUE);
		}

		// Connect to the master
		$this->dbMaster = $this->connect($config->getValue('database.dsn'), $config->getValue('database.username'), $config->getValue('database.password'), $driverOptions);

		// Connect to any configured slaves
		$slaves = $config->getValue('database.slaves');
		if (count($slaves >= 1)) {
			foreach ($slaves as $slave) {
				array_push($this->dbSlaves, $this->connect($slave['dsn'], $slave['username'], $slave['password'], $driverOptions));
			}
		}

		$this->tablePrefix = $config->getString('database.prefix', '');
	}

	/**
	 * Generate an Instance ID based on the database
	 * configuration.
	 *
	 * @param $config 			Configuration class
	 *
	 * @return string $instanceId
	 */
	private static function generateInstanceId($config){
		$assembledConfig = array(
			'master' => array(
				'database.dsn' => $config->getValue('database.dsn'),
				'database.username' => $config->getValue('database.username'),
				'database.password' => $config->getValue('database.password'),
				'database.prefix' => $config->getValue('database.prefix'),
				'database.persistent' => $config->getValue('database.persistent'),
			),
			'slaves' => $config->getValue('database.slaves'),
		);

		return sha1(serialize($assembledConfig));
	}

	/**
	 * This function connects to a dabase.
	 *
	 * @param $dsn 			Database connection string
	 * @param $username 	SQL user
	 * @param $password 	SQL password
	 * @param $options 		PDO options
	 *
	 * @return PDO object
	 */
	private function connect($dsn, $username, $password, $options){
		try{
			$db = new \PDO($dsn, $username, $password, $options);
			$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

			return $db;
		} catch(PDOException $e){
			throw new DBException("Database error: ". $e->getMessage());
		}
	}

	/**
	 * This function randomly selects a slave database server
	 * to query. In the event no slaves are configured, it
	 * will return the master.
	 *
	 * @return PDO object
	 */
	private function getSlave(){
		if (count($this->dbSlaves) > 0) {
			$slaveId = rand(0,count($this->dbSlaves)-1);
			return $this->dbSlaves[$slaveId];
		} else {
			return $this->dbMaster;
		}
	}

	/**
	 * This function simply applies the table prefix to
	 * a suppled table name.
	 *
	 * @param $table Table to apply prefix,if configured
	 * @return string Table with configured prefix
	 */
	public function applyPrefix($table){
		return $this->tablePrefix . $table;
	}

	/**
	 * This function queries the database
	 *
	 * @param $db 			PDO object to use
	 * @param $stmt 		Prepared SQL statement
	 * @param $params 		Parameters
	 *
	 * @return PDO statement object
	 */
	private function query($db, $stmt, $params){
		assert('is_object($db)');
		assert('is_string($stmt)');
		assert('is_array($params)');

		try{
			$query = $db->prepare($stmt);

			foreach ($params as $param => $value) {
				if(is_array($value)){
					$query->bindValue(":$param", $value[0], ($value[1])? $value[1] : \PDO::PARAM_STR);
				}
				else{
					$query->bindValue(":$param", $value, \PDO::PARAM_STR);
				}
			}

			$query->execute();

			return $query;
		} catch (PDOException $e){
			throw new DBException("Database error: ". $e->getMessage());
		}
	}

	/**
	 * This function queries the database without using a
	 * prepared statement.
	 *
	 * @param $db 			PDO object to use
	 * @param $stmt 		Prepared SQL statement
	 * @param $params 		Parameters
	 *
	 * @return PDO statement object
	 */
	private function exec($db, $stmt){
		assert('is_object($db)');
		assert('is_string($stmt)');

		try{
			$query = $db->exec($stmt);

			return $query;
		} catch (PDOException $e){
			throw new DBException("Database error: ". $e->getMessage());
		}
	}

	/**
	 * This executes queries directly on the master.
	 *
	 * @param $stmt 		Prepared SQL statement
	 * @param $params 		Parameters
	 *
	 * @return PDO statement object
	 */
	public function write($stmt, $params = array()){
		$db = $this->dbMaster;

		if (is_array($params)) {
			return $this->query($db, $stmt, $params);
		} else {
			return $this->exec($db, $stmt);
		}
	}

	/**
	 * This executes queries on a database server
	 * that is determined by this::getSlave()
	 *
	 * @param $stmt 		Prepared SQL statement
	 * @param $params 		Parameters
	 *
	 * @return PDO statement object
	 */
	public function read($stmt, $params = array()){
		$db = $this->getSlave();

		return $this->query($db, $stmt, $params);
	}
}
