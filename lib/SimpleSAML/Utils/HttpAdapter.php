<?php

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
     */
    public function getServerHTTPS()
    {
        return HTTP::getServerHTTPS();
    }

    /**
     * @see HTTP::getServerPort()
     */
    public function getServerPort()
    {
        return HTTP::getServerPort();
    }

    /**
     * @see HTTP::addURLParameters()
     */
    public function addURLParameters($url, $parameters)
    {
        return HTTP::addURLParameters($url, $parameters);
    }

    /**
     * @see HTTP::checkSessionCookie()
     */
    public function checkSessionCookie($retryURL = null)
    {
        HTTP::checkSessionCookie($retryURL);
    }

    /**
     * @see HTTP::checkURLAllowed()
     */
    public function checkURLAllowed($url, array $trustedSites = null)
    {
        return HTTP::checkURLAllowed($url, $trustedSites);
    }

    /**
     * @see HTTP::fetch()
     */
    public function fetch($url, $context = [], $getHeaders = false)
    {
        return HTTP::fetch($url, $context, $getHeaders);
    }

    /**
     * @see HTTP::getAcceptLanguage()
     */
    public function getAcceptLanguage()
    {
        return HTTP::getAcceptLanguage();
    }

    /**
     * @see HTTP::guessBasePath()
     */
    public function guessBasePath()
    {
        return HTTP::guessBasePath();
    }

    /**
     * @see HTTP::getBaseURL()
     */
    public function getBaseURL()
    {
        return HTTP::getBaseURL();
    }

    /**
     * @see HTTP::getFirstPathElement()
     */
    public function getFirstPathElement($trailingslash = true)
    {
        return HTTP::getFirstPathElement($trailingslash);
    }

    /**
     * @see HTTP::getPOSTRedirectURL()
     */
    public function getPOSTRedirectURL($destination, $data)
    {
        return HTTP::getPOSTRedirectURL($destination, $data);
    }

    /**
     * @see HTTP::getSelfHost()
     */
    public function getSelfHost()
    {
        return HTTP::getSelfHost();
    }

    /**
     * @see HTTP::getSelfHostWithNonStandardPort()
     */
    public function getSelfHostWithNonStandardPort()
    {
        return HTTP::getSelfHostWithNonStandardPort();
    }

    /**
     * @see HTTP::getSelfHostWithPath()
     */
    public function getSelfHostWithPath()
    {
        return HTTP::getSelfHostWithPath();
    }

    /**
     * @see HTTP::getSelfURL()
     */
    public function getSelfURL()
    {
        return HTTP::getSelfURL();
    }

    /**
     * @see HTTP::getSelfURLHost()
     */
    public function getSelfURLHost()
    {
        return HTTP::getSelfURLHost();
    }

    /**
     * @see HTTP::getSelfURLNoQuery()
     */
    public function getSelfURLNoQuery()
    {
        return HTTP::getSelfURLNoQuery();
    }

    /**
     * @see HTTP::isHTTPS()
     */
    public function isHTTPS()
    {
        return HTTP::isHTTPS();
    }

    /**
     * @see HTTP::normalizeURL()
     */
    public function normalizeURL($url)
    {
        return HTTP::normalizeURL($url);
    }

    /**
     * @see HTTP::parseQueryString()
     */
    public function parseQueryString($query_string)
    {
        return HTTP::parseQueryString($query_string);
    }

    /**
     * @see HTTP::redirectTrustedURL()
     */
    public function redirectTrustedURL($url, $parameters = [])
    {
        HTTP::redirectTrustedURL($url, $parameters);
    }

    /**
     * @see HTTP::redirectUntrustedURL()
     */
    public function redirectUntrustedURL($url, $parameters = [])
    {
        HTTP::redirectUntrustedURL($url, $parameters);
    }

    /**
     * @see HTTP::resolveURL()
     */
    public function resolveURL($url, $base = null)
    {
        return HTTP::resolveURL($url, $base);
    }

    /**
     * @see HTTP::setCookie()
     */
    public function setCookie($name, $value, $params = null, $throw = true)
    {
        HTTP::setCookie($name, $value, $params, $throw);
    }

    /**
     * @see HTTP::submitPOSTData()
     */
    public function submitPOSTData($destination, $data)
    {
        HTTP::submitPOSTData($destination, $data);
    }
}
