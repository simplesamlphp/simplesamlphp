<?php

namespace SimpleSAML\Stats;

/**
 * Interface for statistics outputs.
 *
 * @package SimpleSAMLphp
 */

abstract class Output
{
    /**
     * Initialize the output.
     *
     * @param \SimpleSAML\Configuration $config The configuration for this output.
     */
    public function __construct(\SimpleSAML\Configuration $config)
    {
        // do nothing by default
    }


    /**
     * Write a stats event.
     *
     * @param array $data The event.
     */
    abstract public function emit(array $data);
}
