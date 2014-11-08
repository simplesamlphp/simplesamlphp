<?php

/**
 * Class which implements a basic metadata aggregator.
 *
 * @package SimpleSAMLphp
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
     * An array of entity IDs to exclude from the aggregate.
     *
     * @var string[]|null
     */
    protected $excluded;


    /**
     * An indexed array of protocols to filter the aggregate by. keys can be any of:
     *
     * - urn:oasis:names:tc:SAML:1.1:protocol
     * - urn:oasis:names:tc:SAML:2.0:protocol
     *
     * Values will be true if enabled, false otherwise.
     *
     * @var string[]|null
     */
    protected $protocols;


    /**
     * An array of roles to filter the aggregate by. Keys can be any of:
     *
     * - SAML2_XML_md_IDPSSODescriptor
     * - SAML2_XML_md_SPSSODescriptor
     * - SAML2_XML_md_AttributeAuthorityDescriptor
     *
     * Values will be true if enabled, false otherwise.
     *
     * @var string[]|null
     */
    protected $roles;


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
	 * The registration information for our generated metadata.
	 *
	 * @var array
	 */
	protected $regInfo;


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

        // configure entity IDs excluded by default
        $this->excludeEntities($config->getArrayize('exclude', null));

        // configure filters
        $this->setFilters($config->getArrayize('filter', null));

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

		$this->regInfo = $config->getArray('RegistrationInfo', NULL);

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

		$now = time();

		// add RegistrationInfo extension if enabled
		if ($this->regInfo !== NULL) {
			$ri = new SAML2_XML_mdrpi_RegistrationInfo();
			$ri->registrationInstant = $now;
			foreach ($this->regInfo as $riName => $riValues) {
				switch ($riName) {
					case 'authority':
						$ri->registrationAuthority = $riValues;
						break;
					case 'instant':
						$ri->registrationInstant = SAML2_Utils::xsDateTimeToTimestamp($riValues);
						break;
					case 'policies':
						$ri->RegistrationPolicy = $riValues;
						break;
				}
			}
			$ret->Extensions[] = $ri;
		}

		foreach ($this->sources as $source) {
			$m = $source->getMetadata();
			if ($m === NULL) {
				continue;
			}
			$ret->children[] = $m;
		}

		$ret->validUntil = $now + $this->validLength;

		return $ret;
	}


    /**
     * Recursively traverse the children of an EntitiesDescriptor, removing those entities listed in the $entities
     * property. Returns the EntitiesDescriptor with the entities filtered out.
     *
     * @param SAML2_XML_md_EntitiesDescriptor $descriptor The EntitiesDescriptor from where to exclude entities.
     *
     * @return SAML2_XML_md_EntitiesDescriptor The EntitiesDescriptor with excluded entities filtered out.
     */
    protected function exclude(SAML2_XML_md_EntitiesDescriptor $descriptor)
    {
        if (empty($this->excluded)) {
            return $descriptor;
        }

        $filtered = array();
        foreach ($descriptor->children as $child) {
            if ($child instanceof SAML2_XML_md_EntityDescriptor) {
                if (in_array($child->entityID, $this->excluded)) {
                    continue;
                }
                $filtered[] = $child;
            }

            if ($child instanceof SAML2_XML_md_EntitiesDescriptor) {
                $filtered[] = $this->exclude($child);
            }
        }

        $descriptor->children = $filtered;
        return $descriptor;
    }


    /**
     * Recursively traverse the children of an EntitiesDescriptor, keeping only those entities with the roles listed in
     * the $roles property, and support for the protocols listed in the $protocols property. Returns the
     * EntitiesDescriptor containing only those entities.
     *
     * @param SAML2_XML_md_EntitiesDescriptor $descriptor The EntitiesDescriptor to filter.
     *
     * @return SAML2_XML_md_EntitiesDescriptor The EntitiesDescriptor with only the entities filtered.
     */
    protected function filter(SAML2_XML_md_EntitiesDescriptor $descriptor)
    {
        if ($this->roles === null || $this->protocols === null) {
            return $descriptor;
        }

        $enabled_roles = array_keys($this->roles, true);
        $enabled_protos = array_keys($this->protocols, true);

        $filtered = array();
        foreach ($descriptor->children as $child) {
            if ($child instanceof SAML2_XML_md_EntityDescriptor) {
                foreach ($child->RoleDescriptor as $role) {
                    if (in_array(get_class($role), $enabled_roles)) {
                        // we found a role descriptor that is enabled by our filters, check protocols
                        if (array_intersect($enabled_protos, $role->protocolSupportEnumeration) !== array()) {
                            // it supports some protocol we have enabled, add it
                            $filtered[] = $child;
                            break;
                        }
                    }
                }

            }

            if ($child instanceof SAML2_XML_md_EntitiesDescriptor) {
                $filtered[] = $this->filter($child);
            }
        }

        $descriptor->children = $filtered;
        return $descriptor;
    }


    /**
     * Set this aggregator to exclude a set of entities from the resulting aggregate.
     *
     * @param array|null $entities The entity IDs of the entities to exclude.
     */
    public function excludeEntities($entities)
    {
        assert('is_array($entities) || is_null($entities)');

        if ($entities === null) {
            return;
        }
        $this->excluded = $entities;
        sort($this->excluded);
        $this->cacheId = sha1($this->cacheId . serialize($this->excluded));
    }


    /**
     * Set the internal filters according to one or more options:
     *
     * - 'saml2': all SAML2.0-capable entities.
     * - 'shib13': all SHIB1.3-capable entities.
     * - 'saml20-idp': all SAML2.0-capable identity providers.
     * - 'saml20-sp': all SAML2.0-capable service providers.
     * - 'saml20-aa': all SAML2.0-capable attribute authorities.
     * - 'shib13-idp': all SHIB1.3-capable identity providers.
     * - 'shib13-sp': all SHIB1.3-capable service providers.
     * - 'shib13-aa': all SHIB1.3-capable attribute authorities.
     *
     * @param array|null $set An array of the different roles and protocols to filter by.
     */
    public function setFilters($set)
    {
        assert('is_array($set) || is_null($set)');

        if ($set === null) {
            return;
        }

        // configure filters
        $this->protocols = array(
            SAML2_Const::NS_SAMLP                  => TRUE,
            'urn:oasis:names:tc:SAML:1.1:protocol' => TRUE,
        );
        $this->roles = array(
            'SAML2_XML_md_IDPSSODescriptor'             => TRUE,
            'SAML2_XML_md_SPSSODescriptor'              => TRUE,
            'SAML2_XML_md_AttributeAuthorityDescriptor' => TRUE,
        );

        // now translate from the options we have, to specific protocols and roles

        // check SAML 2.0 protocol
        $options = array('saml2', 'saml20-idp', 'saml20-sp', 'saml20-aa');
        $this->protocols[SAML2_Const::NS_SAMLP] = (array_intersect($set, $options) !== array());

        // check SHIB 1.3 protocol
        $options = array('shib13', 'shib13-idp', 'shib13-sp', 'shib13-aa');
        $this->protocols['urn:oasis:names:tc:SAML:1.1:protocol'] = (array_intersect($set, $options) !== array());

        // check IdP
        $options = array('saml2', 'shib13', 'saml20-idp', 'shib13-idp');
        $this->roles['SAML2_XML_md_IDPSSODescriptor'] = (array_intersect($set, $options) !== array());

        // check SP
        $options = array('saml2', 'shib13', 'saml20-sp', 'shib13-sp');
        $this->roles['SAML2_XML_md_SPSSODescriptor'] = (array_intersect($set, $options) !== array());

        // check AA
        $options = array('saml2', 'shib13', 'saml20-aa', 'shib13-aa');
        $this->roles['SAML2_XML_md_AttributeAuthorityDescriptor'] = (array_intersect($set, $options) !== array());

        $this->cacheId = sha1($this->cacheId . serialize($this->protocols) . serialize($this->roles));
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
        $ed = $this->exclude($ed);
        $ed = $this->filter($ed);
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
