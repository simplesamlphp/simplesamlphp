<?php

declare(strict_types=1);

namespace SimpleSAML\Metadata\Sources;

use Exception;
use SimpleSAML\{Configuration, Error, Logger, Utils};
use SimpleSAML\Metadata\MetaDataStorageSource;
use SimpleSAML\Metadata\SAMLParser;
use Symfony\Component\HttpFoundation\File\File;

use function array_key_exists;
use function error_get_last;
use function is_array;
use function json_decode;
use function json_encode;
use function sha1;
use function sprintf;
use function strval;
use function time;
use function urlencode;

/**
 * This class implements SAML Metadata Query Protocol
 *
 * @package simplesamlphp/simplesamlphp
 */

class MDQ extends MetaDataStorageSource
{
    /**
     * The URL of MDQ server (url:port)
     *
     * @var string
     */
    private string $server;

    /**
     * The certificate(s) that may be used to sign the metadata. You don't need this option if you don't want to
     * validate the signature on the metadata.
     *
     * @var array|null
     */
    private ?array $validateCertificate = null;

    /**
     * The cache directory, or null if no cache directory is configured.
     *
     * @var string|null
     */
    private ?string $cacheDir = null;

    /**
     * The maximum cache length, in seconds.
     *
     * @var integer
     */
    private int $cacheLength;

    /**
     * This function initializes the dynamic XML metadata source.
     *
     * Options:
     * - 'server':              URL of the MDQ server (url:port). Mandatory.
     *
     * Optional:
     * - 'validateCertificate': The certificate(s) that may be used to sign the metadata.
     *                          You don't need this option if you don't want to validate the signature on the metadata.
     * - 'cachedir':            Directory where metadata can be cached. Optional.
     * - 'cachelength':         Maximum time metadata cah be cached, in seconds. Default to 24 hours (86400 seconds).
     *
     * @param \SimpleSAML\Configuration $globalConfig The global configuration
     * @param array $config The configuration for this instance of the XML metadata source.
     *
     * @throws \Exception If no server option can be found in the configuration.
     */
    protected function __construct(Configuration $globalConfig, array $config)
    {
        parent::__construct();

        if (!array_key_exists('server', $config)) {
            throw new Exception(__CLASS__ . ": the 'server' configuration option is not set.");
        } else {
            $this->server = $config['server'];
        }

        if (array_key_exists('validateCertificate', $config)) {
            $this->validateCertificate = $config['validateCertificate'];
        }

        if (array_key_exists('cachedir', $config)) {
            $this->cacheDir = $globalConfig->resolvePath($config['cachedir']);
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
    public function getMetadataSet(string $set): array
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
    private function getCacheFilename(string $set, string $entityId): string
    {
        if ($this->cacheDir === null) {
            throw new Error\ConfigurationError("Missing cache directory configuration.");
        }

        $cachekey = sha1($entityId);
        return $this->cacheDir . '/' . $set . '-' . $cachekey . '.cached.json';
    }


    /**
     * Load a entity from the cache.
     *
     * @param string $set The metadata set this entity belongs to.
     * @param string $entityId The entity id of this entity.
     *
     * @return array|null  The associative array with the metadata for this entity, or NULL
     *                     if the entity could not be found.
     * @throws \Exception If an error occurs while loading metadata from cache.
     */
    private function getFromCache(string $set, string $entityId): ?array
    {
        if (empty($this->cacheDir)) {
            return null;
        }

        $cacheFileName = $this->getCacheFilename($set, $entityId);
        if (!$this->fileSystem->exists($cacheFileName)) {
            return null;
        }

        $file = new File($cacheFileName);
        if (!$file->isReadable()) {
            throw new Exception(sprintf('%s: could not read cache file for entity [%s]', strval($file), __CLASS__));
        }
        Logger::debug(sprintf('%s: reading cache [%s] => [%s]', __CLASS__, $entityId, strval($file)));

        /* Ensure that this metadata isn't older that the cachelength option allows. This
         * must be verified based on the file, since this option may be changed after the
         * file is written.
         */
        if (($file->getMtime() + $this->cacheLength) <= time()) {
            Logger::debug(sprintf('%s: cache file older that the cachelength option allows.', __CLASS__));
            $this->fileSystem->remove($cacheFileName);
            return null;
        }

        $rawData = $file->getContent();
        if (empty($rawData)) {
            /** @var array $error */
            $error = error_get_last();
            throw new Exception(sprintf(
                '%s: error reading metadata from cache file "%s": %s',
                __CLASS__,
                strval($file),
                $error['message'],
            ));
        }

        // ensure json is decoded as an associative array not an object
        $data = json_decode($rawData, true, 512, JSON_THROW_ON_ERROR);
        if ($data === false) {
            throw new Exception(
                sprintf('%s: error unserializing cached data from file "%s".', __CLASS__, strval($file)),
            );
        }

        if (!is_array($data)) {
            throw new Exception(sprintf("%s: Cached metadata from \"%s\" wasn't an array.", __CLASS__, strval($file)));
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
     */
    private function writeToCache(string $set, string $entityId, array $data): void
    {
        if (empty($this->cacheDir)) {
            return;
        }

        $cacheFileName = $this->getCacheFilename($set, $entityId);

        Logger::debug(sprintf('%s: Writing cache [%s] => [%s]', __CLASS__, $entityId, $cacheFileName));

        $this->fileSystem->dumpFile($cacheFileName, json_encode($data, JSON_THROW_ON_ERROR));
    }


    /**
     * Retrieve metadata for the correct set from a SAML2Parser.
     *
     * @param \SimpleSAML\Metadata\SAMLParser $entity A SAML2Parser representing an entity.
     * @param string                         $set The metadata set we are looking for.
     *
     * @return array|null  The associative array with the metadata, or NULL if no metadata for
     *                     the given set was found.
     */
    private static function getParsedSet(SAMLParser $entity, string $set): ?array
    {
        switch ($set) {
            case 'saml20-idp-remote':
                return $entity->getMetadata20IdP();
            case 'saml20-sp-remote':
                return $entity->getMetadata20SP();
            case 'attributeauthority-remote':
                return $entity->getAttributeAuthorities();
            default:
                Logger::warning(sprintf('%s: unknown metadata set: \'%s\'.', __CLASS__, $set));
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
     * @param string $entityId The entityId or metaindex we are looking up.
     * @param string $set The set we are looking for metadata in.
     *
     * @return array|null An associative array with metadata for the given entity, or NULL if we are unable to
     *         locate the entity.
     * @throws \Exception If an error occurs while validating the signature or the metadata is in an
     *         incorrect set.
     */
    public function getMetaData(string $entityId, string $set): ?array
    {
        Logger::info(sprintf('%s: loading metadata entity [%s] from [%s]', __CLASS__, $entityId, $set));

        // read from cache if possible
        try {
            $data = $this->getFromCache($set, $entityId);
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            // proceed with fetching metadata even if the cache is broken
            $data = null;
        }

        if (isset($data)) {
            if (array_key_exists('expire', $data) && $data['expire'] < time()) {
                // metadata has expired
                unset($data);
            } else {
                // metadata found in cache and not expired
                Logger::debug(sprintf('%s: using cached metadata for: %s.', __CLASS__, $entityId));
                return $data;
            }
        }

        // look at Metadata Query Protocol: https://github.com/iay/md-query/blob/master/draft-young-md-query.txt
        $mdq_url = $this->server . '/entities/' . urlencode($entityId);

        Logger::debug(sprintf('%s: downloading metadata for "%s" from [%s]', __CLASS__, $entityId, $mdq_url));
        $httpUtils = new Utils\HTTP();
        $context = [
            'http' => [
                'header' => 'Accept: application/samlmetadata+xml',
            ],
        ];
        try {
            $xmldata = $httpUtils->fetch($mdq_url, $context);
        } catch (Exception $e) {
            // Avoid propagating the exception, make sure we can handle the error later
            $xmldata = false;
        }

        if (empty($xmldata)) {
            $error = error_get_last();
            Logger::info(sprintf(
                'Unable to fetch metadata for "%s" from %s: %s',
                $entityId,
                $mdq_url,
                (is_array($error) ? $error['message'] : 'no error available'),
            ));
            return null;
        }

        /** @var string $xmldata */
        $entity = SAMLParser::parseString($xmldata);
        Logger::debug(sprintf('%s: completed parsing of [%s]', __CLASS__, $mdq_url));

        if (!empty($this->validateCertificate)) {
            if (!$entity->validateSignature($this->validateCertificate)) {
                throw new Exception(__CLASS__ . ': error, could not verify signature for entity: ' . $entityId . '".');
            }
        } else {
            Logger::notice('Not verifying MDQ metadata signature because no certificates were configured.');
        }

        $data = self::getParsedSet($entity, $set);
        if ($data === null) {
            throw new Exception(
                sprintf('%s: no metadata for set "%s" available from "%s".', __CLASS__, $set, $entityId),
            );
        }

        try {
            $this->writeToCache($set, $entityId, $data);
        } catch (Exception $e) {
            // Proceed without writing to cache
            Logger::error(sprintf('Error writing MDQ result to cache: %s', $e->getMessage()));
        }

        return $data;
    }

    /**
     * This function loads the metadata for entity IDs in $entityIds. It is returned as an associative array
     * where the key is the entity id. An empty array may be returned if no matching entities were found
     * @param string[] $entityIds The entity ids to load
     * @param string $set The set we want to get metadata from.
     * @return array An associative array with the metadata for the requested entities, if found.
     */
    public function getMetaDataForEntities(array $entityIds, string $set): array
    {
        return $this->getMetaDataForEntitiesIndividually($entityIds, $set);
    }
}
