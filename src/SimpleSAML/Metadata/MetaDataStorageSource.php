<?php

declare(strict_types=1);

namespace SimpleSAML\Metadata;

use Exception;
use SimpleSAML\{Configuration, Error, Module, Utils};
use Symfony\Component\Filesystem\Filesystem;

use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_merge;
use function is_array;

/**
 * This abstract class defines an interface for metadata storage sources.
 *
 * It also contains the overview of the different metadata storage sources.
 * A metadata storage source can be loaded by passing the configuration of it
 * to the getSource static function.
 *
 * @package SimpleSAMLphp
 */

abstract class MetaDataStorageSource
{
    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected Filesystem $fileSystem;


    /**
     * This function initializes an XML metadata source.
     */
    protected function __construct()
    {
        $this->fileSystem = new Filesystem();
    }


    /**
     * Parse array with metadata sources.
     *
     * This function accepts an array with metadata sources, and returns an array with
     * each metadata source as an object.
     *
     * @param array $sourcesConfig Array with metadata source configuration.
     *
     * @return array  Parsed metadata configuration.
     *
     * @throws \Exception If something is wrong in the configuration.
     */
    public static function parseSources(array $sourcesConfig): array
    {
        $sources = [];

        foreach ($sourcesConfig as $sourceConfig) {
            if (!is_array($sourceConfig)) {
                throw new Exception("Found an element in metadata source configuration which wasn't an array.");
            }

            $sources[] = self::getSource($sourceConfig);
        }

        return $sources;
    }


    /**
     * This function creates a metadata source based on the given configuration.
     * The type of source is based on the 'type' parameter in the configuration.
     * The default type is 'flatfile'.
     *
     * @param array $sourceConfig Associative array with the configuration for this metadata source.
     *
     * @return \SimpleSAML\Metadata\MetaDataStorageSource An instance of a metadata source with the given configuration.
     *
     * @throws \Exception If the metadata source type is invalid.
     */
    public static function getSource(array $sourceConfig): MetaDataStorageSource
    {
        if (array_key_exists('type', $sourceConfig)) {
            $type = $sourceConfig['type'];
        } else {
            $type = 'flatfile';
        }

        $config = Configuration::getInstance();
        switch ($type) {
            case 'flatfile':
                return new MetaDataStorageHandlerFlatFile($config, $sourceConfig);
            case 'xml':
                return new MetaDataStorageHandlerXML($config, $sourceConfig);
            case 'serialize':
                return new MetaDataStorageHandlerSerialize($config, $sourceConfig);
            case 'mdx':
            case 'mdq':
                return new Sources\MDQ($config, $sourceConfig);
            case 'pdo':
                return new MetaDataStorageHandlerPdo($config, $sourceConfig);
            case 'directory':
                return new MetaDataStorageHandlerDirectory($config, $sourceConfig);
            default:
                // metadata store from module
                try {
                    $className = Module::resolveClass(
                        $type,
                        'MetadataStore',
                        '\SimpleSAML\Metadata\MetaDataStorageSource',
                    );
                } catch (Exception $e) {
                    throw new Error\CriticalConfigurationError(
                        "Invalid 'type' for metadata source. Cannot find store '$type'.",
                        null,
                    );
                }

                /** @var \SimpleSAML\Metadata\MetaDataStorageSource */
                return new $className($config, $sourceConfig);
        }
    }


    /**
     * This function attempts to generate an associative array with metadata for all entities in the
     * given set. The key of the array is the entity id.
     *
     * A subclass should override this function if it is able to easily generate this list.
     *
     * @param string $set The set we want to list metadata for.
     *
     * @return array An associative array with all entities in the given set, or an empty array if we are
     *         unable to generate this list.
     */
    public function getMetadataSet(string $set): array
    {
        return [];
    }


    /**
     * This function resolves an host/path combination to an entity id.
     *
     * This class implements this function using the getMetadataSet-function. A subclass should
     * override this function if it doesn't implement the getMetadataSet function, or if the
     * implementation of getMetadataSet is slow.
     *
     * @param string $hostPath The host/path combination we are looking up.
     * @param string $set Which set of metadata we are looking it up in.
     * @param string $type Do you want to return the metaindex or the entityID. [entityid|metaindex]
     *
     * @return string|null An entity id which matches the given host/path combination, or NULL if
     *         we are unable to locate one which matches.
     */
    public function getEntityIdFromHostPath(string $hostPath, string $set, string $type = 'entityid'): ?string
    {
        $metadataSet = $this->getMetadataSet($set);

        foreach ($metadataSet as $index => $entry) {
            if (!array_key_exists('host', $entry)) {
                continue;
            }

            if ($hostPath === $entry['host']) {
                if ($type === 'entityid') {
                    return $entry['entityid'];
                } else {
                    return $index;
                }
            }
        }

        // no entries matched, we should return null
        return null;
    }


    /**
     * This function will go through all the metadata, and check the DiscoHints->IPHint
     * parameter, which defines a network space (ip range) for each remote entry.
     * This function returns the entityID for any of the entities that have an
     * IP range which the IP falls within.
     *
     * @param string $set Which set of metadata we are looking it up in.
     * @param string $ip IP address
     * @param string $type Do you want to return the metaindex or the entityID. [entityid|metaindex]
     *
     * @return string|null The entity id of a entity which have a CIDR hint where the provided
     *        IP address match.
     */
    public function getPreferredEntityIdFromCIDRhint(string $set, string $ip, string $type = 'entityid'): ?string
    {
        $metadataSet = $this->getMetadataSet($set);

        foreach ($metadataSet as $index => $entry) {
            $cidrHints = [];

            // support hint.cidr for idp discovery
            if (array_key_exists('hint.cidr', $entry) && is_array($entry['hint.cidr'])) {
                $cidrHints = $entry['hint.cidr'];
            }

            // support discohints in idp metadata for idp discovery
            if (
                array_key_exists('DiscoHints', $entry)
                && array_key_exists('IPHint', $entry['DiscoHints'])
                && is_array($entry['DiscoHints']['IPHint'])
            ) {
                // merge with hints derived from discohints, but prioritize hint.cidr in case it is used
                $cidrHints = array_merge($entry['DiscoHints']['IPHint'], $cidrHints);
            }

            if (empty($cidrHints)) {
                continue;
            }

            $netUtils = new Utils\Net();
            foreach ($cidrHints as $hint_entry) {
                if ($netUtils->ipCIDRcheck($hint_entry, $ip)) {
                    if ($type === 'entityid') {
                        return $entry['entityid'];
                    } else {
                        return $index;
                    }
                }
            }
        }

        // no entries matched, we should return null
        return null;
    }


    /**
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
     */
    public function getMetaData(string $entityId, string $set): ?array
    {
        $metadataSet = $this->getMetadataSet($set);

        $indexLookup = $this->lookupIndexFromEntityId($entityId, $metadataSet);
        if (isset($indexLookup) && array_key_exists($indexLookup, $metadataSet)) {
            return $metadataSet[$indexLookup];
        }

        return null;
    }


    /**
     * This function loads the metadata for entity IDs in $entityIds. It is returned as an associative array
     * where the key is the entity id. An empty array may be returned if no matching entities were found.
     * Subclasses should override if their getMetadataSet returns nothing or is slow. Subclasses may want to
     * delegate to getMetaDataForEntitiesIndividually if loading entities one at a time is faster.
     * @param string[] $entityIds The entity ids to load
     * @param string $set The set we want to get metadata from.
     * @return array An associative array with the metadata for the requested entities, if found.
     */
    public function getMetaDataForEntities(array $entityIds, string $set): array
    {
        if (empty($entityIds)) {
            return [];
        }
        if (count($entityIds) === 1) {
            return $this->getMetaDataForEntitiesIndividually($entityIds, $set);
        }
        $entities = $this->getMetadataSet($set);
        return array_intersect_key($entities, array_flip($entityIds));
    }


    /**
     * Loads metadata entities one at a time, rather than the default implementation of loading all entities
     * and filtering.
     * @see MetaDataStorageSource::getMetaDataForEntities()
     * @param string[] $entityIds The entity ids to load
     * @param string $set The set we want to get metadata from.
     * @return array An associative array with the metadata for the requested entities, if found.
     */
    protected function getMetaDataForEntitiesIndividually(array $entityIds, string $set): array
    {
        $entities = [];
        foreach ($entityIds as $entityId) {
            $metadata = $this->getMetaData($entityId, $set);
            if ($metadata !== null) {
                $entities[$entityId] = $metadata;
            }
        }
        return $entities;
    }


    /**
     * This method returns the full metadata set for a given entity id or null if the entity id cannot be found
     * in the given metadata set.
     *
     * @param string $entityId
     * @param array $metadataSet the already loaded metadata set
     * @return mixed|null
     */
    protected function lookupIndexFromEntityId(string $entityId, array $metadataSet): mixed
    {
        // check for hostname
        $httpUtils = new Utils\HTTP();
        $currentHost = $httpUtils->getSelfHost(); // sp.example.org

        foreach ($metadataSet as $index => $entry) {
            // explicit index match
            if ($index === $entityId) {
                return $index;
            }

            if ($entry['entityid'] === $entityId) {
                if ($entry['host'] === '__DEFAULT__' || $entry['host'] === $currentHost) {
                    return $index;
                }
            }
        }

        return null;
    }
}
