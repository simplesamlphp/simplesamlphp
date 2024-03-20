<?php

declare(strict_types=1);

namespace SimpleSAML;

use Exception;
use SimpleSAML\Utils\Auth;
use SimpleSAML\Utils\Config;
use SimpleSAML\Utils\Crypto;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\Utils\AuthSource;
use SimpleSAML\Utils\System;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Provides various SimpleSAMLphp utilities.
 */
class Utils
{
    // SSP utils
    protected ?Crypto $crypto = null;
    protected ?Config $config = null;
    protected ?HTTP $http = null;
    protected ?Auth $auth = null;
    protected ?AuthSource $authSource = null;
    protected ?System $system = null;
    protected ?Module $module = null;

    // Symfony utils
    protected ?Filesystem $symfonyFilesystem = null;
    protected ?Finder $symfonyFinder = null;

    public function __construct(
        protected ?Configuration $globalConfig = null,
        protected ?Session $session = null,
        protected ?Configuration $authSourcesConfig = null
    ) {
    }

    /**
     * @throws Exception
     */
    public function globalConfig(): Configuration
    {
        return $this->globalConfig ??= Configuration::getInstance();
    }

    /**
     * @throws Exception
     */
    public function session(): Session
    {
        return $this->session ??= Session::getSessionFromRequest();
    }

    /**
     * @throws Exception
     */
    public function authSourcesConfig(): Configuration
    {
        return $this->authSourcesConfig ??= Configuration::getConfig('authsources.php');
    }

    /**
     * @throws Exception
     */
    public function config(): Config
    {
        return $this->config ??= new Config($this);
    }

    /**
     * @throws Exception
     */
    public function crypto(): Crypto
    {
        return $this->crypto ??= new Crypto($this);
    }

    /**
     * @throws Exception
     */
    public function http(): HTTP
    {
        return $this->http ??= new HTTP($this);
    }

    public function auth(): Auth
    {
        return $this->auth ??= new Auth($this);
    }

    public function authSource(): AuthSource
    {
        return $this->authSource ??= new AuthSource($this);
    }

    public function system(): System
    {
        return $this->system ??= new System($this);
    }

    /**
     * Symfony utils
     */
    public function symfonyFilesystem(): Filesystem
    {
        return $this->symfonyFilesystem ??= new Filesystem();
    }

    public function symfonyFinder(): Finder
    {
        return $this->symfonyFinder ??= new Finder();
    }

    // TODO mivanci Add any other utility class to be used throughout the codebase
}
