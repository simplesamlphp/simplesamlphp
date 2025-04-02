<?php

declare(strict_types=1);

namespace SimpleSAML\Metadata;

use Exception;
use SimpleSAML\{Configuration, Error, Logger, Utils};
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\Utils\ClearableState;

use function array_key_exists;
use function array_merge;
use function sha1;
use function str_replace;
use function time;
use function var_export;

/**
 * This file defines a class for metadata handling.
 *
 * @package SimpleSAMLphp
 */

class MetaDataStorageHandler implements ClearableState
{
    /**
     * The configuration
     */
    protected Configuration $globalConfig;

    /**
     * This static variable contains a reference to the current
     * instance of the metadata handler. This variable will be null if
     * we haven't instantiated a metadata handler yet.
     *
     * @var \SimpleSAML\Metadata\MetaDataStorageHandler|null
     */
    private static ?MetadataStorageHandler $metadataHandler = null;


    /**
     * This is a list of all the metadata sources we have in our metadata
     * chain. When we need metadata, we will look through this chain from start to end.
     *
     * @var \SimpleSAML\Metadata\MetaDataStorageSource[]
     */
    private array $sources = [];


    /**
     * This function retrieves the current instance of the metadata handler.
     * The metadata handler will be instantiated if this is the first call
     * to this function.
     *
     * @return MetaDataStorageHandler The current metadata handler instance.
     */
    public static function getMetadataHandler(Configuration $config): MetaDataStorageHandler
    {
        if (self::$metadataHandler === null) {
            self::$metadataHandler = new MetaDataStorageHandler($config);
        }

        return self::$metadataHandler;
    }


    /**
     * This constructor initializes this metadata storage handler. It will load and
     * parse the configuration, and initialize the metadata source list.
     */
    protected function __construct(Configuration $globalConfig)
    {
        $this->globalConfig = $globalConfig;

        $sourcesConfig = $this->globalConfig->getOptionalArray('metadata.sources', [['type' => 'flatfile']]);

        try {
            $this->sources = MetaDataStorageSource::parseSources($sourcesConfig);
        } catch (Exception $e) {
            throw new Exception(
                "Invalid configuration of the 'metadata.sources' configuration option: " . $e->getMessage(),
            );
        }
    }


    /**
     * This function is used to generate some metadata elements automatically.
     *
     * @param string $property The metadata property which should be auto-generated.
     * @param string $set The set we the property comes from.
     * @param string|null $overrideHost Hostname to use in the URLs
     *
     * @return string|array The auto-generated metadata property.
     * @throws \Exception If the metadata cannot be generated automatically.
     */
    public function getGenerated(
        string $property,
        string $set,
        ?string $overrideHost = null,
        ?string $entityId = null,
    ): string|array {
        // first we check if the user has overridden this property in the metadata
        try {
            $metadataSet = $entityId ? $this->getMetaData($entityId, $set) : $this->getMetaDataCurrent($set);
            if (array_key_exists($property, $metadataSet)) {
                return $metadataSet[$property];
            }
        } catch (Exception $e) {
            // probably metadata wasn't found. In any case we continue by generating the metadata
        }

        // get the configuration
        $httpUtils = new Utils\HTTP();
        $baseurl = $httpUtils->getSelfURLHost() . $this->globalConfig->getBasePath();
        if ($overrideHost !== null) {
            $baseurl = str_replace('://' . $httpUtils->getSelfHost() . '/', '://' . $overrideHost . '/', $baseurl);
        }

        if ($set == 'saml20-sp-hosted') {
            if ($property === 'SingleLogoutServiceBinding') {
                return C::BINDING_HTTP_REDIRECT;
            }
        } elseif ($set == 'saml20-idp-hosted') {
            switch ($property) {
                case 'SingleSignOnService':
                    return $baseurl . 'module.php/saml/idp/singleSignOnService';

                case 'SingleSignOnServiceBinding':
                    return C::BINDING_HTTP_REDIRECT;

                case 'SingleLogoutService':
                    return $baseurl . 'module.php/saml/idp/singleLogout';

                case 'SingleLogoutServiceBinding':
                    return C::BINDING_HTTP_REDIRECT;
            }
        }

        throw new Exception('Could not generate metadata property ' . $property . ' for set ' . $set . '.');
    }


    /**
     * This function lists all known metadata in the given set. It is returned as an associative array
     * where the key is the entity id.
     *
     * @param string $set The set we want to list metadata from.
     * @param bool $showExpired A boolean specifying whether expired entities should be returned
     *
     * @return array An associative array with the metadata from from the given set.
     */
    public function getList(string $set = 'saml20-idp-remote', bool $showExpired = false): array
    {
        $result = [];
        $timeUtils = new Utils\Time();

        foreach ($this->sources as $source) {
            $srcList = $source->getMetadataSet($set);

            if ($showExpired === false) {
                foreach ($srcList as $key => $le) {
                    if (array_key_exists('expire', $le) && ($le['expire'] < time())) {
                        unset($srcList[$key]);
                        Logger::warning(
                            "Dropping metadata entity " . var_export($key, true) . ", expired " .
                            $timeUtils->generateTimestamp($le['expire']) . ".",
                        );
                    }
                }
            }

            /* $result is the last argument to array_merge because we want the content already
             * in $result to have precedence.
             */
            $result = array_merge($srcList, $result);
        }

        return $result;
    }


    /**
     * This function retrieves metadata for the current entity based on the hostname/path the request
     * was directed to. It will throw an exception if it is unable to locate the metadata.
     *
     * @param string $set The set we want metadata from.
     *
     * @return array An associative array with the metadata.
     */
    public function getMetaDataCurrent(string $set): array
    {
        return $this->getMetaData(null, $set);
    }


    /**
     * This function locates the current entity id based on the hostname/path combination the user accessed.
     * It will throw an exception if it is unable to locate the entity id.
     *
     * @param string $set The set we look for the entity id in.
     * @param string $type Do you want to return the metaindex or the entityID. [entityid|metaindex]
     *
     * @return string The entity id which is associated with the current hostname/path combination.
     * @throws \Exception If no default metadata can be found in the set for the current host.
     */
    public function getMetaDataCurrentEntityID(string $set, string $type = 'entityid'): string
    {
        // first we look for the hostname/path combination
        $httpUtils = new Utils\HTTP();
        $currenthostwithpath = $httpUtils->getSelfHostWithPath(); // sp.example.org/university

        foreach ($this->sources as $source) {
            $index = $source->getEntityIdFromHostPath($currenthostwithpath, $set, $type);
            if ($index !== null) {
                return $index;
            }
        }

        // then we look for the hostname
        $currenthost = $httpUtils->getSelfHost(); // sp.example.org

        foreach ($this->sources as $source) {
            $index = $source->getEntityIdFromHostPath($currenthost, $set, $type);
            if ($index !== null) {
                return $index;
            }
        }

        // then we look for the DEFAULT entry
        foreach ($this->sources as $source) {
            $entityId = $source->getEntityIdFromHostPath('__DEFAULT__', $set, $type);
            if ($entityId !== null) {
                return $entityId;
            }
        }

        // we were unable to find the hostname/path in any metadata source
        throw new Exception(
            'Could not find any default metadata entities in set [' . $set . '] for host [' . $currenthost . ' : ' .
            $currenthostwithpath . ']',
        );
    }


    /**
     * This method will call getPreferredEntityIdFromCIDRhint() on all of the
     * sources.
     *
     * @param string $set Which set of metadata we are looking it up in.
     * @param string $ip IP address
     *
     * @return string|null The entity id of a entity which have a CIDR hint where the provided
     *        IP address match.
     */
    public function getPreferredEntityIdFromCIDRhint(string $set, string $ip): ?string
    {
        foreach ($this->sources as $source) {
            $entityId = $source->getPreferredEntityIdFromCIDRhint($set, $ip);
            if ($entityId !== null) {
                return $entityId;
            }
        }

        return null;
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
        $result = [];
        // We are flipping the entityIds array in order to avoid constant iteration over it.
        // Even if it becomes smaller over time.
        // Still, after flipping all actions will be O(1)
        $entityIdsFlipped = array_flip($entityIds);
        $timeUtils = new Utils\Time();

        foreach ($this->sources as $source) {
            // entityIds may be reduced to being empty in this loop or already empty
            if (empty($entityIds)) {
                break;
            }

            $srcList = $source->getMetaDataForEntities($entityIds, $set);
            foreach ($srcList as $key => $le) {
                if (!empty($le['expire']) && $le['expire'] < time()) {
                    unset($srcList[$key]);
                    Logger::warning(
                        'Dropping metadata entity ' . var_export($key, true) . ', expired ' .
                        $timeUtils->generateTimestamp($le['expire']) . '.',
                    );
                    continue;
                }
                // We found the entity id so remove it from the list that needs resolving
                /** @psalm-suppress PossiblyInvalidArrayOffset */
                unset($entityIds[$entityIdsFlipped[$key]]);
                // Add the key to the result set
                $result[$key] = $le;
            }
        }

        return $result;
    }

    /**
     * This function looks up the metadata for the given entity id in the given set. It will throw an
     * exception if it is unable to locate the metadata.
     *
     * @param string|null $entityId The entity id we are looking up. This parameter may be NULL,
     * in which case we look up the current entity id based on the current hostname/path.
     * @param string $set The set of metadata we are looking up the entity id in.
     *
     * @return array The metadata array describing the specified entity.
     * @throws \Exception If metadata for the specified entity is expired.
     * @throws \SimpleSAML\Error\MetadataNotFound If no metadata for the entity specified can be found.
     */
    public function getMetaData(?string $entityId, string $set): array
    {
        if ($entityId === null) {
            $entityId = $this->getMetaDataCurrentEntityID($set, 'metaindex');
        }

        foreach ($this->sources as $source) {
            $metadata = $source->getMetaData($entityId, $set);

            if ($metadata !== null) {
                if (array_key_exists('expire', $metadata)) {
                    if ($metadata['expire'] < time()) {
                        throw new Exception(
                            'Metadata for the entity [' . $entityId . '] expired ' .
                            (time() - $metadata['expire']) . ' seconds ago.',
                        );
                    }
                }

                $metadata['metadata-index'] = $entityId;
                $metadata['metadata-set'] = $set;
                Assert::keyExists($metadata, 'entityid');
                return $metadata;
            }
        }

        throw new Error\MetadataNotFound($entityId);
    }


    /**
     * Retrieve the metadata as a configuration object.
     *
     * This function will throw an exception if it is unable to locate the metadata.
     *
     * @param string $entityId The entity ID we are looking up.
     * @param string $set The metadata set we are searching.
     *
     * @return \SimpleSAML\Configuration The configuration object representing the metadata.
     * @throws \SimpleSAML\Error\MetadataNotFound If no metadata for the entity specified can be found.
     */
    public function getMetaDataConfig(string $entityId, string $set): Configuration
    {
        $metadata = $this->getMetaData($entityId, $set);
        return Configuration::loadFromArray($metadata, $set . '/' . var_export($entityId, true));
    }


    /**
     * Search for an entity's metadata, given the SHA1 digest of its entity ID.
     *
     * @param string $sha1 The SHA1 digest of the entity ID.
     * @param string $set The metadata set we are searching.
     *
     * @return null|\SimpleSAML\Configuration The metadata corresponding to the entity, or null if the entity cannot be
     * found.
     */
    public function getMetaDataConfigForSha1(string $sha1, string $set): ?Configuration
    {
        $result = [];

        foreach ($this->sources as $source) {
            $srcList = $source->getMetadataSet($set);

            /* $result is the last argument to array_merge because we want the content already
             * in $result to have precedence.
             */
            $result = array_merge($srcList, $result);
        }
        foreach ($result as $remote_provider) {
            if (sha1($remote_provider['entityid']) == $sha1) {
                $remote_provider['metadata-set'] = $set;

                return Configuration::loadFromArray(
                    $remote_provider,
                    $set . '/' . var_export($remote_provider['entityid'], true),
                );
            }
        }

        return null;
    }


    /**
     * Clear any metadata cached.
     * Allows for metadata configuration to be changed and reloaded during a given request. Most useful
     * when running phpunit tests and needing to alter config.php and metadata sources between test cases
     */
    public static function clearInternalState(): void
    {
        self::$metadataHandler = null;
    }
}
