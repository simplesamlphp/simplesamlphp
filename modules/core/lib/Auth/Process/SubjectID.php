<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use Exception;
use SAML2\Constants;
use SAML2\XML\saml\NameID;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;

/**
 * Filter to generate the subject ID attribute.
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
 *       'core:SubjectID',
 *       'identifyingAttribute' => 'uid',
 *       'scope' => 'example.org',
 *   ]
 * ]
 * </code>
 *
 * @package SimpleSAMLphp
 */
class SubjectID extends Auth\ProcessingFilter
{
    /**
     * The regular expression to match the scope
     *
     * @var string
     */
    public const SCOPE_REGEX = '/^[a-zA-Z0-9]{1}[a-zA-Z0-9.-]{0,126}$/i';

    /** @var string */
    public const WARN_PATTERN = '/^[a-zA-Z0-9]{1}[a-zA-Z0-9=-]{3,126}@[a-zA-Z0-9]{1}[a-zA-Z0-9.-]{3,126}$/i';

    /**
     * The attribute we should generate the subject id from.
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
            self::SCOPE_REGEX,
            'SubjectID: \'scope\' contains illegal characters.'
        );

        $this->identifyingAttribute = $config['identifyingAttribute'];
        $this->scope = $config['scope'];
    }


    /**
     * Apply filter to add the subject ID.
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
                "core:SubjectID: Missing attribute '%s', which is needed to generate the subject ID.",
                $this->identifyingAttribute
            )
        );

        $userID = $state['Attributes'][$this->identifyingAttribute][0];
        Assert::notEmpty($userID, 'SubjectID: \'identifyingAttribute\' cannot be an empty string.');

        $value = strtolower($userID . '@' . $this->scope);

        if (preg_match($pattern, $value) === 0) {
            Logger::warning("Generated SubjectID '$value' can hardly be considered globally unique.");
        }

        $state['Attributes'][Constants::ATTR_SUBJECT_ID] = [$value];
    }
}
