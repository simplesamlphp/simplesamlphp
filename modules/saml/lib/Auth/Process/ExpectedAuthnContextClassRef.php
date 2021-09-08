<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Auth\Process;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Error;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Utils;

/**
 * Attribute filter to validate AuthnContextClassRef values.
 *
 * Example configuration:
 *
 * 91 => [
 *      'class' => 'saml:ExpectedAuthnContextClassRef',
 *      'accepted' => [
 *         'urn:oasis:names:tc:SAML:2.0:post:ac:classes:nist-800-63:3',
 *         'urn:oasis:names:tc:SAML:2.0:ac:classes:Password',
 *         ],
 *       ],
 *
 * @package SimpleSAMLphp
 */

class ExpectedAuthnContextClassRef extends ProcessingFilter
{
    /**
     * Array of accepted AuthnContextClassRef
     * @var array
     */
    private array $accepted;


    /**
     * AuthnContextClassRef of the assertion
     * @var string|null
     */
    private ?string $AuthnContextClassRef = null;


    /**
     * Initialize this filter, parse configuration
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     *
     * @throws \SimpleSAML\Error\Exception if the mandatory 'accepted' configuration option is missing.
     */
    public function __construct(array $config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (empty($config['accepted'])) {
            Logger::error(
                'ExpectedAuthnContextClassRef: Configuration error. There is no accepted AuthnContextClassRef.'
            );
            throw new Error\Exception(
                'ExpectedAuthnContextClassRef: Configuration error. There is no accepted AuthnContextClassRef.'
            );
        }
        $this->accepted = $config['accepted'];
    }


    /**
     * @param array &$state The current request
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');

        $this->AuthnContextClassRef = $state['saml:sp:State']['saml:sp:AuthnContext'];

        if (!in_array($this->AuthnContextClassRef, $this->accepted, true)) {
            $this->unauthorized($state);
        }
    }


    /**
     * When the process logic determines that the user is not
     * authorized for this service, then forward the user to
     * an 403 unauthorized page.
     *
     * Separated this code into its own method so that child
     * classes can override it and change the action. Forward
     * thinking in case a "chained" ACL is needed, more complex
     * permission logic.
     *
     * @param array $state
     */
    protected function unauthorized(array &$state): void
    {
        Logger::error(
            'ExpectedAuthnContextClassRef: Invalid authentication context: ' . strval($this->AuthnContextClassRef) .
            '. Accepted values are: ' . var_export($this->accepted, true)
        );

        $id = Auth\State::saveState($state, 'saml:ExpectedAuthnContextClassRef:unauthorized');
        $url = Module::getModuleURL(
            'saml/sp/wrong_authncontextclassref.php'
        );

        $httpUtils = new Utils\HTTP();
        $httpUtils->redirectTrustedURL($url, ['StateId' => $id]);
    }
}
