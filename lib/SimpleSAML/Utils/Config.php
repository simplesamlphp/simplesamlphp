<?php


/**
 * Utility class for SimpleSAMLphp configuration management and manipulation.
 *
 * @package SimpleSAMLphp
 */
class SimpleSAML_Utils_Config
{

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
     *
     * @throws SimpleSAML_Error_Exception If the secret salt hasn't been configured.
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no> 
     */
    public static function getSecretSalt()
    {
        $secretSalt = SimpleSAML_Configuration::getInstance()->getString('secretsalt');
        if ($secretSalt === 'defaultsecretsalt') {
            throw new SimpleSAML_Error_Exception('The "secretsalt" configuration option must be set to a secret value.');
        }

        return $secretSalt;
    }
}