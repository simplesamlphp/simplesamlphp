<?php

declare(strict_types=1);

namespace SimpleSAML;

use Exception;
use PDO;
use PDOException;
use PDOStatement;

use function count;
use function is_array;
use function rand;
use function serialize;
use function sha1;

/**
 * This file implements functions to read and write to a group of database servers.
 *
 * This database class supports a single database, or a primary/secondary configuration with as many defined secondaries
 * as a user would like.
 *
 * The goal of this class is to provide a single mechanism to connect to a database that can be reused by any component
 * within SimpleSAMLphp including modules. When using this class, the global configuration should be passed here, but in
 * the case of a module that has a good reason to use a different database, such as sqlauth, an alternative config file
 * can be provided.
 *
 * @package SimpleSAMLphp
 */

class Database
{
    /**
     * This variable holds the instance of the session - Singleton approach.
     * @var \SimpleSAML\Database[]
     */
    private static array $instance = [];

    /**
     * PDO Object for the Primary database server
     */
    private PDO $dbPrimary;

    /**
     * Array of PDO Objects for configured database secondaries
     * @var \PDO[]
     */
    private array $dbSecondaries = [];

    /**
     * Prefix to apply to the tables
     */
    private string $tablePrefix;

    /**
     * Array with information on the last error occurred.
     */
    private array $lastError = [];


    /**
     * Retrieves the current database instance. Will create a new one if there isn't an existing connection.
     *
     * @param \SimpleSAML\Configuration|null $altConfig Optional: Instance of a \SimpleSAML\Configuration class
     *
     * @return \SimpleSAML\Database The shared database connection.
     */
    public static function getInstance(?Configuration $altConfig = null): Database
    {
        $config = ($altConfig) ? $altConfig : Configuration::getInstance();
        $instanceId = self::generateInstanceId($config);

        // check if we already have initialized the session
        if (isset(self::$instance[$instanceId])) {
            return self::$instance[$instanceId];
        }

        // create a new session
        self::$instance[$instanceId] = new Database($config);
        return self::$instance[$instanceId];
    }


    /**
     * Private constructor that restricts instantiation to getInstance().
     *
     * @param \SimpleSAML\Configuration $config Instance of the \SimpleSAML\Configuration class
     */
    private function __construct(Configuration $config)
    {
        $driverOptions = $config->getOptionalArray('database.driver_options', []);
        if ($config->getOptionalBoolean('database.persistent', true)) {
            $driverOptions[PDO::ATTR_PERSISTENT] = true;
        }

        // connect to the primary
        $this->dbPrimary = $this->connect(
            $config->getString('database.dsn'),
            $config->getOptionalString('database.username', null),
            $config->getOptionalString('database.password', null),
            $driverOptions,
        );

        // connect to any configured secondaries
        $secondaries = $config->getOptionalArray('database.secondaries', []);
        foreach ($secondaries as $secondary) {
            $this->dbSecondaries[] = $this->connect(
                $secondary['dsn'],
                $secondary['username'],
                $secondary['password'],
                $driverOptions,
            );
        }
        $this->tablePrefix = $config->getOptionalString('database.prefix', '');
    }


    /**
     * Generate an Instance ID based on the database configuration.
     *
     * @param \SimpleSAML\Configuration $config Configuration class
     *
     * @return string $instanceId
     */
    private static function generateInstanceId(Configuration $config): string
    {
        $assembledConfig = [
            'primary' => [
                'database.dsn'        => $config->getString('database.dsn'),
                'database.username'   => $config->getOptionalString('database.username', null),
                'database.password'   => $config->getOptionalString('database.password', null),
                'database.prefix'     => $config->getOptionalString('database.prefix', ''),
                'database.persistent' => $config->getOptionalBoolean('database.persistent', true),
            ],

            'secondaries' => $config->getOptionalArray('database.secondaries', []),
        ];

        return sha1(serialize($assembledConfig));
    }


    /**
     * This function connects to a database.
     *
     * @param string $dsn Database connection string
     * @param string|null $username SQL user
     * @param string|null $password SQL password
     * @param array $options PDO options
     *
     * @throws \Exception If an error happens while trying to connect to the database.
     * @return \PDO object
     */
    private function connect(
        string $dsn,
        ?string $username = null,
        #[\SensitiveParameter]
        ?string $password = null,
        array $options = [],
    ): PDO {
        try {
            $db = new PDO($dsn, $username, $password, $options);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $db;
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }


    /**
     * This function randomly selects a secondary database server to query. In the event no secondaries are configured,
     * it will return the primary.
     *
     * @return \PDO object
     */
    private function getSecondary(): PDO
    {
        if (count($this->dbSecondaries) > 0) {
            $secondaryId = rand(0, count($this->dbSecondaries) - 1);
            return $this->dbSecondaries[$secondaryId];
        } else {
            return $this->dbPrimary;
        }
    }


    /**
     * This function simply applies the table prefix to a supplied table name.
     *
     * @param string $table Table to apply prefix to, if configured
     *
     * @return string Table with configured prefix
     */
    public function applyPrefix(string $table): string
    {
        return $this->tablePrefix . $table;
    }


    /**
     * This function queries the database
     *
     * @param \PDO   $db PDO object to use
     * @param string $stmt Prepared SQL statement
     * @param array  $params Parameters
     *
     * @throws \Exception If an error happens while trying to execute the query.
     * @return \PDOStatement object
     */
    private function query(PDO $db, string $stmt, array $params): PDOStatement
    {
        try {
            $query = $db->prepare($stmt);

            foreach ($params as $param => $value) {
                if (is_array($value)) {
                    $query->bindValue(":$param", $value[0], ($value[1]) ? $value[1] : PDO::PARAM_STR);
                } else {
                    $query->bindValue(":$param", $value, PDO::PARAM_STR);
                }
            }

            $query->execute();

            return $query;
        } catch (PDOException $e) {
            $this->lastError = $db->errorInfo();
            throw new Exception("Database error: " . $e->getMessage());
        }
    }


    /**
     * This function queries the database without using a prepared statement.
     *
     * @param \PDO   $db PDO object to use
     * @param string $stmt An SQL statement to execute, previously escaped.
     *
     * @throws \Exception If an error happens while trying to execute the query.
     * @return int The number of rows affected.
     */
    private function exec(PDO $db, string $stmt): int
    {
        try {
            return $db->exec($stmt);
        } catch (PDOException $e) {
            $this->lastError = $db->errorInfo();
            throw new Exception("Database error: " . $e->getMessage());
        }
    }


    /**
     * This executes queries directly on the primary.
     *
     * @param string $stmt Prepared SQL statement
     * @param array  $params Parameters
     *
     * @return int|false The number of rows affected by the query or false on error.
     */
    public function write(string $stmt, array $params = []): int|bool
    {
        return $this->query($this->dbPrimary, $stmt, $params)->rowCount();
    }


    /**
     * This executes queries on a database server that is determined by this::getSecondary().
     *
     * @param string $stmt Prepared SQL statement
     * @param array  $params Parameters
     *
     * @return \PDOStatement object
     */
    public function read(string $stmt, array $params = []): PDOStatement
    {
        $db = $this->getSecondary();

        return $this->query($db, $stmt, $params);
    }


    /**
     * Return an array with information about the last operation performed in the database.
     *
     * @return array The array with error information.
     */
    public function getLastError(): array
    {
        return $this->lastError;
    }


    /**
     * Return the name of the PDO-driver
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->dbPrimary->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
}
