<?php

declare(strict_types=1);

namespace SimpleSAML\Metadata;

use SimpleSAML\{Configuration, Logger, Utils};
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;

use function array_key_exists;
use function rawurlencode;
use function serialize;
use function sprintf;
use function strlen;
use function substr;
use function unserialize;
use function var_export;

/**
 * Class for handling metadata files in serialized format.
 *
 * @package SimpleSAMLphp
 */

class MetaDataStorageHandlerSerialize extends MetaDataStorageSource
{
    /**
     * The file extension we use for our metadata files.
     *
     * @var string
     */
    public const EXTENSION = '.serialized';


    /**
     * The base directory where metadata is stored.
     *
     * @var string
     */
    private string $directory = '/';


    /**
     * Constructor for this metadata handler.
     *
     * Parses configuration.
     *
     * @param array $config The configuration for this metadata handler.
     */
    public function __construct(Configuration $globalConfig, array $config)
    {
        parent::__construct();

        $cfgHelp = Configuration::loadFromArray($config, 'serialize metadata source');
        $this->directory = $cfgHelp->getString('directory');

        /* Resolve this directory relative to the SimpleSAMLphp directory (unless it is
         * an absolute path).
         */
        $sysUtils = new Utils\System();
        $this->directory = $sysUtils->resolvePath($this->directory, $globalConfig->getBaseDir());
    }


    /**
     * Helper function for retrieving the path of a metadata file.
     *
     * @param string $entityId The entity ID.
     * @param string $set The metadata set.
     *
     * @return string The path to the metadata file.
     */
    private function getMetadataPath(string $entityId, string $set): string
    {
        return $this->directory . '/' . rawurlencode($set) . '/' . rawurlencode($entityId) . self::EXTENSION;
    }


    /**
     * Retrieve a list of all available metadata sets.
     *
     * @return array An array with the available sets.
     */
    public function getMetadataSets(): array
    {
        $ret = [];

        $loc = new File($this->directory, false);
        if (!$this->fileSystem->exists($this->directory) || !$loc->isReadable()) {
            Logger::warning(
                'Serialize metadata handler: Unable to open directory: ' . var_export($this->directory, true),
            );
            return $ret;
        }

        $finder = new Finder();
        $finder->directories()->name(sprintf('/%s$/', self::EXTENSION))->in($this->directory);

        $ret = [];
        foreach ($finder as $file) {
            $ret[] = rawurlencode($file->getPathName());
        }

        return $ret;
    }


    /**
     * Retrieve a list of all available metadata for a given set.
     *
     * @param string $set The set we are looking for metadata in.
     *
     * @return array An associative array with all the metadata for the given set.
     */
    public function getMetadataSet(string $set): array
    {
        $ret = [];

        $loc = new File(Path::canonicalize($this->directory . '/' . rawurlencode($set)), false);
        if (!$this->fileSystem->exists($loc->getPath()) || !$loc->isReadable()) {
            Logger::warning(sprintf(
                'Serialize metadata handler: Unable to open directory: %s',
                var_export($loc->getPathName(), true),
            ));
            return $ret;
        }

        $extLen = strlen(self::EXTENSION);

        $finder = new Finder();
        $finder->files()->name('*' .  self::EXTENSION)->in($this->directory . DIRECTORY_SEPARATOR . $set);

        $ret = [];
        foreach ($finder as $file) {
            $entityId = substr($file->getFileName(), 0, -$extLen);
            $entityId = rawurldecode($entityId);

            $md = $this->getMetaData($entityId, $set);
            if ($md !== null) {
                $ret[$entityId] = $md;
            }
        }

        return $ret;
    }


    /**
     * Retrieve a metadata entry.
     *
     * @param string $entityId The entityId we are looking up.
     * @param string $set The set we are looking for metadata in.
     *
     * @return array|null An associative array with metadata for the given entity, or NULL if we are unable to
     *         locate the entity.
     */
    public function getMetaData(string $entityId, string $set): ?array
    {
        $filePath = $this->getMetadataPath($entityId, $set);

        if (!$this->fileSystem->exists($filePath)) {
            return null;
        }

        $file = new File($filePath);
        try {
            $data = $file->getContent();
        } catch (IOException $e) {
            Logger::warning('Error reading file ' . $filePath . ': ' . $e->getMessage());
            return null;
        }

        $data = @unserialize($data);
        if ($data === false) {
            Logger::warning('Error unserializing file: ' . $filePath);
            return null;
        }

        if (!array_key_exists('entityid', $data)) {
            $data['entityid'] = $entityId;
        }

        return $data;
    }


    /**
     * Save a metadata entry.
     *
     * @param string $entityId The entityId of the metadata entry.
     * @param string $set The metadata set this metadata entry belongs to.
     * @param array $metadata The metadata.
     *
     * @return bool True if successfully saved, false otherwise.
     */
    public function saveMetadata(string $entityId, string $set, array $metadata): bool
    {
        $old = new File($this->getMetadataPath($entityId, $set), false);
        $new = new File($old->getPathName() . '.new', false);

        $loc = new File($old->getPath(), false);
        if (!$loc->isDir()) {
            Logger::info('Creating directory: ' . $loc);
            try {
                $this->fileSystem->mkdir($loc->getPath(), 0777);
            } catch (IOException $e) {
                Logger::error('Failed to create directory ' . $loc . ': ' . $e->getMessage());
                return false;
            }
        }

        $data = serialize($metadata);

        Logger::debug('Writing: ' . $new->getPathName());

        try {
            $this->fileSystem->appendToFile($new->getPathName(), $data);
        } catch (IOException $e) {
            Logger::error('Error saving file ' . $new->getPathName() . ': ' . $e->getMessage());
            return false;
        }

        try {
            $this->fileSystem->rename($new->getPathName(), $old->getPathName(), true);
        } catch (IOException $e) {
            Logger::error(
                sprintf('Error renaming %s to %s: %s', $new->getPathName(), $old->getPathName(), $e->getMessage()),
            );
            return false;
        }

        return true;
    }


    /**
     * Delete a metadata entry.
     *
     * @param string $entityId The entityId of the metadata entry.
     * @param string $set The metadata set this metadata entry belongs to.
     */
    public function deleteMetadata(string $entityId, string $set): void
    {
        $filePath = $this->getMetadataPath($entityId, $set);

        if (!$this->fileSystem->exists($filePath)) {
            Logger::warning(
                'Attempted to erase nonexistent metadata entry ' .
                var_export($entityId, true) . ' in set ' . var_export($set, true) . '.',
            );
            return;
        }

        try {
            $this->fileSystem->remove($filePath);
        } catch (IOException $e) {
            Logger::error(sprintf(
                'Failed to delete file %s: %s',
                $filePath,
                $e->getMessage(),
            ));
        }
    }


    /**
     * This function loads the metadata for entity IDs in $entityIds. It is returned as an associative array
     * where the key is the entity id. An empty array may be returned if no matching entities were found
     * @param array $entityIds The entity ids to load
     * @param string $set The set we want to get metadata from.
     * @return array An associative array with the metadata for the requested entities, if found.
     */
    public function getMetaDataForEntities(array $entityIds, string $set): array
    {
        return $this->getMetaDataForEntitiesIndividually($entityIds, $set);
    }
}
