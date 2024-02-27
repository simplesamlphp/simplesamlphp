<?php

declare(strict_types=1);

namespace SimpleSAML\Module\exampleauth\Auth\Source;

use Exception;
use SimpleSAML\{Auth, Utils};
use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Example authentication source.
 *
 * This class is an example authentication source which will always return a user with
 * a static set of attributes.
 *
 * @package SimpleSAMLphp
 */
class StaticSource extends Auth\Source
{
    /**
     * The attributes we return.
     * @var array
     */
    private array $attributes;


    /**
     * Constructor for this authentication source.
     *
     * @param array $info  Information about this authentication source.
     * @param array $config  Configuration.
     */
    public function __construct(array $info, array $config)
    {
        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        $attrUtils = new Utils\Attributes();

        // Parse attributes
        try {
            $this->attributes = $attrUtils->normalizeAttributesArray($config);
        } catch (Exception $e) {
            throw new Exception('Invalid attributes for authentication source ' .
                $this->authId . ': ' . $e->getMessage());
        }
    }


    /**
     * Log in using static attributes.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request  The current request
     * @param array &$state  Information about the current authentication.
     */
    public function authenticate(Request $request, array &$state): ?Response
    {
        $state['Attributes'] = $this->attributes;
        return null;
    }
}
