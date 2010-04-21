<?php

/**
 * Class which implements a basic metadata aggregator.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_aggregator2_Aggregator {

	/**
	 * The ID of this aggregator.
	 *
	 * @var string
	 */
	protected $id;


	/**
	 * Our log "location".
	 *
	 * @var string
	 */
	protected $logLoc;


	/**
	 * Which cron-tag this should be updated in.
	 *
	 * @var string|NULL
	 */
	protected $cronTag;


	/**
	 * Absolute path to a cache directory.
	 *
	 * @var string|NULL
	 */
	protected $cacheDirectory;


	/**
	 * The entity sources.
	 *
	 * Array of sspmod_aggregator2_EntitySource objects.
	 *
	 * @var array
	 */
	protected $sources = array();


	/**
	 * How long the generated metadata should be valid, as a number of seconds.
	 *
	 * This is used to set the validUntil attribute on the generated EntityDescriptor.
	 *
	 * @var int
	 */
	protected $validLength;


	/**
	 * Duration we should cache generated metadata.
	 *
	 * @var int
	 */
	protected $cacheGenerated;


	/**
	 * The key we should use to sign the metadata.
	 *
	 * @var string|NULL
	 */
	protected $signKey;


	/**
	 * The password for the private key.
	 *
	 * @var string|NULL
	 */
	protected $signKeyPass;


	/**
	 * The certificate of the key we sign the metadata with.
	 *
	 * @var string|NULL
	 */
	protected $signCert;


	/**
	 * The CA certificate file that should be used to validate https-connections.
	 *
	 * @var string|NULL
	 */
	protected $sslCAFile;


	/**
	 * The cache ID for our generated metadata.
	 *
	 * @var string
	 */
	protected $cacheId;


	/**
	 * The cache tag for our generated metadata.
	 *
	 * This tag is used to make sure that a config change
	 * invalidates our cached metadata.
	 *
	 * @var string
	 */
	protected $cacheTag;


	/**
	 * Initialize this aggregator.
	 *
	 * @param string $id  The id of this aggregator.
	 * @param SimpleSAML_Configuration $config  The configuration for this aggregator.
	 */
	protected function __construct($id, SimpleSAML_Configuration $config) {
		assert('is_string($id)');

		$this->id = $id;
		$this->logLoc = 'aggregator2:' . $this->id . ': ';

		$this->cronTag = $config->getString('cron.tag', NULL);

		$this->cacheDirectory = $config->getString('cache.directory', NULL);
		if ($this->cacheDirectory !== NULL) {
			$this->cacheDirectory = SimpleSAML_Utilities::resolvePath($this->cacheDirectory);
		}

		$this->cacheGenerated = $config->getInteger('cache.generated', NULL);
		if ($this->cacheGenerated !== NULL) {
			$this->cacheId = sha1($this->id);
			$this->cacheTag = sha1(serialize($config));
		}

		$this->validLength = $config->getInteger('valid.length', 7*24*60*60);

		$globalConfig = SimpleSAML_Configuration::getInstance();
		$certDir = $globalConfig->getPathValue('certdir', 'cert/');

		$signKey = $config->getString('sign.privatekey', NULL);
		if ($signKey !== NULL) {
			$signKey = SimpleSAML_Utilities::resolvePath($signKey, $certDir);
			$this->signKey = @file_get_contents($signKey);
			if ($this->signKey === NULL) {
				throw new SimpleSAML_Error_Exception('Unable to load private key from ' . var_export($signKey, TRUE));
			}
		}

		$this->signKeyPass = $config->getString('sign.privatekey_pass', NULL);

		$signCert = $config->getString('sign.certificate', NULL);
		if ($signCert !== NULL) {
			$signCert = SimpleSAML_Utilities::resolvePath($signCert, $certDir);
			$this->signCert = @file_get_contents($signCert);
			if ($this->signCert === NULL) {
				throw new SimpleSAML_Error_Exception('Unable to load certificate file from ' . var_export($signCert, TRUE));
			}
		}


		$this->sslCAFile = $config->getString('ssl.cafile', NULL);

		$this->initSources($config->getConfigList('sources'));
	}


	/**
	 * Populate the sources array.
	 *
	 * This is called from the constructor, and can be overridden in subclasses.
	 *
	 * @param array $sources  The sources as an array of SimpleSAML_Configuration objects.
	 */
	protected function initSources(array $sources) {

		foreach ($sources as $source) {
			$this->sources[] = new sspmod_aggregator2_EntitySource($this, $source);
		}
	}


	/**
	 * Return an instance of the aggregator with the given id.
	 *
	 * @param string $id  The id of the aggregator.
	 */
	public static function getAggregator($id) {
		assert('is_string($id)');

		$config = SimpleSAML_Configuration::getConfig('module_aggregator2.php');
		return new sspmod_aggregator2_Aggregator($id, $config->getConfigItem($id));
	}


	/**
	 * Retrieve the ID of the aggregator.
	 *
	 * @return string  The ID of this aggregator.
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * Add an item to the cache.
	 *
	 * @param string $id  The identifier of this data.
	 * @param string $data  The data.
	 * @param int $expires  The timestamp the data expires.
	 * @param string|NULL $tag  An extra tag that can be used to verify the validity of the cached data.
	 */
	public function addCacheItem($id, $data, $expires, $tag = NULL) {
		assert('is_string($id)');
		assert('is_string($data)');
		assert('is_int($expires)');
		assert('is_null($tag) || is_string($tag)');

		$cacheFile = $this->cacheDirectory . '/' . $id;
		try {
			SimpleSAML_Utilities::writeFile($cacheFile, $data);
		} catch (Exception $e) {
			SimpleSAML_Logger::warning($this->logLoc . 'Unable to write to cache file ' . var_export($cacheFile, TRUE));
			return;
		}

		$expireInfo = (string)$expires;
		if ($tag !== NULL) {
			$expireInfo .= ':' . $tag;
		}

		$expireFile = $cacheFile . '.expire';
		try {
			SimpleSAML_Utilities::writeFile($expireFile, $expireInfo);
		} catch (Exception $e) {
			SimpleSAML_Logger::warning($this->logLoc . 'Unable to write expiration info to ' . var_export($expireFile, TRUE));
			return $metadata;
		}

	}


	/**
	 * Check validity of cached data.
	 *
	 * @param string $id  The identifier of this data.
	 * @param string $tag  The tag that was passed to addCacheItem.
	 * @return bool  TRUE if the data is valid, FALSE if not.
	 */
	public function isCacheValid($id, $tag = NULL) {
		assert('is_string($id)');
		assert('is_null($tag) || is_string($tag)');

		$cacheFile = $this->cacheDirectory . '/' . $id;
		if (!file_exists($cacheFile)) {
			return FALSE;
		}

		$expireFile = $cacheFile . '.expire';
		if (!file_exists($expireFile)) {
			return FALSE;
		}

		$expireData = @file_get_contents($expireFile);
		if ($expireData === FALSE) {
			return FALSE;
		}

		$expireData = explode(':', $expireData, 2);

		$expireTime = (int)$expireData[0];
		if ($expireTime <= time()) {
			return FALSE;
		}

		if (count($expireData) === 1) {
			$expireTag = NULL;
		} else {
			$expireTag = $expireData[1];
		}
		if ($expireTag !== $tag) {
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * Get the cache item.
	 *
	 * @param string $id  The identifier of this data.
	 * @param string $tag  The tag that was passed to addCacheItem.
	 * @return string|NULL  The cache item, or NULL if it isn't cached or if it is expired.
	 */
	public function getCacheItem($id, $tag = NULL) {
		assert('is_string($id)');
		assert('is_null($tag) || is_string($tag)');

		if (!$this->isCacheValid($id, $tag)) {
			return NULL;
		}

		$cacheFile = $this->cacheDirectory . '/' . $id;
		return @file_get_contents($cacheFile);
	}


	/**
	 * Get the cache filename for the specific id.
	 *
	 * @param string $id  The identifier of the cached data.
	 * @return string|NULL  The filename, or NULL if the cache file doesn't exist.
	 */
	public function getCacheFile($id) {
		assert('is_string($id)');

		$cacheFile = $this->cacheDirectory . '/' . $id;
		if (!file_exists($cacheFile)) {
			return NULL;
		}

		return $cacheFile;
	}


	/**
	 * Retrieve the SSL CA file path, if it is set.
	 *
	 * @return string|NULL  The SSL CA file path.
	 */
	public function getCAFile() {

		return $this->sslCAFile;
	}


	/**
	 * Sign the generated EntitiesDescriptor.
	 */
	protected function addSignature(SAML2_SignedElement $element) {

		if ($this->signKey === NULL) {
			return;
		}

		$privateKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
		if ($this->signKeyPass !== NULL) {
			$privateKey->passphrase = $this->signKeyPass;
		}
		$privateKey->loadKey($this->signKey, FALSE);


		$element->setSignatureKey($privateKey);

		if ($this->signCert !== NULL) {
			$element->setCertificates(array($this->signCert));
		}
	}


	/**
	 * Retrieve all entities as an EntitiesDescriptor.
	 *
	 * @return SAML2_XML_md_EntitiesDescriptor  The entities.
	 */
	protected function getEntitiesDescriptor() {

		$ret = new SAML2_XML_md_EntitiesDescriptor();
		foreach ($this->sources as $source) {
			$m = $source->getMetadata();
			if ($m === NULL) {
				continue;
			}
			$ret->children[] = $m;
		}

		$ret->validUntil = time() + $this->validLength;

		return $ret;
	}


	/**
	 * Retrieve the complete, signed metadata as text.
	 *
	 * This function will write the new metadata to the cache file, but will not return
	 * the cached metadata.
	 *
	 * @return string  The metadata, as text.
	 */
	public function updateCachedMetadata() {

		$ed = $this->getEntitiesDescriptor();
		$this->addSignature($ed);

		$xml = $ed->toXML();
		$xml = $xml->ownerDocument->saveXML($xml);

		if ($this->cacheGenerated !== NULL) {
			SimpleSAML_Logger::debug($this->logLoc . 'Saving generated metadata to cache.');
			$this->addCacheItem($this->cacheId, $xml, time() + $this->cacheGenerated, $this->cacheTag);
		}

		return $xml;

	}


	/**
	 * Retrieve the complete, signed metadata as text.
	 *
	 * @return string  The metadata, as text.
	 */
	public function getMetadata() {

		if ($this->cacheGenerated !== NULL) {
			$xml = $this->getCacheItem($this->cacheId, $this->cacheTag);
			if ($xml !== NULL) {
				SimpleSAML_Logger::debug($this->logLoc . 'Loaded generated metadata from cache.');
				return $xml;
			}
		}

		return $this->updateCachedMetadata();
	}


	/**
	 * Update the cached copy of our metadata.
	 */
	public function updateCache() {

		foreach ($this->sources as $source) {
			$source->updateCache();
		}

		$this->updateCachedMetadata();
	}

}
