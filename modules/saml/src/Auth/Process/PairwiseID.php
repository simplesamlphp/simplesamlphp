<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Process;

use SAML2\Constants;
use SimpleSAML\Assert\Assert;
use SimpleSAML\{Auth, Utils};

/**
 * Filter to generate the Pairwise ID attribute.
 *
 * See: http://docs.oasis-open.org/security/saml-subject-id-attr/v1.0/csprd01/saml-subject-id-attr-v1.0-csprd01.html
 *
 * By default, this filter will generate the ID based on the UserID of the current user.
 * This is generated from the attribute configured in 'identifyingAttribute' in the
 * authproc-configuration.
 *
 * NOTE: since the subject-id is specified as single-value attribute, only the first value of `identifyingAttribute`
 *       and `scopeAttribute` are considered.
 *
 * Example - generate from attribute:
 * <code>
 * 'authproc' => [
 *   50 => [
 *       'saml:PairwiseID',
 *       'identifyingAttribute' => 'uid',
 *       'scopeAttribute' => 'example.org',
 *   ]
 * ]
 * </code>
 *
 * @package SimpleSAMLphp
 */
class PairwiseID extends SubjectID
{
    /**
     * The name for this class
     */
    public const NAME = 'PairwiseID';

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

        $this->configUtils = new Utils\Config();
    }


    /**
     * Apply filter to add the Pairwise ID.
     *
     * @param array &$state  The current state.
     */
    public function process(array &$state): void
    {
        $userID = $this->getIdentifyingAttribute($state);
        $scope = $this->getScopeAttribute($state);

        if ($scope === null || $userID === null) {
            // Attributes missing, precondition not met
            return;
        }

        if (!empty($state['saml:RequesterID'])) {
            // Proxied request - use actual SP entity ID
            $sp_entityid = $state['saml:RequesterID'][0];
        } else {
            $sp_entityid = $state['core:SP'];
        }

        // Calculate hash
        $salt = $this->configUtils->getSecretSalt();
        $hash = hash_hmac('sha256', $userID . '|' . $sp_entityid, $salt, false);

        $value = $hash . '@' . strtolower($scope);
        $this->validateGeneratedIdentifier($value);

        $state['Attributes'][Constants::ATTR_PAIRWISE_ID] = [$value];
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
}
