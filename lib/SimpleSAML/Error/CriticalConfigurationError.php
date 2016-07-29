<?php
/**
 * This exception represents a configuration error that we cannot recover from.
 *
 * Throwing a critical configuration error indicates that the configuration available is not usable, and as such
 * SimpleSAMLphp should not try to use it. However, in certain situations we might find a specific configuration
 * error that makes part of the configuration unusable, while the rest we can still use. In those cases, we can
 * just pass a configuration array to the constructor, making sure the offending configuration options are removed,
 * reset to defaults or guessed to some usable value.
 *
 * If, for example, we have an error in the 'baseurlpath' configuration option, we can still load the configuration
 * and substitute the value of that option with one guessed from the environment, using
 * \SimpleSAML\Utils\HTTP::guessPath(). Doing so, the error is still critical, but at least we can recover up to a
 * certain point and inform about the error in an ordered manner, without blank pages, logs out of place or even
 * segfaults.
 *
 * @author Jaime Perez Crespo, UNINETT AS <jaime.perez@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Error;


class CriticalConfigurationError extends ConfigurationError
{

    /**
     * This is the bare minimum configuration that we can use.
     *
     * @var array
     */
    private static $minimum_config = array(
        'logging.handler' => 'errorlog',
        'logging.level'  => \SimpleSAML\Logger::DEBUG,
        'errorreporting' => false,
        'debug'          => true,
    );


    /**
     * CriticalConfigurationError constructor.
     *
     * @param string|null $reason The reason for this critical error.
     * @param string|null $file The configuration file that originated this error.
     * @param array|null The configuration array that led to this problem.
     */
    public function __construct($reason = null, $file = null, $config = null)
    {
        if ($config === null) {
            $config = self::$minimum_config;
            $config['baseurlpath'] = \SimpleSAML\Utils\HTTP::guessBasePath();
        }

        \SimpleSAML_Configuration::loadFromArray(
            $config,
            '',
            'simplesaml'
        );
        parent::__construct($reason, $file);
    }


    /**
     * @param \Exception $exception
     *
     * @return CriticalConfigurationError
     */
    public static function fromException(\Exception $exception)
    {
        $reason = null;
        $file = null;
        if ($exception instanceof ConfigurationError) {
            $reason = $exception->getReason();
            $file = $exception->getConfFile();
        } else {
            $reason = $exception->getMessage();
        }
        return new CriticalConfigurationError($reason, $file);
    }
}
