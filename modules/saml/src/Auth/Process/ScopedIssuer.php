<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Process;

use SAML2\Exception\ProtocolViolationException;
use SimpleSAML\{Auth, Utils};
use SimpleSAML\Assert\Assert;

use function array_key_exists;
use function explode;
use function strpos;
use function sprintf;

/**
 * Filter to generate a saml:issuer dynamically based on an input attribute.
 *
 * See: https://learn.microsoft.com/en-us/entra/identity/hybrid/connect/how-to-connect-install-multiple-domains#multiple-top-level-domain-support
 *
 * By default, this filter will generate the saml:Issuer based on the userPrincipalName of the current user.
 * This is generated from the attribute configured in 'scopedAttribute' in the
 * authproc-configuration.
 *
 * NOTE: since the userPrincipalName is specified as single-value attribute, only the first value
 * of `scopedAttribute` is considered.
 *
 * Example - generate from attribute:
 * <code>
 * 'authproc' => [
 *   50 => [
 *       'saml:ScopedIssuer',
 *       'pattern' => 'https://%1$s/issuer',
 *       'scopedAttribute' => 'userPrincipalName',
 *   ]
 * ]
 * </code>
 *
 * @package SimpleSAMLphp
 */
class ScopedIssuer extends Auth\ProcessingFilter
{
    /**
     * The regular expression to match the scope
     *
     * @var string
     */
    public const SCOPE_PATTERN = '/^[a-z0-9][a-z0-9.-]{0,126}$/Di';

    /**
     * The attribute we should use for the scope of the saml:Issuer.
     *
     * @var string
     */
    protected string $scopedAttribute;

    /**
     * The pattern to use for the new saml:Issuer.
     *
     * @var string
     */
    protected string $pattern;


    /**
     * Initialize this filter.
     *
     * @param array &$config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct(array &$config, $reserved)
    {
        parent::__construct($config, $reserved);

        Assert::keyExists($config, 'scopedAttribute', "Missing mandatory 'scopedAttribute' config setting.");
        Assert::stringNotEmpty($config['scopedAttribute']);

        Assert::keyExists($config, 'pattern', "Missing mandatory 'pattern' config setting.");
        Assert::stringNotEmpty($config['pattern']);

        $this->scopedAttribute = $config['scopedAttribute'];
        $this->pattern = $config['pattern'];
    }


    /**
     * Apply filter to dynamically set the saml:Issuer.
     *
     * @param array &$state  The current state.
     */
    public function process(array &$state): void
    {
        $scope = $this->getScopedAttribute($state);
        if ($scope === null) {
            // Attribute missing, precondition not met
            return;
        }

        $value = sprintf($this->pattern, $scope);

        // @todo: Replace the three asserts underneath with Assert::validEntityID in saml2v5
        Assert::validURI(
            $value,
            sprintf("saml:ScopedIssuer: Generated saml:Issuer '%s' contains illegal characters.", $value),
            ProtocolViolationException::class,
        );
        Assert::notWhitespaceOnly(
            $value,
            '%s is not a SAML2-compliant URI',
            ProtocolViolationException::class,
        );
        // If it doesn't have a scheme, it's not an absolute URI
        Assert::regex(
            $value,
            '/^([a-z][a-z0-9\+\-\.]+[:])/i',
            '%s is not a SAML2-compliant URI',
            ProtocolViolationException::class,
        );

        $state['IdPMetadata']['entityid'] = $value;
    }


    /**
     * Retrieve the scope attribute from the state and test it for erroneous conditions
     *
     * @param array $state
     * @return string|null
     * @throws \SimpleSAML\Assert\AssertionFailedException if the scope is an empty string
     * @throws \SAML2\Exception\ProtocolViolationException if the pre-conditions are not met
     */
    protected function getScopedAttribute(array $state): ?string
    {
        if (!array_key_exists('Attributes', $state) || !array_key_exists($this->scopedAttribute, $state['Attributes'])) {
            return null;
        }

        $scope = $state['Attributes'][$this->scopedAttribute][0];
        Assert::stringNotEmpty($scope, 'saml:ScopedIssuer: \'scopedAttribute\' cannot be an empty string.');

        // If the value is scoped, extract the scope from it
        if (strpos($scope, '@') !== false) {
            $scope = explode('@', $scope, 2);
            $scope = $scope[1];
        }

        Assert::regex(
            $scope,
            self::SCOPE_PATTERN,
            'saml:ScopedIssuer: \'scopedAttribute\' contains illegal characters.',
        );

        return $scope;
    }
}
