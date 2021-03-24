<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use Exception;
use SAML2\Constants;
use SAML2\XML\saml\NameID;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Logger;

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
     * The name for this class
     */
    public const NAME = 'SubjectID';

    /**
     * The regular expression to match the scope
     *
     * @var string
     */
    public const SCOPE_PATTERN = '/^[a-z0-9][a-z0-9.-]{0,126}$/i';

    /**
     * The regular expression to match the specifications
     *
     * @var string
     */
    public const SPEC_PATTERN = '/^[a-z0-9][a-z0-9=-]{0,126}@[a-z0-9][a-z0-9.-]{0,126}$/i';

    /**
     * The regular expression to match worrisome identifiers that need to raise a warning
     *
     * @var string
     */
    public const WARN_PATTERN = '/^[a-z0-9][a-z0-9=-]{3,126}@[a-z0-9][a-z0-9.-]{3,126}$/i';

    /**
     * The attribute we should generate the subject id from.
     *
     * @var string
     */
    protected $identifyingAttribute;

    /**
     * The scope to use for this attribute.
     *
     * @var string
     */
    protected $scope;

    /**
     * @var \SimpleSAML\Logger|string
     * @psalm-var \SimpleSAML\Logger|class-string
     */
    protected $logger = Logger::class;


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
            self::SCOPE_PATTERN,
            'core:' . static::NAME . ': \'scope\' contains illegal characters.'
        );

        $this->identifyingAttribute = $config['identifyingAttribute'];
        $this->scope = $config['scope'];
    }


    /**
     * Apply filter to add the subject ID.
     *
     * @param array &$state  The current state.
     */
    public function process(&$state): void
    {
        $userID = $this->getIdentifyingAttribute($state);

        $value = strtolower($userID . '@' . $this->scope);
        $this->validateGeneratedIdentifier($value);

        $state['Attributes'][Constants::ATTR_SUBJECT_ID] = [$value];
    }


    /**
     * Retrieve the identifying attribute from the state and test it for erroneous conditions
     *
     * @param array $state
     * @return string
     * @throws \SimpleSAML\Assert\AssertionFailedException if the pre-conditions are not met
     */
    protected function getIdentifyingAttribute(array $state): string
    {
        Assert::keyExists($state, 'Attributes');
        Assert::keyExists(
            $state['Attributes'],
            $this->identifyingAttribute,
            sprintf(
                "core:" . static::NAME . ": Missing attribute '%s', which is needed to generate the ID.",
                $this->identifyingAttribute
            )
        );

        $userID = $state['Attributes'][$this->identifyingAttribute][0];
        Assert::stringNotEmpty($userID, 'core' . static::NAME . ': \'identifyingAttribute\' cannot be an empty string.');

        return $userID;
    }


    /**
     * Test the generated identifier to ensure compliancy with the specifications.
     * Log a warning when the generated value is considered to be weak
     *
     * @param string $value
     * @return void
     * @throws \SimpleSAML\Assert\AssertionFailedException if the post-conditions are not met
     */
    protected function validateGeneratedIdentifier(string $value): void
    {
        Assert::regex(
            $value,
            self::SPEC_PATTERN,
            'core:' . static::NAME . ': Generated ID \'' . $value . '\' contains illegal characters.'
        );

        if (preg_match(self::WARN_PATTERN, $value) === 0) {
            $this->logger::warning('core:' . static::NAME . ': Generated ID \'' . $value . '\' can hardly be considered globally unique.');
        }
    }


    /**
     * Inject the \SimpleSAML\Logger dependency.
     *
     * @param \SimpleSAML\Logger $logger
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }
}
