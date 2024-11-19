<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Process;

use SimpleSAML\{Auth, Logger, Utils};
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\Exception\ProtocolViolationException;

use function array_key_exists;
use function explode;
use function hash_hmac;
use function preg_match;
use function strpos;
use function strtolower;
use function sprintf;

/**
 * Filter to generate the subject ID attribute.
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
 *       'saml:SubjectID',
 *       'identifyingAttribute' => 'uid',
 *       'scopeAttribute' => 'scope',
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
     *
     * @var string
     */
    public const NAME = 'SubjectID';

    /**
     * The regular expression to match the scope
     *
     * @var string
     */
    public const SCOPE_PATTERN = '/^[a-z0-9][a-z0-9.-]{0,126}$/Di';

    /**
     * The regular expression to match the specifications
     *
     * @var string
     */
    public const SPEC_PATTERN = '/^[a-z0-9][a-z0-9=-]{0,126}@[a-z0-9][a-z0-9.-]{0,126}$/Di';

    /**
     * The regular expression to match worrisome identifiers that need to raise a warning
     *
     * @var string
     */
    public const WARN_PATTERN = '/^[a-z0-9][a-z0-9=-]{3,}@[a-z0-9][a-z0-9.-]+\.[a-z]{2,}$/Di';

    /**
     * The attribute we should generate the subject id from.
     *
     * @var string
     */
    protected string $identifyingAttribute;

    /**
     * The attribute we should use for the scope of the subject id.
     *
     * @var string
     */
    protected string $scopeAttribute;

    /**
     * Whether the unique part of the subject id must be hashed
     *
     * @var bool
     */
    private bool $hashed = false;

    /**
     * @var \SimpleSAML\Utils\Config
     */
    protected Utils\Config $configUtils;

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
        Assert::keyExists($config, 'scopeAttribute', "Missing mandatory 'scopeAttribute' config setting.");
        Assert::stringNotEmpty($config['identifyingAttribute']);
        Assert::stringNotEmpty($config['scopeAttribute']);

        $this->identifyingAttribute = $config['identifyingAttribute'];
        $this->scopeAttribute = $config['scopeAttribute'];

        if (array_key_exists('hashed', $config)) {
            Assert::boolean($config['hashed']);
            $this->hashed = $config['hashed'];
        }

        $this->configUtils = new Utils\Config();
    }


    /**
     * Apply filter to add the subject ID.
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

        if ($this->hashed === true) {
            $value = strtolower($this->calculateHash($userID) . '@' . $scope);
        } else {
            $value = strtolower($userID . '@' . $scope);
        }

        $this->validateGeneratedIdentifier($value);

        $state['Attributes'][C::ATTR_SUBJECT_ID] = [$value];
    }


    /**
     * Retrieve the identifying attribute from the state and test it for erroneous conditions
     *
     * @param array $state
     * @return string|null
     * @throws \SimpleSAML\Assert\AssertionFailedException if the pre-conditions are not met
     */
    protected function getIdentifyingAttribute(array $state): ?string
    {
        if (
            !array_key_exists('Attributes', $state)
            || !array_key_exists($this->identifyingAttribute, $state['Attributes'])
        ) {
            $this->logger::warning(sprintf(
                "saml:" . static::NAME . ": Missing attribute '%s', which is needed to generate the ID.",
                $this->identifyingAttribute,
            ));

            return null;
        }

        $userID = $state['Attributes'][$this->identifyingAttribute][0];
        Assert::stringNotEmpty(
            $userID,
            'saml:' . static::NAME . ': \'identifyingAttribute\' cannot be an empty string.',
        );

        return $userID;
    }


    /**
     * Retrieve the scope attribute from the state and test it for erroneous conditions
     *
     * @param array $state
     * @return string|null
     * @throws \SimpleSAML\Assert\AssertionFailedException if the scope is an empty string
     * @throws \SimpleSAML\SAML2\Exception\ProtocolViolationException if the pre-conditions are not met
     */
    protected function getScopeAttribute(array $state): ?string
    {
        if (!array_key_exists('Attributes', $state) || !array_key_exists($this->scopeAttribute, $state['Attributes'])) {
            $this->logger::warning(sprintf(
                "saml:" . static::NAME . ": Missing attribute '%s', which is needed to generate the ID.",
                $this->scopeAttribute,
            ));

            return null;
        }

        $scope = $state['Attributes'][$this->scopeAttribute][0];
        Assert::stringNotEmpty($scope, 'saml:' . static::NAME . ': \'scopeAttribute\' cannot be an empty string.');

        // If the value is scoped, extract the scope from it
        if (strpos($scope, '@') !== false) {
            $scope = explode('@', $scope, 2);
            $scope = $scope[1];
        }

        Assert::regex(
            $scope,
            self::SCOPE_PATTERN,
            'saml:' . static::NAME . ': \'scopeAttribute\' contains illegal characters.',
            ProtocolViolationException::class,
        );
        return $scope;
    }


    /**
     * Test the generated identifier to ensure it's compliant with the specifications.
     * Log a warning when the generated value is considered to be weak
     *
     * @param string $value
     * @return void
     * @throws \SimpleSAML\SAML2\Exception\ProtocolViolationException if the post-conditions are not met
     */
    protected function validateGeneratedIdentifier(string $value): void
    {
        Assert::regex(
            $value,
            self::SPEC_PATTERN,
            'saml:' . static::NAME . ': Generated ID \'' . $value . '\' contains illegal characters.',
            ProtocolViolationException::class,
        );

        if (preg_match(self::WARN_PATTERN, $value) === 0) {
            $this->logger::warning(
                'saml:' . static::NAME . ': Generated ID \'' . $value . '\' can hardly be considered globally unique.',
            );
        }
    }


    /**
     * Calculate the hash for the unique part of the identifier.
     */
    protected function calculateHash(string $input): string
    {
        $salt = $this->configUtils->getSecretSalt();
        return hash_hmac('sha256', $input, $salt, false);
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
