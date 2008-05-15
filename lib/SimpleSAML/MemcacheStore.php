<?php 

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Memcache.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Logger.php');

/**
 * This class provides a class with behaviour similar to the $_SESSION variable.
 * Data is automatically saved on exit.
 *
 * Care should be taken when using this class to store objects. It will not detect changes to objects
 * automatically. Instead, a call to set(...) should be done to notify this class of changes.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_MemcacheStore {


	/**
	 * This variable contains the id for this data.
	 *
	 * This variable is serialized.
	 */
	private $id = NULL;


	/**
	 * This variable contains an array with all key-value pairs stored
	 * in this object.
	 *
	 * This variable is serialized.
	 */
	private $data = NULL;


	/**
	 * This variable indicates whether our shutdown function has been registered.
	 *
	 * This variable isn't serialized.
	 */
	private $shutdownFunctionRegistered = FALSE;



	/**
	 * This function is used to find an existing storage object. It will return NULL if no storage object
	 * with the given id is found.
	 *
	 * @param $id  The id of the storage object we are looking for. A id consists of lowercase
	 *             alphanumeric characters.
	 * @return The corresponding MemcacheStorage object if the data is found or NULL if it isn't found.
	 */
	public static function find($id) {
		assert(self::isValidID($id));

		$serializedData = SimpleSAML_Memcache::get($id);
		if($serializedData === NULL) {
			return NULL;
		}

		$data = unserialize($serializedData);

		if(!($data instanceof self)) {
			SimpleSAML_Logger::warning('Retrieved key from memcache did not contain a MemcacheStore object.');
			return NULL;
		}

		return $data;
	}


	/**
	 * This constructor is used to create a new storage object. The storage object will be created with the
	 * specified id and the initial content passed in the data argument.
	 *
	 * If there exists a storage object with the specified id, then it will be overwritten.
	 *
	 * @param $id    The id of the storage object.
	 * @param $data  An array containing the initial data of the storage object.
	 */
	public function __construct($id, $data = array()) {
		/* Validate arguments. */
		assert(self::isValidID($id));
		assert(is_array($data));

		$this->id = $id;
		$this->data = $data;
	}


	/**
	 * This magic function is called on serialization of this class. It returns a list of the names of the
	 * variables which should be serialized.
	 *
	 * @return List of variables which should be serialized.
	 */
	private function __sleep() {
		return array('id', 'data');
	}


	/**
	 * This function retrieves the specified key from this storage object.
	 *
	 * @param $key  The key we should retrieve the value of.
	 * @return The value of the specified key, or NULL of the key wasn't found.
	 */
	public function get($key) {
		if(!array_key_exists($key, $this->data)) {
			return NULL;
		}

		return $this->data[$key];
	}


	/**
	 * This function sets the specified key to the specified value in this
	 * storage object.
	 *
	 * @param $key    The key we should set.
	 * @param $value  The value we should set the key to.
	 */
	public function set($key, $value) {
		$this->data[$key] = $value;

		/* Register the shutdown function if it isn't registered yet. */
		if(!$this->shutdownFunctionRegistered) {
			$this->registerShutdownFunction();
		}
	}


	/**
	 * This function stores this storage object to the memcache servers.
	 */
	public function save() {
		/* Write to the memcache servers. */
		SimpleSAML_Memcache::set($this->id, serialize($this));
	}


	/**
	 * This function determines whether the argument is a valid id.
	 * A valid id is a string containing lowercase alphanumeric
	 * characters.
	 *
	 * @param $id  The id we should validate.
	 * @return  TRUE if the id is valid, FALSE otherwise.
	 */
	private static function isValidID($id) {
		if(!is_string($id)) {
			return FALSE;
		}

		if(strlen($id) < 1) {
			return FALSE;
		}

		if(preg_match('/[^0-9a-z]/', $id)) {
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * Register our shutdown function.
	 */
	private function registerShutdownFunction() {
		register_shutdown_function(array($this, 'shutdown'));
		$this->shutdownFunctionRegistered = TRUE;
	}


	/**
	 * Shutdown function. Calls save and updates flag indicating that the function has been called.
	 *
	 * This function is only public because this is a requirement of the way callbacks work in PHP.
	 */
	public function shutdown() {
		$this->save();
		$this->shutdownFunctionRegistered = FALSE;
	}

}
?>