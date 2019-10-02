<?php

namespace SimpleSAML\Module;

use SimpleSAML\Auth\AuthenticationFactory;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Module;
use SimpleSAML\Session;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as SymfonyControllerResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactory;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * A class to resolve module controllers based on a given request.
 *
 * This class allows us to find a controller (a callable) that's configured for a given URL.
 *
 * @package SimpleSAML
 */
class ControllerResolver extends SymfonyControllerResolver implements ArgumentResolverInterface
{
    /** @var ArgumentMetadataFactory */
    protected $argFactory;

    /** @var ContainerBuilder */
    protected $container;

    /** @var string */
    protected $module;

    /** @var array */
    protected $params = [];

    /** @var RouteCollection|null */
    protected $routes;


    /**
     * Build a module controller resolver.
     *
     * @param string $module The name of the module.
     */
    public function __construct($module)
    {
        parent::__construct();
        $this->module = $module;

        $loader = new YamlFileLoader(
            new FileLocator(Module::getModuleDir($this->module))
        );

        $this->argFactory = new ArgumentMetadataFactory();
        $this->container = new ContainerBuilder();
        $this->container->autowire(AuthenticationFactory::class, AuthenticationFactory::class);

        try {
            $this->routes = $loader->load('routes.yaml');
            $redirect = new Route(
                '/{url}',
                ['_controller' => '\SimpleSAML\Module::removeTrailingSlash'],
                ['url' => '.*/$']
            );
            $this->routes->add('trailing-slash', $redirect);
            $this->routes->addPrefix('/' . $this->module);
        } catch (FileLocatorFileNotFoundException $e) {
        }
    }


    /**
     * Get the controller associated with a given URL, based on a request.
     *
     * This method searches for a 'routes.yaml' file in the root of the module, defining valid routes for the module
     * and mapping them given controllers. It's input is a Request object with the request that we want to serve.
     *
     * @param Request $request The request we need to find a controller for.
     *
     * @return callable|false A controller (as a callable) that can handle the request, or false if we cannot find
     * one suitable for the given request.
     */
    public function getController(Request $request)
    {
        if ($this->routes === null) {
            return false;
        }
        $ctxt = new RequestContext();
        $ctxt->fromRequest($request);

        try {
            $matcher = new UrlMatcher($this->routes, $ctxt);
            $this->params = $matcher->match($ctxt->getPathInfo());
            list($class, $method) = explode('::', $this->params['_controller']);
            $this->container->register($class, $class)->setAutowired(true)->setPublic(true);
            $this->container->compile();
            return [$this->container->get($class), $method];
        } catch (ResourceNotFoundException $e) {
            // no route defined matching this request
        }
        return false;
    }


    /**
     * Get the arguments that should be passed to a controller from a given request.
     *
     * When the signature of the controller includes arguments with type Request, the given request will be passed to
     * those. Otherwise, they'll be matched by name. If no value is available for a given argument, the method will
     * try to set a default value or null, if possible.
     *
     * @param Request $request The request that holds all the information needed by the controller.
     * @param callable $controller A controller for the given request.
     *
     * @return array An array of arguments that should be passed to the controller, in order.
     *
     * @throws \SimpleSAML\Error\Exception If we don't find anything suitable for an argument in the controller's
     * signature.
     */
    public function getArguments(Request $request, $controller)
    {
        $args = [];
        $metadata = $this->argFactory->createArgumentMetadata($controller);

        /** @var ArgumentMetadata $argMeta */
        foreach ($metadata as $argMeta) {
            if ($argMeta->getType() === Request::class) {
                // add request argument
                $args[] = $request;
                continue;
            }

            $argName = $argMeta->getName();
            if (array_key_exists($argName, $this->params)) {
                // add argument by name
                $args[] = $this->params[$argName];
                continue;
            }

            // URL does not contain value for this argument
            if ($argMeta->hasDefaultValue()) {
                // it has a default value
                $args[] = $argMeta->getDefaultValue();
            }

            // no default value
            if ($argMeta->isNullable()) {
                $args[] = null;
            }

            throw new Exception('Missing value for argument ' . $argName . '. This is probably a bug.');
        }

        return $args;
    }


    /**
     * Set the configuration to use by the controllers.
     *
     * @param \SimpleSAML\Configuration $config
     * @return void
     */
    public function setConfiguration(Configuration $config)
    {
        $this->container->set(Configuration::class, $config);
        $this->container->register(Configuration::class)->setSynthetic(true)->setAutowired(true);
    }


    /**
     * Set the session to use by the controllers.
     *
     * @param \SimpleSAML\Session $session
     * @return void
     */
    public function setSession(Session $session)
    {
        $this->container->set(Session::class, $session);
        $this->container->register(Session::class)
            ->setSynthetic(true)
            ->setAutowired(true)
            ->addMethodCall('setConfiguration', [new Reference(Configuration::class)]);
    }
}
