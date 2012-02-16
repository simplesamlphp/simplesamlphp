<?php

/**
 * Class for loading metadata from files and URLs.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_aggregator2_EntitySource {

	/**
	 * Our log "location".
	 *
	 * @var string
	 */
	protected $logLoc;


	/**
	 * The aggregator we belong to.
	 *
	 * @var sspmod_aggregator2_Aggregator
	 */
	protected $aggregator;


	/**
	 * The URL we should fetch it from.
	 *
	 * @var string
	 */
	protected $url;


	/**
	 * The SSL CA file that should be used to validate the connection.
	 *
	 * @var string|NULL
	 */
	protected $sslCAFile;


	/**
	 * The certificate we should use to validate downloaded metadata.
	 *
	 * @var string|NULL
	 */
	protected $certificate;


	/**
	 * The parsed metadata.
	 *
	 * @var SAML2_XML_md_EntitiesDescriptor|SAML2_XML_md_EntityDescriptor|NULL
	 */
	protected $metadata;


	/**
	 * The cache ID.
	 *
	 * @var string
	 */
	protected $cacheId;


	/**
	 * The cache tag.
	 *
	 * @var string
	 */
	protected $cacheTag;


	/**
	 * Whether we have attempted to update the cache already.
	 *
	 * @var bool
	 */
	protected $updateAttempted;


	/**
	 * Initialize this EntitySource.
	 *
	 * @param SimpleSAML_Configuration $config  The configuration.
	 */
	public function __construct(sspmod_aggregator2_Aggregator $aggregator, SimpleSAML_Configuration $config) {

		$this->logLoc = 'aggregator2:' . $aggregator->getId() . ': ';
		$this->aggregator = $aggregator;

		$this->url = $config->getString('url');
		$this->sslCAFile = $config->getString('ssl.cafile', NULL);
		if ($this->sslCAFile === NULL) {
			$this->sslCAFile = $aggregator->getCAFile();
		}

		$this->certificate = $config->getString('cert', NULL);

		$this->cacheId = sha1($this->url);
		$this->cacheTag = sha1(serialize($config));
	}


	/**
	 * Retrieve and parse the metadata.
	 *
	 * @return SAML2_XML_md_EntitiesDescriptor|SAML2_XML_md_EntityDescriptor|NULL
	 * The downloaded metadata or NULL if we were unable to download or parse it.
	 */
	private function downloadMetadata() {

		SimpleSAML_Logger::debug($this->logLoc . 'Downloading metadata from ' .
			var_export($this->url, TRUE));

		$context = array('ssl' => array());
		if ($this->sslCAFile !== NULL) {
			$context['ssl']['cafile'] = SimpleSAML_Utilities::resolveCert($this->sslCAFile);
			SimpleSAML_Logger::debug($this->logLoc . 'Validating https connection against CA certificate(s) found in ' .
				var_export($context['ssl']['cafile'], TRUE));
			$context['ssl']['verify_peer'] = TRUE;
			$context['ssl']['CN_match'] = parse_url($this->url, PHP_URL_HOST);
		}


		$data = SimpleSAML_Utilities::fetch($this->url, $context);
		if ($data === FALSE || $data === NULL) {
			SimpleSAML_Logger::error($this->logLoc . 'Unable to load metadata from ' .
				var_export($this->url, TRUE));
			return NULL;
		}

		$doc = new DOMDocument();
		$res = $doc->loadXML($data);
		if (!$res) {
			SimpleSAML_Logger::error($this->logLoc . 'Error parsing XML from ' .
				var_export($this->url, TRUE));
			return NULL;
		}

		$root = SAML2_Utils::xpQuery($doc->firstChild, '/saml_metadata:EntityDescriptor|/saml_metadata:EntitiesDescriptor');
		if (count($root) === 0) {
			SimpleSAML_Logger::error($this->logLoc . 'No <EntityDescriptor> or <EntitiesDescriptor> in metadata from ' .
				var_export($this->url, TRUE));
			return NULL;
		}

		if (count($root) > 1) {
			SimpleSAML_Logger::error($this->logLoc . 'More than one <EntityDescriptor> or <EntitiesDescriptor> in metadata from ' .
				var_export($this->url, TRUE));
			return NULL;
		}

		$root = $root[0];
		try {
			if ($root->localName === 'EntityDescriptor') {
				$md = new SAML2_XML_md_EntityDescriptor($root);
			} else {
				$md = new SAML2_XML_md_EntitiesDescriptor($root);
			}
		} catch (Exception $e) {
			SimpleSAML_Logger::error($this->logLoc . 'Unable to parse metadata from ' .
				var_export($this->url, TRUE) . ': ' . $e->getMessage());
			return NULL;
		}


		if ($this->certificate !== NULL) {
			$file = SimpleSAML_Utilities::resolveCert($this->certificate);
			$certData = file_get_contents($file);
			if ($certData === FALSE) {
				throw new SimpleSAML_Error_Exception('Error loading certificate from ' . var_export($file, TRUE));
			}

			/* Extract the public key from the certificate for validation. */
			$key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'public'));
			$key->loadKey($file, TRUE);

			if (!$md->validate($key)) {
				SimpleSAML_Logger::error($this->logLoc . 'Error validating signature on metadata.');
				return NULL;
			}
			SimpleSAML_Logger::debug($this->logLoc . 'Validated signature on metadata from ' . var_export($this->url, TRUE));
		}

		return $md;
	}


	/**
	 * Attempt to update our cache file.
	 */
	public function updateCache() {

		if ($this->updateAttempted) {
			return;
		}
		$this->updateAttempted = TRUE;

		$this->metadata = $this->downloadMetadata();
		if ($this->metadata === NULL) {
			return;
		}

		$expires = time() + 24*60*60; /* Default expires in one day. */

		if ($this->metadata->validUntil !== NULL && $this->metadata->validUntil < $expires) {
			$expires = $this->metadata->validUntil;
		}

		$metadataSerialized = serialize($this->metadata);

		$this->aggregator->addCacheItem($this->cacheId, $metadataSerialized, $expires, $this->cacheTag);
	}


	/**
	 * Retrieve the metadata file.
	 *
	 * This function will check its cached copy, to see whether it can be used.
	 *
	 * @return SAML2_XML_md_EntityDescriptor|SAML2_XML_md_EntitiesDescriptor|NULL  The downloaded metadata.
	 */
	public function getMetadata() {

		if ($this->metadata !== NULL) {
			/* We have already downloaded the metdata. */
			return $this->metadata;
		}

		if (!$this->aggregator->isCacheValid($this->cacheId, $this->cacheTag)) {
			$this->updateCache();
			if ($this->metadata !== NULL) {
				return $this->metadata;
			}
			/* We were unable to update the cache - use cached metadata. */
		}


		$cacheFile = $this->aggregator->getCacheFile($this->cacheId);

		if (!file_exists($cacheFile)) {
			SimpleSAML_Logger::error($this->logLoc . 'No cached metadata available.');
			return NULL;
		}

		SimpleSAML_Logger::debug($this->logLoc . 'Using cached metadata from ' .
			var_export($cacheFile, TRUE));

		$metadata = file_get_contents($cacheFile);
		if ($metadata !== NULL) {
			$this->metadata = unserialize($metadata);
			return $this->metadata;
		}

		return NULL;
	}

}
