<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

/**
 * Provides a non-static wrapper for the HTTP utility class.
 *
 * @package SimpleSAML\Utils
 */
class HttpAdapter
{
    /**
     * @see HTTP::getServerHTTPS()
     * @return bool
     */
    public function getServerHTTPS(): bool
    {
        return HTTP::getServerHTTPS();
    }


    /**
     * @see HTTP::getServerPort()
     * @return string
     */
    public function getServerPort(): string
    {
        return HTTP::getServerPort();
    }


    /**
     * @see HTTP::addURLParameters()
     *
     * @param string $url
     * @param array $parameters
     * @return string
     */
    public function addURLParameters(string $url, array $parameters): string
    {
        return HTTP::addURLParameters($url, $parameters);
    }


    /**
     * @see HTTP::checkSessionCookie()
     *
     * @param string|null $retryURL
     * @return void
     */
    public function checkSessionCookie(string $retryURL = null): void
    {
        HTTP::checkSessionCookie($retryURL);
    }


    /**
     * @see HTTP::checkURLAllowed()
     *
     * @param string $url
     * @param array|null $trustedSites
     * @return string
     */
    public function checkURLAllowed(string $url, array $trustedSites = null): string
    {
        return HTTP::checkURLAllowed($url, $trustedSites);
    }


    /**
     * @see HTTP::fetch()
     *
     * @param string $url
     * @param array $context
     * @param bool $getHeaders
     * @return array|string
     */
    public function fetch(string $url, array $context = [], bool $getHeaders = false)
    {
        return HTTP::fetch($url, $context, $getHeaders);
    }


    /**
     * @see HTTP::getAcceptLanguage()
     * @return array
     */
    public function getAcceptLanguage(): array
    {
        return HTTP::getAcceptLanguage();
    }


    /**
     * @see HTTP::guessBasePath()
     * @return string
     */
    public function guessBasePath(): string
    {
        return HTTP::guessBasePath();
    }


    /**
     * @see HTTP::getBaseURL()
     * @return string
     */
    public function getBaseURL(): string
    {
        return HTTP::getBaseURL();
    }


    /**
     * @see HTTP::getFirstPathElement()
     *
     * @param bool $trailingslash
     * @return string
     */
    public function getFirstPathElement(bool $trailingslash = true): string
    {
        return HTTP::getFirstPathElement($trailingslash);
    }


    /**
     * @see HTTP::getPOSTRedirectURL()
     *
     * @param string $destination
     * @param array $data
     * @return string
     */
    public function getPOSTRedirectURL(string $destination, array $data): string
    {
        return HTTP::getPOSTRedirectURL($destination, $data);
    }


    /**
     * @see HTTP::getSelfHost()
     * @return string
     */
    public function getSelfHost(): string
    {
        return HTTP::getSelfHost();
    }


    /**
     * @see HTTP::getSelfHostWithNonStandardPort()
     * @return string
     */
    public function getSelfHostWithNonStandardPort(): string
    {
        return HTTP::getSelfHostWithNonStandardPort();
    }


    /**
     * @see HTTP::getSelfHostWithPath()
     * @return string
     */
    public function getSelfHostWithPath(): string
    {
        return HTTP::getSelfHostWithPath();
    }


    /**
     * @see HTTP::getSelfURL()
     * @return string
     */
    public function getSelfURL(): string
    {
        return HTTP::getSelfURL();
    }


    /**
     * @see HTTP::getSelfURLHost()
     * @return string
     */
    public function getSelfURLHost(): string
    {
        return HTTP::getSelfURLHost();
    }


    /**
     * @see HTTP::getSelfURLNoQuery()
     * @return string
     */
    public function getSelfURLNoQuery(): string
    {
        return HTTP::getSelfURLNoQuery();
    }


    /**
     * @see HTTP::isHTTPS()
     * @return bool
     */
    public function isHTTPS(): bool
    {
        return HTTP::isHTTPS();
    }


    /**
     * @see HTTP::normalizeURL()
     * @param string $url
     * @return string
     */
    public function normalizeURL(string $url): string
    {
        return HTTP::normalizeURL($url);
    }


    /**
     * @see HTTP::parseQueryString()
     *
     * @param string $query_string
     * @return array
     */
    public function parseQueryString(string $query_string): array
    {
        return HTTP::parseQueryString($query_string);
    }


    /**
     * @see HTTP::redirectTrustedURL()
     *
     * @param string $url
     * @param array $parameters
     * @return void
     */
    public function redirectTrustedURL(string $url, array $parameters = []): void
    {
        HTTP::redirectTrustedURL($url, $parameters);
    }


    /**
     * @see HTTP::redirectUntrustedURL()
     *
     * @param string $url
     * @param array $parameters
     * @return void
     */
    public function redirectUntrustedURL(string $url, array $parameters = []): void
    {
        HTTP::redirectUntrustedURL($url, $parameters);
    }


    /**
     * @see HTTP::resolveURL()
     *
     * @param string $url
     * @param string|null $base
     * @return string
     */
    public function resolveURL(string $url, string $base = null): string
    {
        return HTTP::resolveURL($url, $base);
    }


    /**
     * @see HTTP::setCookie()
     *
     * @param string $name
     * @param string $value
     * @param array|null $params
     * @param bool $throw
     * @return void
     */
    public function setCookie(string $name, string $value, array $params = null, bool $throw = true): void
    {
        HTTP::setCookie($name, $value, $params, $throw);
    }


    /**
     * @see HTTP::submitPOSTData()
     *
     * @param string $destination
     * @param array $data
     * @return void
     */
    public function submitPOSTData(string $destination, array $data): void
    {
        HTTP::submitPOSTData($destination, $data);
    }
}
