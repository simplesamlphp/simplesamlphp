<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Source;

use Exception;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Logger;
use SimpleSAML\Session;
use SimpleSAML\Utils;

/**
 * Authentication source which delegates authentication to secondary
 * authentication sources based on the client's source IP
 *
 * @package simplesamlphp/simplesamlphp
 */
class IPSourceSelector extends AbstractSourceSelector
{
    /**
     * The key of the AuthId field in the state.
     */
    public const AUTHID = '\SimpleSAML\Module\core\Auth\Source\IPSourceSelector.AuthId';

    /**
     * The string used to identify our states.
     */
    public const STAGEID = '\SimpleSAML\Module\core\Auth\Source\IPSourceSelector.StageId';

    /**
     * The key where the sources is saved in the state.
     */
    public const SOURCESID = '\SimpleSAML\Module\core\Auth\Source\IPSourceSelector.SourceId';

    /**
     * @param string  The name of the primary authsource
     */
    protected string $primarySource;

    /**
     * @param string  The name of the secondary authsource
     */
    protected string $secondarySource;

    /**
     * @param array  The IP-ranges (CIDR notation) for the primary authsource
     */
    protected array $ipRanges;


    /**
     * Constructor for this authentication source.
     *
     * @param array $info Information about this authentication source.
     * @param array $config Configuration.
     */
    public function __construct(array $info, array $config)
    {
        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        Assert::keyExists($config, 'primarySource');
        Assert::stringNotEmpty($config['primarySource']);
        $this->primarySource = $config['primarySource'];

        Assert::keyExists($config, 'secondarySource');
        Assert::stringNotEmpty($config['secondarySource']);
        $this->secondarySource = $config['secondarySource'];

        Assert::keyExists($config, 'secondarySourceRanges');
        $this->ipRanges = $config['secondarySourceRanges'];
    }


    /**
     * Decide what authsource to use.
     *
     * @param array &$state Information about the current authentication.
     * @return string
     */
    protected function selectAuthSource(): string
    {
        $netUtils = new Utils\Net();

        $ip = $_SERVER['REMOTE_ADDR'];
        $source = $this->primarySource;
        foreach ($this->ipRanges as $range) {
            if ($netUtils->ipCIDRcheck($range, $ip)) {
                // Client's IP is in one of the ranges for the secondary auth source
                $source = $this->secondarySource;
                break;
            }
        }

        Logger::info(sprintf("core:IPSourceSelector:  Selecting authsource `%s` based on client IP %s", $source, $ip));
        return $source;
    }
}
