<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Stats\Output;

use Exception;
use SimpleSAML\{Configuration, Logger};

use function call_user_func;
use function is_callable;
use function json_encode;
use function var_export;

/**
 * Statistics logger that writes to the default logging handler.
 *
 * @package SimpleSAMLphp
 */
class Log extends \SimpleSAML\Stats\Output
{
    /**
     * The logging function we should call.
     * @var callable
     */
    private $logger;


    /**
     * Initialize the output.
     *
     * @param \SimpleSAML\Configuration $config  The configuration for this output.
     * @throws \Exception
     */
    public function __construct(Configuration $config)
    {
        $logLevel = $config->getOptionalString('level', 'notice');
        $this->logger = [Logger::class, $logLevel];
        if (!is_callable($this->logger)) {
            throw new Exception('Invalid log level: ' . var_export($logLevel, true));
        }
    }


    /**
     * Write a stats event.
     *
     * @param array $data  The event
     */
    public function emit(array $data): void
    {
        $str_data = json_encode($data);
        call_user_func($this->logger, 'EVENT ' . $str_data);
    }
}
