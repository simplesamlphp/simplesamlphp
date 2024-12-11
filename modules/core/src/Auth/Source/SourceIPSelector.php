<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Source;

use SimpleSAML\{Error, Logger};
use SimpleSAML\Assert\Assert;
use Symfony\Component\HttpFoundation\{IpUtils, Request};

use function array_key_exists;
use function sprintf;

/**
 * Authentication source which delegates authentication to secondary
 * authentication sources based on the client's source IP
 *
 * @package simplesamlphp/simplesamlphp
 */
class SourceIPSelector extends AbstractSourceSelector
{
    /**
     * The key of the AuthId field in the state.
     */
    public const AUTHID = '\SimpleSAML\Module\core\Auth\Source\SourceIPSelector.AuthId';

    /**
     * The string used to identify our states.
     */
    public const STAGEID = '\SimpleSAML\Module\core\Auth\Source\SourceIPSelector.StageId';

    /**
     * The key where the sources is saved in the state.
     */
    public const SOURCESID = '\SimpleSAML\Module\core\Auth\Source\SourceIPSelector.SourceId';

    /**
     * @param string|null  The default authentication source to use when none of the zones match
     */
    protected ?string $defaultSource;

    /**
     * @param array  An array of zones. Each zone requires two keys;
     *               'source' containing the authsource for the zone,
     *               'subnet' containing an array of IP-ranges (CIDR notation).
     */
    protected array $zones = [];


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

        Assert::keyExists($config, 'zones');
        Assert::keyExists($config['zones'], 'default');
        Assert::nullOrStringNotEmpty($config['zones']['default']);
        $this->defaultSource = $config['zones']['default'];

        unset($config['zones']['default']);
        $zones = $config['zones'];

        foreach ($zones as $key => $zone) {
            if (!array_key_exists('source', $zone)) {
                throw new Error\Exception(
                    sprintf("Incomplete zone-configuration '%s' due to missing `source` key.", $key),
                );
            } elseif (!array_key_exists('subnet', $zone)) {
                throw new Error\Exception(
                    sprintf("Incomplete zone-configuration '%s' due to missing `subnet` key.", $key),
                );
            } else {
                $this->zones[$key] = $zone;
            }
        }
    }


    /**
     * Decide what authsource to use.
     *
     * @param array &$state Information about the current authentication.
     * @return string
     */
    protected function selectAuthSource(/** @scrutinizer ignore-unused */ array &$state): string
    {
        $ip = Request::createFromGlobals()->getClientIp();
        Assert::notNull($ip, "Unable to determine client IP.");

        $state['sourceIPSelector:zone'] = 'default';
        $source = $this->defaultSource;
        foreach ($this->zones as $name => $zone) {
            foreach ($zone['subnet'] as $subnet) {
                if (IpUtils::checkIp($ip, $subnet)) {
                    // Client's IP is in one of the ranges for the secondary auth source
                    Logger::info(sprintf(
                        "core:SourceIPSelector:  Selecting zone `%s` based on client IP %s",
                        $name,
                        $ip,
                    ));
                    $source = $zone['source'];
                    break;
                }
            }
        }

        if ($source === null) {
            throw new Error\NotFound();
        }

        if ($source === $this->defaultSource) {
            Logger::info("core:SourceIPSelector:  no match on client IP; selecting default zone");
        }

        return $source;
    }
}
