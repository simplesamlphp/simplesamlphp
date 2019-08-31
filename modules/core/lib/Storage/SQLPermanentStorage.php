<?php

namespace SimpleSAML\Module\core\Storage;

/**
 * SQLPermanentStorage
 *
 * Generic SQL Store to store key value pairs. To be used in several other modules that needs
 * to store data permanently.
 *
 * @author Andreas Åkre Solberg <andreas@uninett.no>, UNINETT AS.
 * @package SimpleSAMLphp
 */

class SQLPermanentStorage
{
    private $db;

    public function __construct($name, $config = null)
    {
        if (is_null($config)) {
            $config = \SimpleSAML\Configuration::getInstance();
        }

        $datadir = $config->getPathValue('datadir', 'data/');

        if (!is_dir($datadir)) {
            throw new \Exception('Data directory ['.$datadir.'] does not exist');
        } elseif (!is_writable($datadir)) {
            throw new \Exception('Data directory ['.$datadir.'] is not writable');
        }

        $sqllitedir = $datadir.'sqllite/';
        if (!is_dir($sqllitedir)) {
            mkdir($sqllitedir);
        }

        $dbfile = 'sqlite:'.$sqllitedir.$name.'.sqlite';
        if ($this->db = new \PDO($dbfile)) {
            $q = @$this->db->query('SELECT key1 FROM data LIMIT 1');
            if ($q === false) {
                $this->db->exec('
		    CREATE TABLE data (
                        key1 text, 
                        key2 text,
                        type text,
                        value text,
                        created timestamp,
                        updated timestamp,
                        expire timestamp,
                        PRIMARY KEY (key1,key2,type)
                    );
                ');
            }
        } else {
            throw new \Exception('Error creating SQL lite database ['.$dbfile.'].');
        }
    }

    public function set($type, $key1, $key2, $value, $duration = null)
    {
        if ($this->exists($type, $key1, $key2)) {
            $this->update($type, $key1, $key2, $value, $duration);
        } else {
            $this->insert($type, $key1, $key2, $value, $duration);
        }
    }

    private function insert($type, $key1, $key2, $value, $duration = null)
    {
        $expire = is_null($duration) ? null : (time() + $duration);

        $query = "INSERT INTO data (key1, key2, type, created, updated, expire, value)".
            " VALUES(:key1, :key2, :type, :created, :updated, :expire, :value)";
        $prepared = $this->db->prepare($query);
        $data = [':key1' => $key1, ':key2' => $key2,
            ':type' => $type, ':created' => time(),
            ':updated' => time(), ':expire' => $expire,
            ':value' => serialize($value)];
        $prepared->execute($data);
        $results = $prepared->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    private function update($type, $key1, $key2, $value, $duration = null)
    {
        $expire = is_null($duration) ? null : (time() + $duration);

        $query = "UPDATE data SET updated = :updated, value = :value, ".
            "expire = :expire WHERE key1 = :key1 AND key2 = :key2 AND type = :type";
        $prepared = $this->db->prepare($query);
        $data = [':key1' => $key1, ':key2' => $key2,
            ':type' => $type, ':updated' => time(),
            ':expire' => $expire, ':value' => serialize($value)];
        $prepared->execute($data);
        $results = $prepared->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function get($type = null, $key1 = null, $key2 = null)
    {
        $conditions = $this->getCondition($type, $key1, $key2);
        $query = 'SELECT * FROM data WHERE '.$conditions;

        $prepared = $this->db->prepare($query);
        $prepared->execute();
        $results = $prepared->fetchAll(\PDO::FETCH_ASSOC);
        if (count($results) !== 1) {
            return null;
        }

        $res = $results[0];
        $res['value'] = unserialize($res['value']);
        return $res;
    }

    /*
     * Return the value directly (not in a container)
     */
    public function getValue($type = null, $key1 = null, $key2 = null)
    {
        $res = $this->get($type, $key1, $key2);
        if ($res === null) {
            return null;
        }
        return $res['value'];
    }

    public function exists($type, $key1, $key2)
    {
        $query = 'SELECT * FROM data WHERE type = :type AND key1 = :key1 AND key2 = :key2 LIMIT 1';
        $prepared = $this->db->prepare($query);
        $data = [':type' => $type, ':key1' => $key1, ':key2' => $key2];
        $prepared->execute($data);
        $results = $prepared->fetchAll(\PDO::FETCH_ASSOC);
        return (count($results) == 1);
    }

    public function getList($type = null, $key1 = null, $key2 = null)
    {
        $conditions = $this->getCondition($type, $key1, $key2);
        $query = 'SELECT * FROM data WHERE '.$conditions;
        $prepared = $this->db->prepare($query);
        $prepared->execute();

        $results = $prepared->fetchAll(\PDO::FETCH_ASSOC);
        if (count($results) == 0) {
            return null;
        }

        foreach ($results as $key => $value) {
            $results[$key]['value'] = unserialize($results[$key]['value']);
        }
        return $results;
    }

    public function getKeys($type = null, $key1 = null, $key2 = null, $whichKey = 'type')
    {
        if (!in_array($whichKey, ['key1', 'key2', 'type'], true)) {
            throw new \Exception('Invalid key type');
        }

        $conditions = $this->getCondition($type, $key1, $key2);
        $query = 'SELECT DISTINCT :whichKey FROM data WHERE '.$conditions;
        $prepared = $this->db->prepare($query);
        $data = ['whichKey' => $whichKey];
        $prepared->execute($data);
        $results = $prepared->fetchAll(\PDO::FETCH_ASSOC);

        if (count($results) == 0) {
            return null;
        }

        $resarray = [];
        foreach ($results as $key => $value) {
            $resarray[] = $value[$whichKey];
        }
        return $resarray;
    }

    public function remove($type, $key1, $key2)
    {
        $query = 'DELETE FROM data WHERE type = :type AND key1 = :key1 AND key2 = :key2';
        $prepared = $this->db->prepare($query);
        $data = [':type' => $type, ':key1' => $key1, ':key2' => $key2];
        $prepared->execute($data);
        $results = $prepared->fetchAll(\PDO::FETCH_ASSOC);
        return (count($results) == 1);
    }

    public function removeExpired()
    {
        $query = "DELETE FROM data WHERE expire IS NOT NULL AND expire < :expire";
        $prepared = $this->db->prepare($query);
        $data = [':expire' => time()];
        $prepared->execute($data);
        return $prepared->rowCount();
    }

    /**
     * Create a SQL condition statement based on parameters
     */
    private function getCondition($type = null, $key1 = null, $key2 = null)
    {
        $conditions = [];
        if (!is_null($type)) {
            $conditions[] = "type = ".$this->db->quote($type);
        }
        if (!is_null($key1)) {
            $conditions[] = "key1 = ".$this->db->quote($key1);
        }
        if (!is_null($key2)) {
            $conditions[] = "key2 = ".$this->db->quote($key2);
        }

        $conditions[] = "(expire IS NULL OR expire >= ".time().")";
        return join(' AND ', $conditions);
    }
}
