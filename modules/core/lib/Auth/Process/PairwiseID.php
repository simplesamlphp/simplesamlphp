<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use Exception;
use SAML2\Constants;
use SAML2\XML\saml\NameID;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Utils;

/**
 * Filter to generate the Pairwise ID attribute.
 *
 * See: http://docs.oasis-open.org/security/saml-subject-id-attr/v1.0/csprd01/saml-subject-id-attr-v1.0-csprd01.html
 *
 * By default, this filter will generate the ID based on the UserID of the current user.
 * This is generated from the attribute configured in 'identifyingAttribute' in the
 * authproc-configuration.
 *
 * Example - generate from attribute:
 * <code>
 * 'authproc' => [
 *   50 => [
 *       'core:PairwiseID',
 *       'identifyingAttribute' => 'uid',
 *       'scope' => 'example.org',
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
     * @psalm-var \SimpleSAML\Utils\Config|class-string
     * @var \SimpleSAML\Utils\Config
     */
    protected $configUtils = Utils\Config::class;


    /**
     * Apply filter to add the Pairwise ID.
     *
     * @param array &$state  The current state.
     */
    public function process(&$state): void
    {
        $userID = $this->getIdentifyingAttribute($state);

        if (!empty($state['saml:RequesterID'])) {
            // Proxied request - use actual SP entity ID
            $sp_entityid = $state['saml:RequesterID'][0];
        } else {
            $sp_entityid = $state['core:SP'];
        }

        // Calculate hash
        $salt = $this->configUtils::getSecretSalt();
        $hash = hash('sha256', $salt . '|' . $userID . '|' . $sp_entityid, false);

        $value = strtolower($hash . '@' . $this->scope);
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
