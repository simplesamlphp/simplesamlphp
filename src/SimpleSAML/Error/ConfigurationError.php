<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use function dirname;
use function str_replace;

/**
 * This exception represents a configuration error.
 *
 * @package SimpleSAMLphp
 */

class ConfigurationError extends Error
{
    /**
     * The reason for this exception.
     *
     * @var null|string
     */
    protected ?string $reason;

    /**
     * The configuration file that caused this exception.
     *
     * @var null|string
     */
    protected ?string $config_file;


    /**
     * ConfigurationError constructor.
     *
     * @param string|null $reason The reason for this exception.
     * @param string|null $file The configuration file that originated this error.
     * @param array|null $config The configuration array that led to this problem.
     */
    public function __construct(?string $reason = null, ?string $file = null, ?array $config = null)
    {
        $file_str = '';
        $reason_str = '.';
        $params = [ErrorCodes::CONFIG];
        if ($file !== null) {
            $params['%FILE%'] = $file;
            $basepath = dirname(__FILE__, 4) . '/';
            $file_str = '(' . str_replace($basepath, '', $file) . ') ';
        }
        if ($reason !== null) {
            $params['%REASON%'] = $reason;
            $reason_str = ': ' . $reason;
        }
        $this->reason = $reason;
        $this->config_file = $file;
        parent::__construct($params);
        $this->message = 'The configuration ' . $file_str . 'is invalid' . $reason_str;
    }


    /**
     * Get the reason for this exception.
     *
     * @return null|string The reason for this exception.
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }


    /**
     * Get the configuration file that caused this exception.
     *
     * @return null|string The configuration file that caused this exception.
     */
    public function getConfFile(): ?string
    {
        return $this->config_file;
    }
}
