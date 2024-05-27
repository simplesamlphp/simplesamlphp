<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Process;

use SimpleSAML\{Error, Logger, Utils};
use SimpleSAML\Module\saml\BaseNameIDGenerator;
use SimpleSAML\SAML2\Constants as C;

use function array_values;
use function count;
use function sha1;
use function strlen;
use function var_export;

/**
 * Authentication processing filter to generate a persistent NameID.
 *
 * @package SimpleSAMLphp
 */

class PersistentNameID extends BaseNameIDGenerator
{
    /**
     * Which attribute contains the unique identifier of the user.
     *
     * @var string
     */
    private string $identifyingAttribute;


    /**
     * Initialize this filter, parse configuration.
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     *
     * @throws \SimpleSAML\Error\Exception If the required option 'identifyingAttribute' is missing.
     */
    public function __construct(array $config, $reserved)
    {
        parent::__construct($config, $reserved);

        $this->format = C::NAMEID_PERSISTENT;

        if (!isset($config['identifyingAttribute'])) {
            throw new Error\Exception("PersistentNameID: Missing required option 'identifyingAttribute'.");
        }
        $this->identifyingAttribute = $config['identifyingAttribute'];
    }


    /**
     * Get the NameID value.
     *
     * @param array $state The state array.
     * @return string|null The NameID value.
     */
    protected function getValue(array &$state): ?string
    {
        if (!isset($state['Destination']['entityid'])) {
            Logger::warning('No SP entity ID - not generating persistent NameID.');
            return null;
        }
        $spEntityId = $state['Destination']['entityid'];

        if (!isset($state['Source']['entityid'])) {
            Logger::warning('No IdP entity ID - not generating persistent NameID.');
            return null;
        }
        $idpEntityId = $state['Source']['entityid'];

        if (
            !isset($state['Attributes'][$this->identifyingAttribute])
            || count($state['Attributes'][$this->identifyingAttribute]) === 0
        ) {
            Logger::warning(
                'Missing attribute ' . var_export($this->identifyingAttribute, true) .
                ' on user - not generating persistent NameID.',
            );
            return null;
        }
        if (count($state['Attributes'][$this->identifyingAttribute]) > 1) {
            Logger::warning(
                'More than one value in attribute ' . var_export($this->identifyingAttribute, true) .
                ' on user - not generating persistent NameID.',
            );
            return null;
        }
        // just in case the first index is no longer 0
        $uid = array_values($state['Attributes'][$this->identifyingAttribute]);
        $uid = $uid[0];

        if (empty($uid)) {
            Logger::warning(
                'Empty value in attribute ' . var_export($this->identifyingAttribute, true) .
                ' on user - not generating persistent NameID.',
            );
            return null;
        }

        $configUtils = new Utils\Config();
        $secretSalt = $configUtils->getSecretSalt();

        $uidData = 'uidhashbase' . $secretSalt;
        $uidData .= strlen($idpEntityId) . ':' . $idpEntityId;
        $uidData .= strlen($spEntityId) . ':' . $spEntityId;
        $uidData .= strlen($uid) . ':' . $uid;
        $uidData .= $secretSalt;

        return sha1($uidData);
    }
}
