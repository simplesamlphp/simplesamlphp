<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Process;

use SimpleSAML\SAML2\Constants as C;

use function strtolower;

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
     *
     * @var string
     */
    public const NAME = 'PairwiseID';


    /**
     * Initialize this filter.
     *
     * @param array &$config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct(array &$config, $reserved)
    {
        parent::__construct($config, $reserved);
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
        $hash = $this->calculateHash($userID . '|' . $sp_entityid);

        $value = strtolower($hash . '@' . $scope);
        $this->validateGeneratedIdentifier($value);

        $state['Attributes'][C::ATTR_PAIRWISE_ID] = [$value];
    }
}
