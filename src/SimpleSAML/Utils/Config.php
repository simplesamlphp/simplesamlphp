<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

use SimpleSAML\{Configuration, Error};

use function dirname;
use function getenv;
use function is_dir;
use function sprintf;

/**
 * Utility class for SimpleSAMLphp configuration management and manipulation.
 *
 * @package SimpleSAMLphp
 */
class Config
{
    /**
     * Resolves a path that may be relative to the cert-directory.
     *
     * @param string $path The (possibly relative) path to the file.
     *
     * @return string  The file path.
     * @throws \InvalidArgumentException If $path is not a string.
     *
     */
    public function getCertPath(string $path): string
    {
        $globalConfig = Configuration::getInstance();
        $base = $globalConfig->getPathValue('certdir', 'cert/');
        $sysUtils = new System();
        return $sysUtils->resolvePath($path, $base);
    }


    /**
     * Retrieve the secret salt.
     *
     * This function retrieves the value which is configured as the secret salt. It will check that the value exists
     * and is set to a non-default value. If it isn't, an exception will be thrown.
     *
     * The secret salt can be used as a component in hash functions, to make it difficult to test all possible values
     * in order to retrieve the original value. It can also be used as a simple method for signing data, by hashing the
     * data together with the salt.
     *
     * @return string The secret salt.
     * @throws \InvalidArgumentException If the secret salt hasn't been configured.
     *
     */
    public function getSecretSalt(): string
    {
        $secretSalt = Configuration::getInstance()->getString('secretsalt');
        if ($secretSalt === 'defaultsecretsalt') {
            throw new Error\CriticalConfigurationError(
                'The "secretsalt" configuration option must be set to a secret value.',
                'config.php',
            );
        } elseif (str_contains($secretSalt, '%')) {
            throw new Error\CriticalConfigurationError(
                'The "secretsalt" configuration option may not contain a `%` sign.',
            );
        }

        return $secretSalt;
    }

    /**
     * Returns the path to the config dir
     *
     * If the SIMPLESAMLPHP_CONFIG_DIR environment variable has been set, it takes precedence over the default
     * $simplesamldir/config directory.
     *
     * @return string The path to the configuration directory.
     */
    public function getConfigDir(): string
    {
        $configDir = dirname(__DIR__, 3) . '/config';
        $configDirEnv = getenv('SIMPLESAMLPHP_CONFIG_DIR');

        if ($configDirEnv === false) {
            $configDirEnv = getenv('REDIRECT_SIMPLESAMLPHP_CONFIG_DIR');
        }

        if ($configDirEnv !== false) {
            if (!is_dir($configDirEnv)) {
                throw new Error\CriticalConfigurationError(sprintf(
                    'Config directory specified by environment variable SIMPLESAMLPHP_CONFIG_DIR is not a ' .
                    'directory.  Given: "%s"',
                    $configDirEnv,
                ));
            }
            $configDir = $configDirEnv;
        }

        return $configDir;
    }
}
