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
class PairwiseID extends Auth\ProcessingFilter
{
    /**
     * The attribute we should generate the pairwise id from.
     *
     * @var string
     */
    private string $identifyingAttribute;

    /**
     * The scope to use for this attribute.
     *
     * @var string
     */
    private string $scope;

    /**
     * @var \SimpleSAML\Utils\Config|string
     * @psalm-var \SimpleSAML\Utils\Config|class-string
     */
    protected $configUtils = Utils\Config::class;


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
        Assert::keyExists($config, 'scope', "Missing mandatory 'scope' config setting.");
        Assert::stringNotEmpty($config['identifyingAttribute']);
        Assert::regex(
            $config['scope'],
            '/^[a-zA-Z0-9.-]+$/i',
            'PairwiseID: \'scope\' contains illegal characters.'
        );

        $this->identifyingAttribute = $config['identifyingAttribute'];
        $this->scope = $config['scope'];
    }


    /**
     * Apply filter to add the Pairwise ID.
     *
     * @param array &$state  The current state.
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');
        Assert::keyExists(
            $state['Attributes'],
            $this->identifyingAttribute,
            sprintf(
                "core:PairwiseID: Missing attribute '%s', which is needed to generate the Pairwise ID.",
                $this->identifyingAttribute
            )
        );

        $userID = $state['Attributes'][$this->identifyingAttribute][0];
        Assert::regex(
            $userID,
            '/^[a-zA-Z0-9=-]+$/i',
            'PairwiseID: \'identifyingAttribute\' contains illegal characters.'
        );

        $idp_entityid = $state['IdPMetadata']['entityid'];

        if (!empty($state['saml:RequesterID'])) {
            // Proxied request - use actual SP entity ID
            $sp_entityid = $state['saml:RequesterID'][0];
        } else {
            $sp_entityid = $state['core:SP'];
        }

        // Calculate hash
        $salt = $this->configUtils::getSecretSalt();
        $hash = hash('sha256', $salt . '|' . $userID . '|' . $idp_entityid . '|' . $sp_entityid, false);

        $value = strtolower($hash . '@' . $this->scope);
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
