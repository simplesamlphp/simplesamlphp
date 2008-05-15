<?php 

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/ModifiedInfo.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Memcache.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Logger.php');

/**
 * This class provides a class with behaviour similar to the $_SESSION variable.
 * Data is automatically saved on exit.
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
	 * This variable contains the serialized data which is currently
	 * stored on the memcache servers. By comparing the data which is
	 * stored against the current data, we can determine whether we
	 * should update the data.
	 *
	 * If this variable is NULL, then we need to store data to the
	 * memcache servers.
	 *
	 * This variable isn't serialized.
	 */
	private $savedData = NULL;



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

		$data->savedData = $serializedData;

		/* Add a call to save the data when we exit. */
		register_shutdown_function(array($data, 'save'));

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

		/* Add a call to save the data when we exit. */
		register_shutdown_function(array($this, 'save'));
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

		/* Set savedData to NULL. This will save time when
		 * we are going to decide whether we need to update this
		 * object on the memcache servers.
		 */
		$this->savedData = NULL;
	}


	/**
	 * This function determines whether we need to update the data which
	 * is stored on the memcache servers.
	 *
	 * If we are unable to detect a change, then we will serialize the
	 * class and compare this to the data we have cached. We do this to
	 * determine if any of the references have changed.
	 *
	 * @return TRUE if this object needs an update, FALSE if not.
	 */
	private function needUpdate() {
		/* If $savedData is NULL, then we don't have any data stored
		 * on any servers. Therefore, we need to update the data.
		 */
		if($this->savedData === NULL) {
			return TRUE;
		}

		/* Check if we need to serialize this to make sure
		 * that it hasn't changed.
		 */
		$needSer = FALSE;
		foreach($this->data as $k => $v) {
			/* We can safely ignore all values that aren't
			 * objects since they are changed with the set-method.
			 */
			if(!is_object($v)) {
				continue;
			}

			/* If this object implements ModifiedInfo, then
			 * we can query that to find out if the object has
			 * changed.
			 */
			if($v instanceof SimpleSAML_ModifiedInfo) {
				/* Check if this object is modified. If it is
				 * then we return immediately.
				 */
				if($v->isModified()) {
					return TRUE;
				}
				/* This object hasn't changed. */
				continue;
			}

			/* We have no way of knowing whether this object
			 * is changed or not. We need to serialize to check
			 * this.
			 */
			$needSer = TRUE;
		}

		/* If we don't need to serialize, then we know we haven't
		 * changed. (Any changes will have been picked up earlier.)
		 */
		if($needSer === FALSE) {
			return FALSE;
		}

		/* Calculate the serialized value of this object. */
		$serialized = serialize($this);

		/* If the serialized value of this object matches the previous
		 * serialized value, then we don't need to update the data on
		 * the servers.
		 */
		if($serialized === $this->savedData) {
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * This function stores this storage object to the memcache servers.
	 */
	public function save() {
		/* First, chech whether we need to store new data. */
		if(!$this->needUpdate()) {
			/* This object is unchanged - we don't need to commit. */
			return;
		}

		/* Serialize this object. */
		$this->savedData = serialize($this);

		/* Write to the memcache servers. */
		SimpleSAML_Memcache::set($this->id, $this->savedData);
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

}
?>