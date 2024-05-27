<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use Exception;
use SimpleSAML\{Auth, Logger, Utils};
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\XML\saml\NameID;

use function array_key_exists;
use function hash;
use function is_bool;
use function sprintf;
use function strlen;

/**
 * Filter to generate the eduPersonTargetedID attribute.
 *
 * By default, this filter will generate the ID based on the UserID of the current user.
 * This is generated from the attribute configured in 'identifyingAttribute' in the
 * authproc-configuration.
 *
 * Example - generate from attribute:
 * <code>
 * 'authproc' => [
 *   50 => [
 *       'core:TargetedID',
 *       'identifyingAttribute' => 'mail',
 *   ]
 * ]
 * </code>
 *
 * @package SimpleSAMLphp
 */
class TargetedID extends Auth\ProcessingFilter
{
    /**
     * The attribute we should generate the targeted id from.
     *
     * @var string
     */
    private string $identifyingAttribute;

    /**
     * Whether the attribute should be generated as a NameID value, or as a simple string.
     *
     * @var boolean
     */
    private bool $generateNameId = false;

    /**
     * @var \SimpleSAML\Utils\Config
     */
    protected Utils\Config $configUtils;


    /**
     * Initialize this filter.
     *
     * @param array &$config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct(array &$config, $reserved)
    {
        parent::__construct($config, $reserved);

        Assert::keyExists($config, 'identifyingAttribute', "Missing mandatory 'identifyingAttribute' config setting.");
        Assert::stringNotEmpty(
            $config['identifyingAttribute'],
            "TargetedID: 'identifyingAttribute' must be a non-empty string.",
        );

        $this->identifyingAttribute = $config['identifyingAttribute'];

        if (array_key_exists('nameId', $config)) {
            if (!is_bool($config['nameId'])) {
                throw new Exception('Invalid value of \'nameId\'-option to core:TargetedID filter.');
            }
            $this->generateNameId = $config['nameId'];
        }

        $this->configUtils = new Utils\Config();
    }


    /**
     * Inject the \SimpleSAML\Utils\Config dependency.
     *
     * @param \SimpleSAML\Utils\Config $configUtils
     */
    public function setConfigUtils(Utils\Config $configUtils): void
    {
        $this->configUtils = $configUtils;
    }


    /**
     * Apply filter to add the targeted ID.
     *
     * @param array &$state  The current state.
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');
        if (!array_key_exists($this->identifyingAttribute, $state['Attributes'])) {
            Logger::warning(
                sprintf(
                    "core:TargetedID: Missing attribute '%s', which is needed to generate the TargetedID.",
                    $this->identifyingAttribute,
                ),
            );

            return;
        }

        $userID = $state['Attributes'][$this->identifyingAttribute][0];
        Assert::stringNotEmpty($userID);

        if (array_key_exists('Source', $state)) {
            $srcID = self::getEntityId($state['Source']);
        } else {
            $srcID = '';
        }

        if (array_key_exists('Destination', $state)) {
            $dstID = self::getEntityId($state['Destination']);
        } else {
            $dstID = '';
        }

        $secretSalt = $this->configUtils->getSecretSalt();
        $uidData = 'uidhashbase' . $secretSalt;
        $uidData .= strlen($srcID) . ':' . $srcID;
        $uidData .= strlen($dstID) . ':' . $dstID;
        $uidData .= strlen($userID) . ':' . $userID;
        $uidData .= $secretSalt;

        $uid = hash('sha1', $uidData);

        if ($this->generateNameId) {
            // Convert the targeted ID to a SAML 2.0 name identifier element
            $nameId = new NameID(
                value: $uid,
                Format: C::NAMEID_PERSISTENT,
                NameQualifier: $state['Source']['entityid'] ?? null,
                SPNameQualifier: $state['Destination']['entityid'] ?? null,
            );
        } else {
            $nameId = $uid;
        }

        $state['Attributes']['eduPersonTargetedID'] = [$nameId];
    }


    /**
     * Generate ID from entity metadata.
     *
     * This function takes in the metadata of an entity, and attempts to generate
     * an unique identifier based on that.
     *
     * @param array $metadata  The metadata of the entity.
     * @return string  The unique identifier for the entity.
     */
    private static function getEntityId(array $metadata): string
    {
        $id = '';

        if (array_key_exists('metadata-set', $metadata)) {
            $set = $metadata['metadata-set'];
            $id .= 'set' . strlen($set) . ':' . $set;
        }

        if (array_key_exists('entityid', $metadata)) {
            $entityid = $metadata['entityid'];
            $id .= 'set' . strlen($entityid) . ':' . $entityid;
        }

        return $id;
    }
}
