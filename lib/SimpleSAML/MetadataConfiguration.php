<?php


/**
 * Metadata configuration of SimpleSAMLphp
 *
 * @author Andreas Aakre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */
class SimpleSAML_MetadataConfiguration extends \SimpleSAML_Configuration
{
    /**
     * Initializes a configuration from the given array.
     *
     * @param array $config The configuration array.
     * @param string $location The location which will be given when an error occurs.
     */
    public function __construct($config, $location)
    {
        assert(is_array($config));
        assert(is_string($location));

        parent::__construct($config, $location);
    }


    /**
     * Retrieve the default binding for the given endpoint type.
     *
     * This function combines the current metadata type (SAML 2 / SAML 1.1)
     * with the endpoint type to determine which binding is the default.
     *
     * @param string $endpointType The endpoint type.
     *
     * @return string The default binding.
     *
     * @throws Exception If the default binding is missing for this endpoint type.
     */
    private function getDefaultBinding($endpointType)
    {
        assert(is_string($endpointType));

        $set = $this->getString('metadata-set');
        switch ($set.':'.$endpointType) {
            case 'saml20-idp-remote:SingleSignOnService':
            case 'saml20-idp-remote:SingleLogoutService':
            case 'saml20-sp-remote:SingleLogoutService':
                return \SAML2\Constants::BINDING_HTTP_REDIRECT;
            case 'saml20-sp-remote:AssertionConsumerService':
                return \SAML2\Constants::BINDING_HTTP_POST;
            case 'saml20-idp-remote:ArtifactResolutionService':
                return \SAML2\Constants::BINDING_SOAP;
            case 'shib13-idp-remote:SingleSignOnService':
                return 'urn:mace:shibboleth:1.0:profiles:AuthnRequest';
            case 'shib13-sp-remote:AssertionConsumerService':
                return 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post';
            default:
                throw new Exception('Missing default binding for '.$endpointType.' in '.$set);
        }
    }


    /**
     * Helper function for dealing with metadata endpoints.
     *
     * @param string $endpointType The endpoint type.
     *
     * @return array Array of endpoints of the given type.
     *
     * @throws Exception If any element of the configuration options for this endpoint type is incorrect.
     */
    public function getEndpoints($endpointType)
    {
        assert(is_string($endpointType));

        $loc = $this->getLocation().'['.var_export($endpointType, true).']:';

        $configuration = $this->toArray();
        if (!array_key_exists($endpointType, $configuration)) {
            // no endpoints of the given type
            return array();
        }


        $eps = $configuration[$endpointType];
        if (is_string($eps)) {
            // for backwards-compatibility
            $eps = array($eps);
        } elseif (!is_array($eps)) {
            throw new Exception($loc.': Expected array or string.');
        }


        foreach ($eps as $i => &$ep) {
            $iloc = $loc.'['.var_export($i, true).']';

            if (is_string($ep)) {
                // for backwards-compatibility
                $ep = array(
                    'Location' => $ep,
                    'Binding'  => $this->getDefaultBinding($endpointType),
                );
                $responseLocation = $this->getString($endpointType.'Response', null);
                if ($responseLocation !== null) {
                    $ep['ResponseLocation'] = $responseLocation;
                }
            } elseif (!is_array($ep)) {
                throw new Exception($iloc.': Expected a string or an array.');
            }

            if (!array_key_exists('Location', $ep)) {
                throw new Exception($iloc.': Missing Location.');
            }
            if (!is_string($ep['Location'])) {
                throw new Exception($iloc.': Location must be a string.');
            }

            if (!array_key_exists('Binding', $ep)) {
                throw new Exception($iloc.': Missing Binding.');
            }
            if (!is_string($ep['Binding'])) {
                throw new Exception($iloc.': Binding must be a string.');
            }

            if (array_key_exists('ResponseLocation', $ep)) {
                if (!is_string($ep['ResponseLocation'])) {
                    throw new Exception($iloc.': ResponseLocation must be a string.');
                }
            }

            if (array_key_exists('index', $ep)) {
                if (!is_int($ep['index'])) {
                    throw new Exception($iloc.': index must be an integer.');
                }
            }
        }

        return $eps;
    }


    /**
     * Find an endpoint of the given type, using a list of supported bindings as a way to prioritize.
     *
     * @param string $endpointType The endpoint type.
     * @param array  $bindings Sorted array of acceptable bindings.
     * @param mixed  $default The default value to return if no matching endpoint is found. If no default is provided,
     *     an exception will be thrown.
     *
     * @return array|null The default endpoint, or null if no acceptable endpoints are used.
     *
     * @throws Exception If no supported endpoint is found.
     */
    public function getEndpointPrioritizedByBinding($endpointType, array $bindings, $default = self::REQUIRED_OPTION)
    {
        assert(is_string($endpointType));

        $endpoints = $this->getEndpoints($endpointType);

        foreach ($bindings as $binding) {
            foreach ($endpoints as $ep) {
                if ($ep['Binding'] === $binding) {
                    return $ep;
                }
            }
        }

        if ($default === self::REQUIRED_OPTION) {
            $loc = $this->getLocation().'['.var_export($endpointType, true).']:';
            throw new Exception($loc.'Could not find a supported '.$endpointType.' endpoint.');
        }

        return $default;
    }


    /**
     * Find the default endpoint of the given type.
     *
     * @param string $endpointType The endpoint type.
     * @param array  $bindings Array with acceptable bindings. Can be null if any binding is allowed.
     * @param mixed  $default The default value to return if no matching endpoint is found. If no default is provided,
     *     an exception will be thrown.
     *
     * @return array|null The default endpoint, or null if no acceptable endpoints are used.
     *
     * @throws Exception If no supported endpoint is found.
     */
    public function getDefaultEndpoint($endpointType, array $bindings = null, $default = self::REQUIRED_OPTION)
    {
        assert(is_string($endpointType));

        $endpoints = $this->getEndpoints($endpointType);

        $defaultEndpoint = \SimpleSAML\Utils\Config\Metadata::getDefaultEndpoint($endpoints, $bindings);
        if ($defaultEndpoint !== null) {
            return $defaultEndpoint;
        }

        if ($default === self::REQUIRED_OPTION) {
            $loc = $this->getLocation().'['.var_export($endpointType, true).']:';
            throw new Exception($loc.'Could not find a supported '.$endpointType.' endpoint.');
        }

        return $default;
    }


    /**
     * Retrieve a string which may be localized into many languages.
     *
     * The default language returned is always 'en'.
     *
     * @param string $name The name of the option.
     * @param mixed  $default The default value. If no default is given, and the option isn't found, an exception will
     *     be thrown.
     *
     * @return array Associative array with language => string pairs.
     *
     * @throws Exception If the translation is not an array or a string, or its index or value are not strings.
     */
    public function getLocalizedString($name, $default = self::REQUIRED_OPTION)
    {
        assert(is_string($name));

        $ret = $this->getValue($name, $default);
        if ($ret === $default) {
            // the option wasn't found, or it matches the default value. In any case, return this value
            return $ret;
        }

        $loc = $this->getLocation().'['.var_export($name, true).']';

        if (is_string($ret)) {
            $ret = array('en' => $ret,);
        }

        if (!is_array($ret)) {
            throw new Exception($loc.': Must be an array or a string.');
        }

        foreach ($ret as $k => $v) {
            if (!is_string($k)) {
                throw new Exception($loc.': Invalid language code: '.var_export($k, true));
            }
            if (!is_string($v)) {
                throw new Exception($loc.'['.var_export($v, true).']: Must be a string.');
            }
        }

        return $ret;
    }


    /**
     * Get public key from metadata.
     *
     * @param string|null $use The purpose this key can be used for. (encryption or signing).
     * @param bool $required Whether the public key is required. If this is true, a
     *                       missing key will cause an exception. Default is false.
     * @param string $prefix The prefix which should be used when reading from the metadata
     *                       array. Defaults to ''.
     *
     * @return array Public key data, or empty array if no public key or was found.
     *
     * @throws Exception If the certificate or public key cannot be loaded from a file.
     * @throws SimpleSAML_Error_Exception If the file does not contain a valid PEM-encoded certificate, or there is no
     * certificate in the metadata.
     */
    public function getPublicKeys($use = null, $required = false, $prefix = '')
    {
        assert(is_bool($required));
        assert(is_string($prefix));

        if ($this->hasValue($prefix.'keys')) {
            $ret = array();
            foreach ($this->getArray($prefix.'keys') as $key) {
                if ($use !== null && isset($key[$use]) && !$key[$use]) {
                    continue;
                }
                if (isset($key['X509Certificate'])) {
                    // Strip whitespace from key
                    $key['X509Certificate'] = preg_replace('/\s+/', '', $key['X509Certificate']);
                }
                $ret[] = $key;
            }
            return $ret;
        } elseif ($this->hasValue($prefix.'certData')) {
            $certData = $this->getString($prefix.'certData');
            $certData = preg_replace('/\s+/', '', $certData);
            return array(
                array(
                    'encryption'      => true,
                    'signing'         => true,
                    'type'            => 'X509Certificate',
                    'X509Certificate' => $certData,
                ),
            );
        } elseif ($this->hasValue($prefix.'certificate')) {
            $file = $this->getString($prefix.'certificate');
            $file = \SimpleSAML\Utils\Config::getCertPath($file);
            $data = @file_get_contents($file);

            if ($data === false) {
                throw new Exception($this->getLocation().': Unable to load certificate/public key from file "'.$file.'".');
            }

            // extract certificate data (if this is a certificate)
            $pattern = '/^-----BEGIN CERTIFICATE-----([^-]*)^-----END CERTIFICATE-----/m';
            if (!preg_match($pattern, $data, $matches)) {
                throw new SimpleSAML_Error_Exception(
                    $this->getLocation().': Could not find PEM encoded certificate in "'.$file.'".'
                );
            }
            $certData = preg_replace('/\s+/', '', $matches[1]);

            return array(
                array(
                    'encryption'      => true,
                    'signing'         => true,
                    'type'            => 'X509Certificate',
                    'X509Certificate' => $certData,
                ),
            );
        } elseif ($required === true) {
            throw new SimpleSAML_Error_Exception($this->getLocation().': Missing certificate in metadata.');
        } else {
            return array();
        }
    }
}
