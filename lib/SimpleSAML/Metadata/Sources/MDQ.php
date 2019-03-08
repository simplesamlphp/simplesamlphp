<?php

namespace SimpleSAML\Metadata\Sources;

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use SimpleSAML\Logger;
use SimpleSAML\Utils\HTTP;

/**
 * This class implements SAML Metadata Query Protocol
 *
 * @author Andreas Åkre Solberg, UNINETT AS.
 * @author Olav Morken, UNINETT AS.
 * @author Tamas Frank, NIIFI
 * @package SimpleSAMLphp
 */

class MDQ extends \SimpleSAML\Metadata\MetaDataStorageSource
{
    /**
     * The URL of MDQ server (url:port)
     *
     * @var string
     */
    private $server;

    /**
     * The fingerprint of the certificate used to sign the metadata. You don't need this option if you don't want to
     * validate the signature on the metadata.
     *
     * @var string|null
     */
    private $validateFingerprint;

    /**
     * The cache directory, or null if no cache directory is configured.
     *
     * @var string|null
     */
    private $cacheDir;


    /**
     * The maximum cache length, in seconds.
     *
     * @var integer
     */
    private $cacheLength;


    /**
     * This function initializes the dynamic XML metadata source.
     *
     * Options:
     * - 'server': URL of the MDQ server (url:port). Mandatory.
     * - 'validateFingerprint': The fingerprint of the certificate used to sign the metadata.
     *                          You don't need this option if you don't want to validate the signature on the metadata.
     * Optional.
     * - 'cachedir':  Directory where metadata can be cached. Optional.
     * - 'cachelength': Maximum time metadata cah be cached, in seconds. Default to 24
     *                  hours (86400 seconds).
     *
     * @param array $config The configuration for this instance of the XML metadata source.
     *
     * @throws \Exception If no server option can be found in the configuration.
     */
    protected function __construct($config)
    {
        assert(is_array($config));

        if (!array_key_exists('server', $config)) {
            throw new \Exception(__CLASS__.": the 'server' configuration option is not set.");
        } else {
            $this->server = $config['server'];
        }

        if (array_key_exists('validateFingerprint', $config)) {
            $this->validateFingerprint = $config['validateFingerprint'];
        } else {
            $this->validateFingerprint = null;
        }
        if (isset($config['validateFingerprintAlgorithm'])) {
            $this->validateFingerprintAlgorithm = $config['validateFingerprintAlgorithm'];
        } else {
            $this->validateFingerprintAlgorithm = XMLSecurityDSig::SHA1;
        }

        if (array_key_exists('cachedir', $config)) {
            $globalConfig = \SimpleSAML\Configuration::getInstance();
            $this->cacheDir = $globalConfig->resolvePath($config['cachedir']);
        } else {
            $this->cacheDir = null;
        }

        if (array_key_exists('cachelength', $config)) {
            $this->cacheLength = $config['cachelength'];
        } else {
            $this->cacheLength = 86400;
        }
    }


    /**
     * This function is not implemented.
     *
     * @param string $set The set we want to list metadata for.
     *
     * @return array An empty array.
     */
    public function getMetadataSet($set)
    {
        // we don't have this metadata set
        return [];
    }


    /**
     * Find the cache file name for an entity,
     *
     * @param string $set The metadata set this entity belongs to.
     * @param string $entityId The entity id of this entity.
     *
     * @return string  The full path to the cache file.
     */
    private function getCacheFilename($set, $entityId)
    {
        assert(is_string($set));
        assert(is_string($entityId));

        $cachekey = sha1($entityId);
        return $this->cacheDir.'/'.$set.'-'.$cachekey.'.cached.xml';
    }


    /**
     * Load a entity from the cache.
     *
     * @param string $set The metadata set this entity belongs to.
     * @param string $entityId The entity id of this entity.
     *
     * @return array|NULL  The associative array with the metadata for this entity, or NULL
     *                     if the entity could not be found.
     * @throws \Exception If an error occurs while loading metadata from cache.
     */
    private function getFromCache($set, $entityId)
    {
        assert(is_string($set));
        assert(is_string($entityId));

        if (empty($this->cacheDir)) {
            return null;
        }

        $cachefilename = $this->getCacheFilename($set, $entityId);
        if (!file_exists($cachefilename)) {
            return null;
        }
        if (!is_readable($cachefilename)) {
            throw new \Exception(__CLASS__.': could not read cache file for entity ['.$cachefilename.']');
        }
        Logger::debug(__CLASS__.': reading cache ['.$entityId.'] => ['.$cachefilename.']');

        /* Ensure that this metadata isn't older that the cachelength option allows. This
         * must be verified based on the file, since this option may be changed after the
         * file is written.
         */
        $stat = stat($cachefilename);
        if ($stat['mtime'] + $this->cacheLength <= time()) {
            Logger::debug(__CLASS__.': cache file older that the cachelength option allows.');
            return null;
        }

        $rawData = file_get_contents($cachefilename);
        if (empty($rawData)) {
            $error = error_get_last();
            throw new \Exception(
                __CLASS__.': error reading metadata from cache file "'.$cachefilename.'": '.$error['message']
            );
        }

        $data = unserialize($rawData);
        if ($data === false) {
            throw new \Exception(__CLASS__.': error unserializing cached data from file "'.$cachefilename.'".');
        }

        if (!is_array($data)) {
            throw new \Exception(__CLASS__.': Cached metadata from "'.$cachefilename.'" wasn\'t an array.');
        }

        return $data;
    }


    /**
     * Save a entity to the cache.
     *
     * @param string $set The metadata set this entity belongs to.
     * @param string $entityId The entity id of this entity.
     * @param array  $data The associative array with the metadata for this entity.
     *
     * @throws \Exception If metadata cannot be written to cache.
     * @return void
     */
    private function writeToCache($set, $entityId, $data)
    {
        assert(is_string($set));
        assert(is_string($entityId));
        assert(is_array($data));

        if (empty($this->cacheDir)) {
            return;
        }

        $cachefilename = $this->getCacheFilename($set, $entityId);
        if (!is_writable(dirname($cachefilename))) {
            throw new \Exception(__CLASS__.': could not write cache file for entity ['.$cachefilename.']');
        }
        Logger::debug(__CLASS__.': Writing cache ['.$entityId.'] => ['.$cachefilename.']');
        file_put_contents($cachefilename, serialize($data));
    }


    /**
     * Retrieve metadata for the correct set from a SAML2Parser.
     *
     * @param \SimpleSAML\Metadata\SAMLParser $entity A SAML2Parser representing an entity.
     * @param string                         $set The metadata set we are looking for.
     *
     * @return array|NULL  The associative array with the metadata, or NULL if no metadata for
     *                     the given set was found.
     */
    private static function getParsedSet(\SimpleSAML\Metadata\SAMLParser $entity, $set)
    {
        assert(is_string($set));

        switch ($set) {
            case 'saml20-idp-remote':
                return $entity->getMetadata20IdP();
            case 'saml20-sp-remote':
                return $entity->getMetadata20SP();
            case 'shib13-idp-remote':
                return $entity->getMetadata1xIdP();
            case 'shib13-sp-remote':
                return $entity->getMetadata1xSP();
            case 'attributeauthority-remote':
                $ret = $entity->getAttributeAuthorities();
                return $ret[0];

            default:
                Logger::warning(__CLASS__.': unknown metadata set: \''.$set.'\'.');
        }

        return null;
    }


    /**
     * Overriding this function from the superclass \SimpleSAML\Metadata\MetaDataStorageSource.
     *
     * This function retrieves metadata for the given entity id in the given set of metadata.
     * It will return NULL if it is unable to locate the metadata.
     *
     * This class implements this function using the getMetadataSet-function. A subclass should
     * override this function if it doesn't implement the getMetadataSet function, or if the
     * implementation of getMetadataSet is slow.
     *
     * @param string $index The entityId or metaindex we are looking up.
     * @param string $set The set we are looking for metadata in.
     *
     * @return array|null An associative array with metadata for the given entity, or NULL if we are unable to
     *         locate the entity.
     * @throws \Exception If an error occurs while validating the signature or the metadata is in an
     *         incorrect set.
     */
    public function getMetaData($index, $set)
    {
        assert(is_string($index));
        assert(is_string($set));

        Logger::info(__CLASS__.': loading metadata entity ['.$index.'] from ['.$set.']');

        // read from cache if possible
        try {
            $data = $this->getFromCache($set, $index);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            // proceed with fetching metadata even if the cache is broken
            $data = null;
        }

        if ($data !== null && array_key_exists('expires', $data) && $data['expires'] < time()) {
            // metadata has expired
            $data = null;
        }

        if (isset($data)) {
            // metadata found in cache and not expired
            Logger::debug(__CLASS__.': using cached metadata for: '.$index.'.');
            return $data;
        }

        // look at Metadata Query Protocol: https://github.com/iay/md-query/blob/master/draft-young-md-query.txt
        $mdq_url = $this->server.'/entities/'.urlencode($index);

        Logger::debug(__CLASS__.': downloading metadata for "'.$index.'" from ['.$mdq_url.']');
        try {
            $xmldata = HTTP::fetch($mdq_url);
        } catch (\Exception $e) {
            // Avoid propagating the exception, make sure we can handle the error later
            $xmldata = false;
        }

        if (empty($xmldata)) {
            $error = error_get_last();
            Logger::info('Unable to fetch metadata for "'.$index.'" from '.$mdq_url.': '.
                (is_array($error) ? $error['message'] : 'no error available'));
            return null;
        }

        /** @var string $xmldata */
        $entity = \SimpleSAML\Metadata\SAMLParser::parseString($xmldata);
        Logger::debug(__CLASS__.': completed parsing of ['.$mdq_url.']');

        if ($this->validateFingerprint !== null) {
            if (!$entity->validateFingerprint(
                $this->validateFingerprint,
                $this->validateFingerprintAlgorithm
            )) {
                throw new \Exception(__CLASS__.': error, could not verify signature for entity: '.$index.'".');
            }
        }

        $data = self::getParsedSet($entity, $set);
        if ($data === null) {
            throw new \Exception(__CLASS__.': no metadata for set "'.$set.'" available from "'.$index.'".');
        }

        try {
            $this->writeToCache($set, $index, $data);
        } catch (\Exception $e) {
            // Proceed without writing to cache
            Logger::error('Error writing MDQ result to cache: '.$e->getMessage());
        }

        return $data;
    }
}
