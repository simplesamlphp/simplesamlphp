<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

use SimpleSAML\Auth\Source;
use SimpleSAML\Error\Exception;
use SimpleSAML\Utils;

class AuthSource
{
    protected Utils $utils;

    public function __construct(Utils $utils = null)
    {
        $this->utils = $utils ?? new Utils();
    }

    /**
     * Retrieve authentication source.
     *
     * This function takes an id of an authentication source, and returns the
     * AuthSource object. If no authentication source with the given id can be found,
     * NULL will be returned.
     *
     * If the $type parameter is specified, this function will return an
     * authentication source of the given type. If no authentication source or if an
     * authentication source of a different type is found, an exception will be thrown.
     *
     * @param string      $authId The authentication source identifier.
     * @param string|null $type The type of authentication source. If NULL, any type will be accepted.
     *
     * @return Source|null The AuthSource object, or NULL if no authentication
     *     source with the given identifier is found.
     * @throws Exception If no such authentication source is found or it is invalid.
     */
    public function getById(string $authId, ?string $type = null): ?Source
    {
        // TODO NextMajorRelease Move content from Source::getById() to this method.
        return Source::getById($authId, $type);
    }
}
