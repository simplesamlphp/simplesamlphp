<?php

/**
 * The Consent Storage class is used for storing Attribute Release consents.
 *
 * CREATE TABLE consent ( 
 *	hashed_user_id varchar(128) NOT NULL, 
 *	service_id varchar(128) NOT NULL, 
 *	attribute varchar(128) NOT NULL, 
 *	consent_date datetime NOT NULL, 
 *	usage_date datetime NOT NULL, 
 *	PRIMARY KEY USING BTREE (hashed_user_id, service_id) 
 * );
 *
 * @author Mads, Lasse, David, Peter and Andreas.
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Consent_Storage {

	private $config;
	private $dbh;
		
	/**
	 * Constructor
	 */
	public function __construct($config) {

		$this->config = $config;
		
		$pdo_connect = $config->getValue('consent_pdo_connect');
		$pdo_user    = $config->getValue('consent_pdo_user');
		$pdo_passwd  = $config->getValue('consent_pdo_passwd');
		
		try {
			$this->dbh = new PDO($pdo_connect, $pdo_user, $pdo_passwd);
		} catch(Exception $exception) {
			$session = SimpleSAML_Session::getInstance();
			SimpleSAML_Utilities::fatalError($session->getTrackID(), 'PROCESSASSERTION', $exception);
		}
		//$this->dbh->setAttribute('PDO::ATTR_TIMEOUT', 5);

	}


	/**
	 * Lookup consent database for an entry, and update the timestamp.
	 *
	 * @return Will return true if consent is stored, and false if consent is not stored.
	 */
	public function lookup($user_id, $targeted_id, $attribute_hash) {
		$stmt = $this->dbh->prepare("UPDATE consent SET usage_date = NOW() WHERE hashed_user_id = ? AND service_id = ? AND attribute = ?");
		$stmt->execute(array($user_id, $targeted_id, $attribute_hash));
		$rows = $stmt->rowCount();
		
		SimpleSAML_Logger::debug('Library - ConsentStorage get(): user_id        : ' . $user_id);
		SimpleSAML_Logger::debug('Library - ConsentStorage get(): targeted_id    : ' . $targeted_id);
		SimpleSAML_Logger::debug('Library - ConsentStorage get(): attribute_hash : ' . $attribute_hash);
		
		SimpleSAML_Logger::debug('Library - ConsentStorage get(): Number of rows : [' . $rows . ']');
		
		return ($rows === 1);
	}



	/**
	 * Lookup consent database for an entry, and update the timestamp.
	 *
	 * @return Will return true if consent is stored, and false if consent is not stored.
	 */
	public function getList($user_id) {
		$stmt = $this->dbh->prepare("SELECT * FROM consent WHERE hashed_user_id = ?");
		$stmt->execute(array($user_id));

		SimpleSAML_Logger::debug('Library - ConsentStorage getList(): Getting list of all consent entries for a user');
		
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}


	/**
	 * Store user consent in database
	 */
	public function store($user_id, $targeted_id, $attribute_hash) {
		/**
		 * insert new entry into consent storage.
		 */
		$stmt = $this->dbh->prepare("REPLACE INTO consent VALUES (?,?,?,NOW(),NOW())");
		$stmt->execute(array($user_id, $targeted_id, $attribute_hash));
		$rows = $stmt->rowCount();
	
		SimpleSAML_Logger::debug('Library - ConsentStorage store(): user_id        : ' . $user_id);
		SimpleSAML_Logger::debug('Library - ConsentStorage store(): targeted_id    : ' . $targeted_id);
		SimpleSAML_Logger::debug('Library - ConsentStorage store(): attribute_hash : ' . $attribute_hash);

		SimpleSAML_Logger::debug('Library - ConsentStorage store(): Number of rows : [' . $rows . ']');

		return ($rows === 1);
	}

  /**
   * Delete specific user consent in database
   */
  public function delete($user_id, $targeted_id, $attribute_hash) {

    SimpleSAML_Logger::debug('Library - ConsentStorage delete(): user_id        : ' . $user_id);
    SimpleSAML_Logger::debug('Library - ConsentStorage delete(): targeted_id    : ' . $targeted_id);
    SimpleSAML_Logger::debug('Library - ConsentStorage delete(): attribute_hash : ' . $attribute_hash);
    
    /**
     * delete specific entry from consent storage.
     */
    $stmt = $this->dbh->prepare("DELETE FROM consent WHERE hashed_user_id = ? AND service_id = ? AND attribute = ?");
    $stmt->execute(array($user_id, $targeted_id, $attribute_hash));
    
    return $stmt->rowCount();
  }

  /**
   * Delete user consent in database
   */
  public function deleteUserConsent($user_id) {

    SimpleSAML_Logger::debug('Library - ConsentStorage deleteUserConsent(): user_id        : ' . $user_id);
    
    /**
     * delete specific entry from consent storage.
     */
    $stmt = $this->dbh->prepare("DELETE FROM consent WHERE hashed_user_id = ?");
    $stmt->execute(array($user_id));
    
    return $stmt->rowCount();
  }
}

?>