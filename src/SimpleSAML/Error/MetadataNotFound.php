<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

/**
 * Error for missing metadata.
 *
 * @package SimpleSAMLphp
 */

class MetadataNotFound extends Error
{
    /**
     * Create the error
     *
     * @param string $entityId  The entityID we were unable to locate.
     */
    public function __construct(string $entityId)
    {
        $this->includeTemplate = 'core:no_metadata.twig';
        parent::__construct([
                ErrorCodes::METADATANOTFOUND,
                '%ENTITYID%' => $entityId,
        ]);
    }
}
