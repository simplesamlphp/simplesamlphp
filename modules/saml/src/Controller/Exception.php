<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Controller;

use SimpleSAML\{Configuration, Logger};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

use function implode;
use function sprintf;

/**
 * Controller class for handling 'method not allowed' on SAML 2.0 endpoints.
 *
 * @package simplesamlphp/simplesamlphp
 */
class Exception
{
    /**
     * Controller constructor.
     *
     * It initializes the global configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     */
    public function __construct(
        protected Configuration $config,
    ) {
    }


    /**
     * @param string[] $allowedMethods
     * @return \SimpleSAML\HTTP\RunnableResponse
     */
    public function methodNotAllowed(array $allowedMethods): never
    {
        Logger::debug('Handling a HEAD request by returning method not allowed...');

        $request = Request::createFromGlobals();

        // These are the allowed methods from routes.yml
        $message = sprintf(
            'No route found for "%s %s": Method Not Allowed (Allow: %s)',
            $request->getMethod(),
            $request->getUriForPath($request->getPathInfo()),
            implode(', ', $allowedMethods),
        );

        throw new MethodNotAllowedHttpException($allowedMethods, $message);
    }
}
