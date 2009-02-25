<?php

/**
 * Store consent in database.
 *
 * This class implements a consent store which stores the consent information in
 * a database. It is tested, and should work against both MySQL and PostgreSQL.
 *
 * It has the following options:
 * - dsn: The DSN which should be used to connect to the database server. Check the various
 *        database drivers in http://php.net/manual/en/pdo.drivers.php for a description of
 *        the various DSN formats.
 * - username: The username which should be used when connecting to the database server.
 * - password: The password which should be used when connecting to the database server.
 * - table: The name of the table. Optional, defaults to 'consent'.
 *
 * Example - consent module with MySQL database:
 * <code>
 * 'authproc' => array(
 *   array(
 *     'consent:Consent',
 *     'store' => array(
 *       'consent:Database',
 *       'dsn' => 'mysql:host=db.example.org;dbname=simplesaml',
 *       'username' => 'simplesaml',
 *       'password' => 'secretpassword',
 *       ),
 *     ),
 *   ),
 * </code>
 *
 * Example - consent module with PostgreSQL database:
 * <code>
 * 'authproc' => array(
 *   array(
 *     'consent:Consent',
 *     'store' => array(
 *       'consent:Database',
 *       'dsn' => 'pgsql:host=db.example.org;port=5432;dbname=simplesaml',
 *       'username' => 'simplesaml',
 *       'password' => 'secretpassword',
 *       ),
 *     ),
 *   ),
 * </code>
 *
 *
 * Table declaration:
 * CREATE TABLE consent (
 *   consent_date TIMESTAMP NOT NULL,
 *   usage_date TIMESTAMP NOT NULL,
 *   hashed_user_id VARCHAR(80) NOT NULL,
 *   service_id VARCHAR(255) NOT NULL,
 *   attribute VARCHAR(80) NOT NULL,
 *   UNIQUE (hashed_user_id, service_id)
 * );
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_consent_Consent_Store_Database extends sspmod_consent_Store {


	/**
	 * DSN for the database.
	 */
	private $dsn;


	/**
	 * Username for the database.
	 */
	private $username;


	/**
	 * Password for the database;
	 */
	private $password;


	/**
	 * Table with consent.
	 */
	private $table;


	/**
	 * Database handle.
	 *
	 * This variable can't be serialized.
	 */
	private $db;


	/**
	 * Parse configuration.
	 *
	 * This constructor parses the configuration.
	 *
	 * @param array $config  Configuration for database consent store.
	 */
	public function __construct($config) {
		parent::__construct($config);

		foreach (array('dsn', 'username', 'password') as $id) {
			if (!array_key_exists($id, $config)) {
				throw new Exception('consent:Database - Missing required option \'' . $id . '\'.');
			}
			if (!is_string($config[$id])) {
				throw new Exception('consent:Database - \'' . $id . '\' is supposed to be a string.');
			}
		}

		$this->dsn = $config['dsn'];
		$this->username = $config['username'];
		$this->password = $config['password'];

		if (array_key_exists('table', $config)) {
			if (!is_string($config['table'])) {
				throw new Exception('consent:Database - \'table\' is supposed to be a string.');
			}
			$this->table = $config['table'];
		} else {
			$this->table = 'consent';
		}
		
		$db = $this->getDB();
	}


	/**
	 * Called before serialization.
	 *
	 * @return array  The variables which should be serialized.
	 */
	public function __sleep() {

		return array(
			'dsn',
			'username',
			'password',
			'table',
			);
	}


	/**
	 * Check for consent.
	 *
	 * This function checks whether a given user has authorized the release of the attributes
	 * identified by $attributeSet from $source to $destination.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 * @param string $destinationId  A string which identifies the destination.
	 * @param string $attributeSet  A hash which identifies the attributes.
	 * @return bool  TRUE if the user has given consent earlier, FALSE if not (or on error).
	 */
	public function hasConsent($userId, $destinationId, $attributeSet) {
		assert('is_string($userId)');
		assert('is_string($destinationId)');
		assert('is_string($attributeSet)');

		$st = $this->execute('UPDATE ' . $this->table . ' SET usage_date = NOW() WHERE hashed_user_id = ? AND service_id = ? AND attribute = ?',
			array($userId, $destinationId, $attributeSet));
		if ($st === FALSE) {
			return FALSE;
		}

		$rowCount = $st->rowCount();
		if ($rowCount === 0) {
			SimpleSAML_Logger::debug('consent:Database - No consent found.');
			return FALSE;
		} else {
			SimpleSAML_Logger::debug('consent:Database - Consent found.');
			return TRUE;
		}

	}


	/**
	 * Save consent.
	 *
	 * Called when the user asks for the consent to be saved. If consent information
	 * for the given user and destination already exists, it should be overwritten.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 * @param string $destinationId  A string which identifies the destination.
	 * @param string $attributeSet  A hash which identifies the attributes.
	 */
	public function saveConsent($userId, $destinationId, $attributeSet) {
		assert('is_string($userId)');
		assert('is_string($destinationId)');
		assert('is_string($attributeSet)');

		/* Check for old consent (with different attribute set). */
		$st = $this->execute('UPDATE ' . $this->table . ' SET consent_date = NOW(), usage_date = NOW(), attribute = ? WHERE hashed_user_id = ? AND service_id = ?',
			array($attributeSet, $userId, $destinationId));
		if ($st === FALSE) {
			return;
		}
		if ($st->rowCount() > 0) {
			/* We had already stored consent for the given destination in the database. */
			SimpleSAML_Logger::debug('consent:Database - Updated old consent.');
			return;
		}

		/* Add new consent. We don't check for error since there is nothing we can do if one occurs. */
		$st = $this->execute('INSERT INTO ' . $this->table . ' (consent_date, usage_date, hashed_user_id, service_id, attribute) VALUES(NOW(),NOW(),?,?,?)',
			array($userId, $destinationId, $attributeSet));
		if ($st !== FALSE) {
			SimpleSAML_Logger::debug('consent:Database - Saved new consent.');
		}
		return TRUE;
	}


	/**
	 * Delete consent.
	 *
	 * Called when a user revokes consent for a given destination.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 * @param string $destinationId  A string which identifies the destination.
	 */
	public function deleteConsent($userId, $destinationId) {
		assert('is_string($userId)');
		assert('is_string($destinationId)');

		$st = $this->execute('DELETE FROM ' . $this->table . ' WHERE hashed_user_id = ? and service_id = ?',
			array($userId, $destinationId));
		if ($st === FALSE) {
			return;
		}

		if ($st->rowCount() > 0) {
			SimpleSAML_Logger::debug('consent:Database - Deleted consent.');
			return $st->rowCount();
		} else {
			SimpleSAML_Logger::warning('consent:Database - Attempted to delete nonexistent consent');
		}
	}

	/**
	 * Delete all consents.
 	 * 
	 * @param string $userId  The hash identifying the user at an IdP.
	 */
	public function deleteAllConsents($userId) {
		assert('is_string($userId)');

		$st = $this->execute('DELETE FROM ' . $this->table . ' WHERE hashed_user_id = ?', array($userId));
		if ($st === FALSE) return;

		if ($st->rowCount() > 0) {
			SimpleSAML_Logger::debug('consent:Database - Deleted (' . $st->rowCount() . ') consent(s).');
			return $st->rowCount();
		} else {
			SimpleSAML_Logger::warning('consent:Database - Attempted to delete nonexistent consent');
		}
	}


	/**
	 * Retrieve consents.
	 *
	 * This function should return a list of consents the user has saved.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 * @return array  Array of all destination ids the user has given consent for.
	 */
	public function getConsents($userId) {
		assert('is_string($userId)');

		$ret = array();

		$st = $this->execute('SELECT service_id, attribute, consent_date, usage_date FROM ' . $this->table . ' WHERE hashed_user_id = ?',
			array($userId));
		if ($st === FALSE) {
			return array();
		}

		while ($row = $st->fetch(PDO::FETCH_NUM)) {
			$ret[] = $row;
		}

		return $ret;
	}


	/**
	 * Prepare and execute statement.
	 *
	 * This function prepares and executes a statement. On error, FALSE will be returned.
	 *
	 * @param string $statement  The statement which should be executed.
	 * @param array $parameters  Parameters for the statement.
	 * @return PDOStatement|FALSE  The statement, or FALSE if execution failed.
	 */
	private function execute($statement, $parameters) {
		assert('is_string($statement)');
		assert('is_array($parameters)');

		$db = $this->getDB();
		if ($db === FALSE) {
			return FALSE;
		}

		$st = $db->prepare($statement);
		if ($st === FALSE) {
			if ($st === FALSE) {
				SimpleSAML_Logger::error('consent:Database - Error preparing statement \'' .
					$statement . '\': ' . self::formatError($db->errorInfo()));
				return FALSE;
			}
		}

		if ($st->execute($parameters) !== TRUE) {
			SimpleSAML_Logger::error('consent:Database - Error executing statement \'' .
				$statement . '\': ' . self::formatError($st->errorInfo()));
			return FALSE;
		}

		return $st;
	}


	/**
	 * get statistics
	 *
	 */
	public function getStatistics() {
		$ret = array();

		$st = $this->execute('select count(*) as no from consent', array());
		if ($st === FALSE) return array(); 
		if($row = $st->fetch(PDO::FETCH_NUM)) $ret['total'] = $row[0];

		$st = $this->execute('select count(*) as no from (select distinct hashed_user_id from consent ) as foo', array());
		if ($st === FALSE) return array(); 
		if($row = $st->fetch(PDO::FETCH_NUM)) $ret['users'] = $row[0];

		$st = $this->execute('select count(*) as no from (select distinct service_id from consent ) as foo', array());
		if ($st === FALSE) return array();
		if($row = $st->fetch(PDO::FETCH_NUM)) $ret['services'] = $row[0];

		return $ret;
	}
	
	
	
	
	/**
	 * Create consent table.
	 *
	 * This function creates the table with consent data.
	 *
	 * @return TRUE if successful, FALSE if not.
	 */
	private function createTable() {

		$db = $this->getDB();
		if ($db === FALSE) {
			return FALSE;
		}

		$res = $this->db->exec(
			'CREATE TABLE ' . $this->table . ' (' .
			'consent_date TIMESTAMP NOT NULL,' .
			'usage_date TIMESTAMP NOT NULL,' .
			'hashed_user_id VARCHAR(80) NOT NULL,' .
			'service_id VARCHAR(255) NOT NULL,' .
			'attribute VARCHAR(80) NOT NULL,' .
			'UNIQUE (hashed_user_id, service_id)' .
			')');
		if ($res === FALSE) {
			SimpleSAML_Logger::error('consent:Database - Failed to create table \'' . $this->table . '\'.');
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * Get database handle.
	 *
	 * @return PDO|FALSE  Database handle, or FALSE if we fail to connect.
	 */
	private function getDB() {
		if ($this->db !== NULL) {
			return $this->db;
		}

		//try {
		$this->db = new PDO($this->dsn, $this->username, $this->password);
		// 		} catch (PDOException $e) {
		// 			SimpleSAML_Logger::error('consent:Database - Failed to connect to \'' .
		// 				$this->dsn . '\': '. $e->getMessage());
		// 			$this->db = FALSE;
		// 		}

		return $this->db;
	}


	/**
	 * Format PDO error.
	 *
	 * This function formats a PDO error, as returned from errorInfo.
	 *
	 * @param array $error  The error information.
	 * @return string  Error text.
	 */
	private static function formatError($error) {
		assert('is_array($error)');
		assert('count($error) >= 3');

		return $error[0] . ' - ' . $error[2] . ' (' . $error[1] . ')';
	}

}

?>