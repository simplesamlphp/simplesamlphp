<?php

/**
 * Simple SQL authentication source
 *
 * This class is an example authentication source which authenticates an user
 * against a SQL database.
 *
 * The following options are required:
 * It has the following options:
 * - dsn: The DSN which should be used to connect to the database server. Check the various
 *        database drivers in http://php.net/manual/en/pdo.drivers.php for a description of
 *        the various DSN formats.
 * - username: The username which should be used when connecting to the database server.
 * - password: The password which should be used when connecting to the database server.
 * - query: The SQL query which should be used to retrieve the user. The parameters :username
 *          and :password are available. If the username/password is incorrect, the query should
 *          return no rows. The name of the columns in resultset will be used as attribute names.
 *          If the query returns multiple rows, they will be merged into the attributes. Duplicate
 *          values and NULL values will be removed.
 *
 * Database layout used in examples:
 * CREATE TABLE users (
 *   username VARCHAR(30) NOT NULL PRIMARY KEY,
 *   password TEXT NOT NULL,
 *   name TEXT NOT NULL,
 *   email TEXT NOT NULL
 * );
 * CREATE TABLE usergroups (
 *   username TEXT REFERENCES users (username) ON DELETE CASCADE ON UPDATE CASCADE,
 *   groupname TEXT,
 *   UNIQUE(username, groupname)
 * );
 *
 * Example - simple setup, PostgreSQL server:
 * 'sql-exampleorg' => array(
 *   'sqlauth:SQL',
 *   'dsn' => 'pgsql:host=sql.example.org;port=5432;dbname=simplesaml',
 *   'username' => 'userdb',
 *   'password' => 'secretpassword',
 *   'query' => 'SELECT username, name, email FROM users WHERE username = :username AND password = :password',
 * ),
 *
 * Example - multiple groups, MySQL server:
 * 'sql-exampleorg-groups' => array(
 *   'sqlauth:SQL',
 *   'dsn' => 'mysql:host=sql.example.org;dbname=simplesaml',
 *   'username' => 'userdb',
 *   'password' => 'secretpassword',
 *   'query' => 'SELECT users.username, name, email, groupname AS groups FROM users LEFT JOIN usergroups ON users.username=usergroups.username WHERE users.username = :username AND password = :password',
 * ),
 *
 * Example query - MD5 of salt + password, stored as salt + md5(salt + password) in password-field, MySQL server:
 * SELECT username, name, email
 * FROM users
 * WHERE username = :username AND SUBSTRING(password, -32) = MD5(CONCAT(SUBSTRING(password, 1, LENGTH(password) - 32), :password))
 *
 * Example query - MD5 of salt + password, stored as salt + md5(salt + password) in password-field, PostgreSQL server:
 * SELECT username, name, email
 * FROM users
 * WHERE username = :username AND SUBSTRING(password FROM LENGTH(password) - 31) = MD5(SUBSTRING(password FROM 1 FOR LENGTH(password) - 32) || :password)
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_sqlauth_Auth_Source_SQL extends sspmod_core_Auth_UserPassBase {


	/**
	 * The DSN we should connect to.
	 */
	private $dsn;


	/**
	 * The username we should connect to the database with.
	 */
	private $username;


	/**
	 * The password we should connect to the database with.
	 */
	private $password;


	/**
	 * The query we should use to retrieve the attributes for the user.
	 *
	 * The username and password will be available as :username and :password.
	 */
	private $query;


	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		/* Make sure that all required parameters are present. */
		foreach (array('dsn', 'username', 'password', 'query') as $param) {
			if (!array_key_exists($param, $config)) {
				throw new Exception('Missing required attribute \'' . $param .
					'\' for authentication source ' . $this->authId);
			}

			if (!is_string($config[$param])) {
				throw new Exception('Expected parameter \'' . $param .
					'\' for authentication source ' . $this->authId .
					' to be a string. Instead it was: ' .
					var_export($config[$param], TRUE));
			}
		}

		$this->dsn = $config['dsn'];
		$this->username = $config['username'];
		$this->password = $config['password'];
		$this->query = $config['query'];
	}


	/**
	 * Create a database connection.
	 *
	 * @return PDO  The database connection.
	 */
	private function connect() {
		try {
			$db = new PDO($this->dsn, $this->username, $this->password);
		} catch (PDOException $e) {
			throw new Exception('sqlauth:' . $this->authId . ': - Failed to connect to \'' .
				$this->dsn . '\': '. $e->getMessage());
		}

		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


		$driver = explode(':', $this->dsn, 2);
		$driver = strtolower($driver[0]);

		/* Driver specific initialization. */
		switch ($driver) {
		case 'mysql':
			/* Use UTF-8. */
			$db->exec("SET NAMES 'utf8'");
			break;
		case 'pgsql':
			/* Use UTF-8. */
			$db->exec("SET NAMES 'UTF8'");
			break;
		}

		return $db;
	}


	/**
	 * Attempt to log in using the given username and password.
	 *
	 * On a successful login, this function should return the users attributes. On failure,
	 * it should throw an exception. If the error was caused by the user entering the wrong
	 * username or password, a SimpleSAML_Error_Error('WRONGUSERPASS') should be thrown.
	 *
	 * Note that both the username and the password are UTF-8 encoded.
	 *
	 * @param string $username  The username the user wrote.
	 * @param string $password  The password the user wrote.
	 * @return array  Associative array with the users attributes.
	 */
	protected function login($username, $password) {
		assert('is_string($username)');
		assert('is_string($password)');

		$db = $this->connect();

		try {
			$sth = $db->prepare($this->query);
		} catch (PDOException $e) {
			throw new Exception('sqlauth:' . $this->authId .
				': - Failed to prepare query: ' . $e->getMessage());
		}

		try {
			$res = $sth->execute(array('username' => $username, 'password' => $password));
		} catch (PDOException $e) {
			throw new Exception('sqlauth:' . $this->authId .
				': - Failed to execute query: ' . $e->getMessage());
		}

		try {
			$data = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			throw new Exception('sqlauth:' . $this->authId .
				': - Failed to fetch result set: ' . $e->getMessage());
		}

		SimpleSAML_Logger::info('sqlauth:' . $this->authId . ': Got ' . count($data) .
			' rows from database');

		if (count($data) === 0) {
			/* No rows returned - invalid username/password. */
			SimpleSAML_Logger::error('sqlauth:' . $this->authId .
				': No rows in result set. Probably wrong username/password.');
			throw new SimpleSAML_Error_Error('WRONGUSERPASS');
		}

		/* Extract attributes. We allow the resultset to consist of multiple rows. Attributes
		 * which are present in more than one row will become multivalued. NULL values and
		 * duplicate values will be skipped. All values will be converted to strings.
		 */
		$attributes = array();
		foreach ($data as $row) {
			foreach ($row as $name => $value) {

				if ($value === NULL) {
					continue;
				}

				$value = (string)$value;

				if (!array_key_exists($name, $attributes)) {
					$attributes[$name] = array();
				}

				if (in_array($value, $attributes[$name], TRUE)) {
					/* Value already exists in attribute. */
					continue;
				}

				$attributes[$name][] = $value;
			}
		}

		SimpleSAML_Logger::info('sqlauth:' . $this->authId . ': Attributes: ' .
			implode(',', array_keys($attributes)));

		return $attributes;
	}

}

?>