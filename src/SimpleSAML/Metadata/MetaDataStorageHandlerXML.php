<?php

declare(strict_types=1);

namespace SimpleSAML\Metadata;

use Exception;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;

use function array_key_exists;
use function count;

/**
 * This class implements a metadata source which loads metadata from XML files.
 * The XML files should be in the SAML 2.0 metadata format.
 *
 * @package SimpleSAMLphp
 */

class MetaDataStorageHandlerXML extends MetaDataStorageSource
{
    /**
     * This variable contains an associative array with the parsed metadata.
     *
     * @var array
     */
    private array $metadata;


    /**
     * This function initializes the XML metadata source. The configuration must contain one of
     * the following options:
     * - 'file': Path to a file with the metadata. This path is relative to the SimpleSAMLphp
     *           base directory.
     * - 'url': URL we should download the metadata from. This is only meant for testing.
     *
     * @param array $config The configuration for this instance of the XML metadata source.
     *
     * @throws \Exception If neither the 'file' or 'url' options are defined in the configuration.
     */
    protected function __construct(Configuration $globalConfig, array $config)
    {
        parent::__construct();

        $src = $srcXml = null;
        $context = [];
        if (array_key_exists('file', $config)) {
            // get the configuration
            $src = $globalConfig->resolvePath($config['file']);
        } elseif (array_key_exists('url', $config)) {
            $src = $config['url'];
            if (array_key_exists('context', $config)) {
                Assert::isArray($config['context']);
                $context = $config['context'];
            }
        } elseif (array_key_exists('xml', $config)) {
            $srcXml = $config['xml'];
        } else {
            throw new Exception("Missing one of 'file', 'url' and 'xml' in XML metadata source configuration.");
        }


        $SP20 = [];
        $IdP20 = [];
        $AAD = [];

        if (isset($src)) {
            $entities = SAMLParser::parseDescriptorsFile($src, $context);
        } elseif (isset($srcXml)) {
            $entities = SAMLParser::parseDescriptorsString($srcXml);
        } else {
            throw new Exception("Neither source file path/URI nor string data provided");
        }
        foreach ($entities as $entityId => $entity) {
            $md = $entity->getMetadata20SP();
            if ($md !== null) {
                $SP20[$entityId] = $md;
            }

            $md = $entity->getMetadata20IdP();
            if ($md !== null) {
                $IdP20[$entityId] = $md;
            }

            $md = $entity->getAttributeAuthorities();
            if (count($md) > 0) {
                $AAD[$entityId] = $md[0];
            }
        }

        $this->metadata = [
            'saml20-sp-remote'          => $SP20,
            'saml20-idp-remote'         => $IdP20,
            'attributeauthority-remote' => $AAD,
        ];
    }


    /**
     * This function returns an associative array with metadata for all entities in the given set. The
     * key of the array is the entity id.
     *
     * @param string $set The set we want to list metadata for.
     *
     * @return array An associative array with all entities in the given set.
     */
    public function getMetadataSet(string $set): array
    {
        if (array_key_exists($set, $this->metadata)) {
            return $this->metadata[$set];
        }

        // we don't have this metadata set
        return [];
    }
}
