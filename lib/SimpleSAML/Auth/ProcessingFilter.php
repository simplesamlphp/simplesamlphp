<?php

declare(strict_types=1);

namespace SimpleSAML\Auth;

use SimpleSAML\Assert\Assert;

/**
 * Base class for authentication processing filters.
 *
 * All authentication processing filters must support serialization.
 *
 * The current request is stored in an associative array. It has the following defined attributes:
 * - 'Attributes'  The attributes of the user.
 * - 'Destination'  Metadata of the destination (SP).
 * - 'Source'  Metadata of the source (IdP).
 *
 * It may also contain other attributes. If an authentication processing filter wishes to store other
 * information in it, it should have a name on the form 'module:filter:attributename', to avoid name
 * collisions.
 *
 * @package SimpleSAMLphp
 */

abstract class ProcessingFilter
{
    /**
     * Priority of this filter.
     *
     * Used when merging IdP and SP processing chains.
     * The priority can be any integer. The default for most filters is 50. Filters may however
     * specify their own default, if they typically should be amongst the first or the last filters.
     *
     * The priority can also be overridden by the user by specifying the '%priority' option.
     */
    public int $priority = 50;


    /**
     * Constructor for a processing filter.
     *
     * Any processing filter which implements its own constructor must call this
     * constructor first.
     *
     * @param array &$config  Configuration for this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct(array &$config, /** @scrutinizer ignore-unused */ $reserved)
    {
        if (array_key_exists('%priority', $config)) {
            $this->priority = $config['%priority'];
            unset($config['%priority']);
        }
    }


    /**
     * Process a request.
     *
     * When a filter returns from this function, it is assumed to have completed its task.
     *
     * @param array &$state  The request we are currently processing.
     */
    abstract public function process(array &$state): void;
}
