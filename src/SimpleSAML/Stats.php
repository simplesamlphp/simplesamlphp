<?php

declare(strict_types=1);

namespace SimpleSAML;

use SimpleSAML\Assert\Assert;

use function bin2hex;
use function microtime;
use function openssl_random_pseudo_bytes;
use function sprintf;

/**
 * Statistics handler class.
 *
 * This class is responsible for taking a statistics event and logging it.
 *
 * @package SimpleSAMLphp
 */

class Stats
{
    /**
     * Whether this class is initialized.
     *
     * @var boolean
     */
    private static bool $initialized = false;


    /**
     * The statistics output callbacks.
     *
     * @var \SimpleSAML\Stats\Output[]
     */
    private static array $outputs = [];


    /**
     * Create an output from a configuration object.
     *
     * @param \SimpleSAML\Configuration $config The configuration.
     *
     * @return mixed A new instance of the configured class.
     */
    private static function createOutput(Configuration $config): mixed
    {
        $cls = $config->getString('class');
        $cls = Module::resolveClass($cls, 'Stats\Output', '\SimpleSAML\Stats\Output');

        $output = new $cls($config);
        return $output;
    }


    /**
     * Initialize the outputs.
     *
     */
    private static function initOutputs(): void
    {
        $config = Configuration::getInstance();
        $outputCfgs = $config->getOptionalArray('statistics.out', []);

        self::$outputs = [];
        foreach ($outputCfgs as $cfg) {
            self::$outputs[] = self::createOutput(Configuration::loadFromArray($cfg));
        }
    }


    /**
     * Notify about an event.
     *
     * @param string $event The event.
     * @param array  $data Event data. Optional.
     *
     * @return false|null
     */
    public static function log(string $event, array $data = []): bool|null
    {
        Assert::keyNotExists($data, 'op');
        Assert::keyNotExists($data, 'time');
        Assert::keyNotExists($data, '_id');

        if (!self::$initialized) {
            self::initOutputs();
            self::$initialized = true;
        }

        if (empty(self::$outputs)) {
            // not enabled
            return false;
        }

        $data['op'] = $event;
        $data['time'] = microtime(true);

        // the ID generation is designed to cluster IDs related in time close together
        $int_t = (int) $data['time'];
        $hd = openssl_random_pseudo_bytes(16);
        $data['_id'] = sprintf('%016x%s', $int_t, bin2hex($hd));

        foreach (self::$outputs as $out) {
            $out->emit($data);
        }

        return null;
    }
}
