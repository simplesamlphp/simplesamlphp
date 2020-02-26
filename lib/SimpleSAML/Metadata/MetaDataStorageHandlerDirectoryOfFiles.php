<?php

declare(strict_types=1);

namespace SimpleSAML\Metadata;

use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use Webmozart\Assert\Assert;

/**
 * This file defines a directory of files metadata source.
 * Instantiation of session handler objects should be done through
 * the class method getMetadataHandler().
 *
 * @author Philipp Kolmann, TU Wien <philipp@kolmann.at>
 * @package SimpleSAMLphp
 *
 * Based on MetaDataStorageHandlerFlatFile by Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no> and
 * MetaDataStorageHandlerSerialize.php
 */

class MetaDataStorageHandlerDirectoryOfFiles extends MetaDataStorageSource
{
    /**
     * This is the directory we will load metadata files from. The path will always end
     * with a '/'.
     *
     * @var string
     */
    private $directory = '/';


    /**
     * This is an associative array which stores the different metadata sets we have loaded.
     *
     * @var array
     */
    private $cachedMetadata = [];

    /**
     * The extension we use for our metadata directories.
     *
     * @var string
     */
    const EXTENSION = '.d';


    /**
     * This constructor initializes the directory of files metadata storage handler with the
     * specified configuration. The configuration is an associative array with the following
     * possible elements:
     * - 'directory': The directory we should load metadata from. The default directory is
     *                set in the 'metadatadir' configuration option in 'config.php'.
     *
     * @param array $config An associative array with the configuration for this handler.
     */
    protected function __construct(array $config)
    {
        // get the configuration
        $globalConfig = Configuration::getInstance();

        // find the path to the directory we should search for metadata in
        if (array_key_exists('directory', $config)) {
            $this->directory = $config['directory'] ?: 'metadata/';
        } else {
            $this->directory = $globalConfig->getString('metadatadir', 'metadata/');
        }

        /* Resolve this directory relative to the SimpleSAMLphp directory (unless it is
         * an absolute path).
         */

        /** @var string $base */
        $base = $globalConfig->resolvePath($this->directory);
        $this->directory = $base . '/';
    }


    /**
     * This function loads the given set of metadata from a file our metadata directory.
     * This function returns null if it is unable to locate the given set in the metadata directory.
     *
     * @param string $set The set of metadata we are loading.
     *
     * @return array|null An associative array with the metadata,
     *     or null if we are unable to load metadata from the given file.
     * @throws \Exception If the metadata set cannot be loaded.
     */
    private function load(string $set): ?array
    {
        $metadatasetdir = $this->directory . $set . self::EXTENSION;

        /** @psalm-var mixed $metadata   We cannot be sure what the include below will do with this var */
        $metadata = [];

        $dh = @opendir($metadatasetdir);
        if ($dh === false) {
            Logger::warning(
                'Directory metadata handler: Unable to open directory: ' . var_export($metadatasetdir, true)
            );
            return $metadata;
        }

        while (($entry = readdir($dh)) !== false) {
            if ($entry[0] === '.') {
                // skip '..', '.' and hidden files
                continue;
            }

            $path = $metadatasetdir.DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path)) {
                Logger::warning(
                    'Directory metadata handler: Metadata directory contained a directory where only files should ' .
                    'exist: ' . var_export($path, true)
                );
                continue;
            }

            file_put_contents('/tmp/pk', "including: ".$path."\n", FILE_APPEND);
            include($path);
        }

        closedir($dh);

        if (!is_array($metadata)) {
            throw new \Exception('Could not load metadata set [' . $set . '] from directory: ' . $metadatasetdir);
        }

        return $metadata;
    }


    /**
     * This function retrieves the given set of metadata. It will return an empty array if it is
     * unable to locate it.
     *
     * @param string $set The set of metadata we are retrieving.
     *
     * @return array An associative array with the metadata. Each element in the array is an entity, and the
     *         key is the entity id.
     */
    public function getMetadataSet(string $set): array
    {
        if (array_key_exists($set, $this->cachedMetadata)) {
            return $this->cachedMetadata[$set];
        }

        $metadataSet = $this->load($set);
        if ($metadataSet === null) {
            $metadataSet = [];
        }

        // add the entity id of an entry to each entry in the metadata
        foreach ($metadataSet as $entityId => &$entry) {
            $entry = $this->updateEntityID($set, $entityId, $entry);
        }

        $this->cachedMetadata[$set] = $metadataSet;
        return $metadataSet;
    }
}
