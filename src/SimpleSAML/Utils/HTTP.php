<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;

/**
 * HTTP-related utility methods.
 *
 * @package SimpleSAMLphp
 */
class HTTP
{
    /**
     * Determine if the user agent can support cookies being sent with SameSite equal to "None".
     * Browsers without support may drop the cookie and or treat it as stricter setting
     * Browsers with support may have additional requirements on setting it on non-secure websites.
     *
     * Based on the Azure teams experience rolling out support and Chromium's advice
     * https://devblogs.microsoft.com/aspnet/upcoming-samesite-cookie-changes-in-asp-net-and-asp-net-core/
     * https://www.chromium.org/updates/same-site/incompatible-clients
     * @return bool true if user agent supports a None value for SameSite.
     */
    public function canSetSameSiteNone(): bool
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        if (!$useragent) {
            return true;
        }
        // All iOS 12 based browsers have no support
        if (strpos($useragent, "CPU iPhone OS 12") !== false || strpos($useragent, "iPad; CPU OS 12") !== false) {
            return false;
        }

        // Safari Mac OS X 10.14 has no support
        // - Safari on Mac OS X.
        if (strpos($useragent, "Macintosh; Intel Mac OS X 10_14") !== false) {
            // regular safari
            if (strpos($useragent, "Version/") !== false && strpos($useragent, "Safari") !== false) {
                return false;
            } elseif (preg_match('|AppleWebKit/[\.\d]+ \(KHTML, like Gecko\)$|', $useragent)) {
                return false;
            }
        }

        // Chrome based UCBrowser may have support (>= 12.13.2) even though its chrome version is old
        $matches = [];
        if (preg_match('|UCBrowser/(\d+\.\d+\.\d+)[\.\d]*|', $useragent, $matches)) {
            return version_compare($matches[1], '12.13.2', '>=');
        }

        // Chrome 50-69 may have broken SameSite=None and don't require it to be set
        if (strpos($useragent, "Chrome/5") !== false || strpos($useragent, "Chrome/6") !== false) {
            return false;
        }
        return true;
    }

    /**
     * Obtain a URL where we can redirect to securely post a form with the given data to a specific destination.
     *
     * @param string $destination The destination URL.
     * @param array  $data An associative array containing the data to be posted to $destination.
     *
     * @throws Error\Exception If the current session is transient.
     * @return string  A URL which allows to securely post a form to $destination.
     *
     */
    private function getSecurePOSTRedirectURL(string $destination, array $data): string
    {
        $session = Session::getSessionFromRequest();
        $id = $this->savePOSTData($session, $destination, $data);

        if ($session->isTransient()) {
            // this is a transient session, it is pointless to continue
            throw new Error\Exception('Cannot save POST data to a transient session.');
        }

        /** @var string $session_id */
        $session_id = $session->getSessionId();

        // encrypt the session ID and the random ID
        $cryptoUtils = new Crypto();
        $info = base64_encode($cryptoUtils->aesEncrypt($session_id . ':' . $id));

        $url = Module::getModuleURL('core/postredirect', ['RedirInfo' => $info]);
        return preg_replace('#^https:#', 'http:', $url);
    }


    /**
     * Retrieve Host value from $_SERVER environment variables.
     *
     * @return string The current host name, including the port if needed. It will use localhost when unable to
     *     determine the current host.
     *
     */
    private function getServerHost(): string
    {
        if (array_key_exists('HTTP_HOST', $_SERVER)) {
            $current = $_SERVER['HTTP_HOST'];
        } elseif (array_key_exists('SERVER_NAME', $_SERVER)) {
            $current = $_SERVER['SERVER_NAME'];
        } else {
            // almost certainly not what you want, but...
            $current = 'localhost';
        }

        if (str_contains($current, ":")) {
            $decomposed = explode(":", $current);
            $port = array_pop($decomposed);
            if (!is_numeric($port)) {
                $decomposed[] = $port;
            }
            $current = implode(":", $decomposed);
        }
        return $current;
    }


    /**
     * Retrieve HTTPS status from $_SERVER environment variables.
     *
     * @return boolean True if the request was performed through HTTPS, false otherwise.
     *
     */
    public function getServerHTTPS(): bool
    {
        if (!array_key_exists('HTTPS', $_SERVER)) {
            // not an https-request
            return false;
        }

        if ($_SERVER['HTTPS'] === 'off') {
            // IIS with HTTPS off
            return false;
        }

        // otherwise, HTTPS will be non-empty
        return !empty($_SERVER['HTTPS']);
    }


    /**
     * Retrieve the port number from $_SERVER environment variables.
     *
     * @return string The port number prepended by a colon, if it is different than the default port for the protocol
     *     (80 for HTTP, 443 for HTTPS), or an empty string otherwise.
     *
     */
    public function getServerPort(): string
    {
        $default_port = $this->getServerHTTPS() ? '443' : '80';
        $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : $default_port;

        // Take care of edge-case where SERVER_PORT is an integer
        $port = strval($port);

        if ($port !== $default_port) {
            return ':' . $port;
        }
        return '';
    }


    /**
     * Verify that a given URL is valid.
     *
     * @param string $url The URL we want to verify.
     *
     * @return boolean True if the given URL is valid, false otherwise.
     */
    public function isValidURL(string $url): bool
    {
        $url = filter_var($url, FILTER_VALIDATE_URL);
        if ($url === false) {
            return false;
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true)) {
            return true;
        }
        return false;
    }


    /**
     * This function redirects the user to the specified address.
     *
     * This function will use the "HTTP 303 See Other" redirection if the current request used the POST method and the
     * HTTP version is 1.1. Otherwise, a "HTTP 302 Found" redirection will be used.
     *
     * The function will also generate a simple web page with a clickable link to the target page.
     *
     * @param string   $url The URL we should redirect to. This URL may include query parameters. If this URL is a
     *     relative URL (starting with '/'), then it will be turned into an absolute URL by prefixing it with the
     *     absolute URL to the root of the website.
     * @param string[] $parameters An array with extra query string parameters which should be appended to the URL. The
     *     name of the parameter is the array index. The value of the parameter is the value stored in the index. Both
     *     the name and the value will be urlencoded. If the value is NULL, then the parameter will be encoded as just
     *     the name, without a value.
     *
     * @throws \InvalidArgumentException If $url is not a string or is empty, or $parameters is not an array.
     * @throws \SimpleSAML\Error\Exception If $url is not a valid HTTP URL.
     *
     */
    private function redirect(string $url, array $parameters = []): void
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('Invalid input parameters.');
        }

        if (!$this->isValidURL($url)) {
            throw new Error\Exception('Invalid destination URL: ' . $url);
        }

        if (!empty($parameters)) {
            $url = $this->addURLParameters($url, $parameters);
        }

        if (strlen($url) > 2048) {
            Logger::warning('Redirecting to a URL longer than 2048 bytes.');
        }

        if (!headers_sent()) {
            // set the location header
            header('Location: ' . $url, true, 303);

            // disable caching of this response
            header('Pragma: no-cache');
            header('Cache-Control: no-cache, no-store, must-revalidate');
        }

        // show a minimal web page with a clickable link to the URL
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"';
        echo ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n";
        echo '<html xmlns="http://www.w3.org/1999/xhtml">' . "\n";
        echo "  <head>\n";
        echo '    <meta http-equiv="content-type" content="text/html; charset=utf-8">' . "\n";
        echo '    <meta http-equiv="refresh" content="0;URL=\'' . htmlspecialchars($url) . '\'">' . "\n";
        echo "    <title>Redirect</title>\n";
        echo "  </head>\n";
        echo "  <body>\n";
        echo "    <h1>Redirect</h1>\n";
        echo '      <p>You were redirected to: <a id="redirlink" href="' . htmlspecialchars($url) . '">';
        echo htmlspecialchars($url) . "</a>\n";
        echo '        <script type="text/javascript">document.getElementById("redirlink").focus();</script>' . "\n";
        echo "      </p>\n";
        echo "  </body>\n";
        echo '</html>';

        // end script execution
        exit;
    }


    /**
     * Save the given HTTP POST data and the destination where it should be posted to a given session.
     *
     * @param \SimpleSAML\Session $session The session where to temporarily store the data.
     * @param string              $destination The destination URL where the form should be posted.
     * @param array               $data An associative array with the data to be posted to $destination.
     *
     * @return string A random identifier that can be used to retrieve the data from the current session.
     *
     */
    private function savePOSTData(Session $session, string $destination, array $data): string
    {
        // generate a random ID to avoid replay attacks
        $randomUtils = new Random();
        $id = $randomUtils->generateID();
        $postData = [
            'post' => $data,
            'url'  => $destination,
        ];

        // save the post data to the session, tied to the random ID
        $session->setData('core_postdatalink', $id, $postData);

        return $id;
    }


    /**
     * Add one or more query parameters to the given URL.
     *
     * @param string $url The URL the query parameters should be added to.
     * @param array  $parameters The query parameters which should be added to the url. This should be an associative
     *     array.
     *
     * @return string The URL with the new query parameters.
     * @throws \InvalidArgumentException If $url is not a string or $parameters is not an array.
     *
     */
    public function addURLParameters(string $url, array $parameters): string
    {
        $queryStart = strpos($url, '?');
        if ($queryStart === false) {
            $oldQuery = [];
            $url .= '?';
        } else {
            $oldQuery = substr($url, $queryStart + 1);
            if ($oldQuery === false) {
                $oldQuery = [];
            } else {
                $oldQuery = $this->parseQueryString($oldQuery);
            }
            $url = substr($url, 0, $queryStart + 1);
        }

        $query = array_merge($oldQuery, $parameters);
        $url .= http_build_query($query, '', '&');

        return $url;
    }


    /**
     * Check for session cookie, and show missing-cookie page if it is missing.
     *
     * @param string|null $retryURL The URL the user should access to retry the operation. Defaults to null.
     *
     *     page telling about the missing cookie.
     * @throws \InvalidArgumentException If $retryURL is neither a string nor null.
     *
     */
    public function checkSessionCookie(?string $retryURL = null): void
    {
        $session = Session::getSessionFromRequest();
        if ($session->hasSessionCookie()) {
            return;
        }

        // we didn't have a session cookie. Redirect to the no-cookie page

        $url = Module::getModuleURL('core/error/nocookie');
        if ($retryURL !== null) {
            $url = $this->addURLParameters($url, ['retryURL' => $retryURL]);
        }
        $this->redirectTrustedURL($url);
    }


    /**
     * Check if a URL is valid and is in our list of allowed URLs.
     *
     * @param string $url The URL to check.
     * @param string[] $trustedSites An optional white list of domains. If none specified, the 'trusted.url.domains'
     * configuration directive will be used.
     *
     * @return string The normalized URL itself if it is allowed. An empty string if the $url parameter is empty as
     * defined by the empty() function.
     * @throws \InvalidArgumentException If the URL is malformed.
     * @throws Error\Exception If the URL is not allowed by configuration.
     *
     */
    public function checkURLAllowed(string $url, array $trustedSites = null): string
    {
        if (empty($url)) {
            return '';
        }
        $url = $this->normalizeURL($url);

        if (!$this->isValidURL($url)) {
            throw new Error\Exception('Invalid URL: ' . $url);
        }

        // get the white list of domains
        if ($trustedSites === null) {
            $trustedSites = Configuration::getInstance()->getOptionalArray('trusted.url.domains', []);
        }

        // validates the URL's host is among those allowed
        if (is_array($trustedSites)) {
            $components = parse_url($url);
            $hostname = $components['host'];

            // check for userinfo
            if (
                (isset($components['user'])
                && strpos($components['user'], '\\') !== false)
                || (isset($components['pass'])
                && strpos($components['pass'], '\\') !== false)
            ) {
                throw new Error\Exception('Invalid URL: ' . $url);
            }

            // allow URLs with standard ports specified (non-standard ports must then be allowed explicitly)
            if (
                isset($components['port'])
                && (($components['scheme'] === 'http'
                && $components['port'] !== 80)
                || ($components['scheme'] === 'https'
                && $components['port'] !== 443))
            ) {
                $hostname = $hostname . ':' . $components['port'];
            }

            $self_host = $this->getSelfHostWithNonStandardPort();

            $trustedRegex = Configuration::getInstance()->getOptionalValue('trusted.url.regex', null);

            $trusted = false;
            if (!in_array($trustedRegex, [null, false])) {
                // add self host to the white list
                $trustedSites[] = preg_quote($self_host);
                foreach ($trustedSites as $regex) {
                    // Add start and end delimiters.
                    $regex = "@^{$regex}$@";
                    if (preg_match($regex, $hostname)) {
                        $trusted = true;
                        break;
                    }
                }
            } else {
                // add self host to the white list
                $trustedSites[] = $self_host;
                $trusted = in_array($hostname, $trustedSites, true);
            }

            // throw exception due to redirection to untrusted site
            if (!$trusted) {
                throw new Error\Exception('URL not allowed: ' . $url);
            }
        }
        return $url;
    }


    /**
     * Helper function to retrieve a file or URL with proxy support, also
     * supporting proxy basic authorization..
     *
     * An exception will be thrown if we are unable to retrieve the data.
     *
     * @param string  $url The path or URL we should fetch.
     * @param array   $context Extra context options. This parameter is optional.
     * @param boolean $getHeaders Whether to also return response headers. Optional.
     *
     * @return string|array An array if $getHeaders is set, containing the data and the headers respectively; string
     *  otherwise.
     * @throws \InvalidArgumentException If the input parameters are invalid.
     * @throws Error\Exception If the file or URL cannot be retrieved.
     *
     */
    public function fetch(string $url, array $context = [], bool $getHeaders = false)
    {
        $config = Configuration::getInstance();

        $proxy = $config->getOptionalString('proxy', null);
        if ($proxy !== null) {
            if (!isset($context['http']['proxy'])) {
                $context['http']['proxy'] = $proxy;
            }
            $proxy_auth = $config->getOptionalString('proxy.auth', null);
            if ($proxy_auth !== null) {
                $context['http']['header'] = "Proxy-Authorization: Basic " . base64_encode($proxy_auth);
            }
            if (!isset($context['http']['request_fulluri'])) {
                $context['http']['request_fulluri'] = true;
            }
            /*
             * If the remote endpoint over HTTPS uses the SNI extension (Server Name Indication RFC 4366), the proxy
             * could introduce a mismatch between the names in the Host: HTTP header and the SNI_server_name in TLS
             * negotiation (thanks to Cristiano Valli @ GARR-IDEM to have pointed this problem).
             * See: https://bugs.php.net/bug.php?id=63519
             * These controls will force the same value for both fields.
             * Marco Ferrante (marco@csita.unige.it), Nov 2012
             */
            if (
                preg_match('#^https#i', $url)
                && defined('OPENSSL_TLSEXT_SERVER_NAME')
                && OPENSSL_TLSEXT_SERVER_NAME
            ) {
                // extract the hostname
                $hostname = parse_url($url, PHP_URL_HOST);
                if (!empty($hostname)) {
                    $context['ssl'] = [
                        'SNI_server_name' => $hostname,
                        'SNI_enabled'     => true,
                    ];
                } else {
                    Logger::warning('Invalid URL format or local URL used through a proxy');
                }
            }
        }

        $context = stream_context_create($context);
        $data = @file_get_contents($url, false, $context);
        if ($data === false) {
            $error = error_get_last();
            throw new Error\Exception('Error fetching ' . var_export($url, true) . ':' .
                (is_array($error) ? $error['message'] : 'no error available'));
        }

        // data and headers
        if ($getHeaders) {
            if (!empty($http_response_header)) {
                $headers = [];
                foreach ($http_response_header as $h) {
                    if (preg_match('@^HTTP/1\.[01]\s+\d{3}\s+@', $h)) {
                        $headers = []; // reset
                        $headers[0] = $h;
                        continue;
                    }
                    $bits = explode(':', $h, 2);
                    if (count($bits) === 2) {
                        $headers[strtolower($bits[0])] = trim($bits[1]);
                    }
                }
            } else {
                // no HTTP headers, probably a different protocol, e.g. file
                $headers = null;
            }
            return [$data, $headers];
        }

        return $data;
    }


    /**
     * This function parses the Accept-Language HTTP header and returns an associative array with each language and the
     * score for that language. If a language includes a region, then the result will include both the language with
     * the region and the language without the region.
     *
     * The returned array will be in the same order as the input.
     *
     * @return array An associative array with each language and the score for that language.
     *
     */
    public function getAcceptLanguage(): array
    {
        if (!array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) {
            // no Accept-Language header, return an empty set
            return [];
        }

        $languages = explode(',', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));

        $ret = [];

        foreach ($languages as $l) {
            $opts = explode(';', $l);

            $l = trim(array_shift($opts)); // the language is the first element

            $q = 1.0;

            // iterate over all options, and check for the quality option
            foreach ($opts as $o) {
                $o = explode('=', $o);
                if (count($o) < 2) {
                    // skip option with no value
                    continue;
                }

                $name = trim($o[0]);
                $value = trim($o[1]);

                if ($name === 'q') {
                    $q = (float) $value;
                }
            }

            // remove the old key to ensure that the element is added to the end
            unset($ret[$l]);

            // set the quality in the result
            $ret[$l] = $q;

            if (strpos($l, '-')) {
                // the language includes a region part

                // extract the language without the region
                $l = explode('-', $l);
                $l = $l[0];

                // add this language to the result (unless it is defined already)
                if (!array_key_exists($l, $ret)) {
                    $ret[$l] = $q;
                }
            }
        }
        return $ret;
    }


    /**
     * Try to guess the base SimpleSAMLphp path from the current request.
     *
     * This method offers just a guess, so don't rely on it.
     *
     * @return string The guessed base path that should correspond to the root installation of SimpleSAMLphp.
     */
    public function guessBasePath(): string
    {
        if (!array_key_exists('REQUEST_URI', $_SERVER) || !array_key_exists('SCRIPT_FILENAME', $_SERVER)) {
            return '/';
        }
        // get the name of the current script
        $path = explode('/', $_SERVER['SCRIPT_FILENAME']);
        $script = array_pop($path);

        // get the portion of the URI up to the script, i.e.: /simplesaml/some/directory/script.php
        if (!preg_match('#^/(?:[^/]+/)*' . $script . '#', $_SERVER['REQUEST_URI'], $matches)) {
            return '/';
        }
        $uri_s = explode('/', $matches[0]);
        $file_s = explode('/', $_SERVER['SCRIPT_FILENAME']);

        // compare both arrays from the end, popping elements matching out of them
        while ($uri_s[count($uri_s) - 1] === $file_s[count($file_s) - 1]) {
            array_pop($uri_s);
            array_pop($file_s);
        }
        // we are now left with the minimum part of the URI that does not match anything in the file system, use it
        return join('/', $uri_s) . '/';
    }


    /**
     * Retrieve the base URL of the SimpleSAMLphp installation. The URL will always end with a '/'. For example:
     *      https://idp.example.org/simplesaml/
     *
     * @return string The absolute base URL for the SimpleSAMLphp installation.
     * @throws \SimpleSAML\Error\CriticalConfigurationError If 'baseurlpath' has an invalid format.
     *
     */
    public function getBaseURL(): string
    {
        $globalConfig = Configuration::getInstance();
        $baseURL = $globalConfig->getOptionalString('baseurlpath', 'simplesaml/');

        if (preg_match('#^https?://.*/?$#D', $baseURL, $matches)) {
            // full URL in baseurlpath, override local server values
            return rtrim($baseURL, '/') . '/';
        } elseif (
            (preg_match('#^/?([^/]?.*/)$#D', $baseURL, $matches))
            || (preg_match('#^\*(.*)/$#D', $baseURL, $matches))
            || ($baseURL === '')
        ) {
            // get server values
            $protocol = 'http';
            $protocol .= ($this->getServerHTTPS()) ? 's' : '';
            $protocol .= '://';

            $hostname = $this->getServerHost();
            $port = $this->getServerPort();
            $path = $globalConfig->getBasePath();

            return $protocol . $hostname . $port . $path;
        } else {
            /*
             * Invalid 'baseurlpath'. We cannot recover from this, so throw a critical exception and try to be graceful
             * with the configuration. Use a guessed base path instead of the one provided.
             */
            $c = $globalConfig->toArray();
            $c['baseurlpath'] = $this->guessBasePath();
            throw new Error\CriticalConfigurationError(
                'Invalid value for \'baseurlpath\' in config.php. Valid format is in the form: ' .
                '[(http|https)://(hostname|fqdn)[:port]]/[path/to/simplesaml/]. It must end with a \'/\'.',
                null,
                $c
            );
        }
    }


    /**
     * Create a link which will POST data.
     *
     * @param string $destination The destination URL.
     * @param array  $data The name-value pairs which will be posted to the destination.
     *
     * @return string  A URL which can be accessed to post the data.
     * @throws \InvalidArgumentException If $destination is not a string or $data is not an array.
     *
     */
    public function getPOSTRedirectURL(string $destination, array $data): string
    {
        $config = Configuration::getInstance();
        $allowed = $config->getOptionalBoolean('enable.http_post', false);

        if ($allowed && preg_match("#^http:#", $destination) && $this->isHTTPS()) {
            // we need to post the data to HTTP
            $url = $this->getSecurePOSTRedirectURL($destination, $data);
        } else {
            // post the data directly
            $session = Session::getSessionFromRequest();
            $id = $this->savePOSTData($session, $destination, $data);
            $url = Module::getModuleURL('core/postredirect', ['RedirId' => $id]);
        }

        return $url;
    }


    /**
     * Retrieve our own host.
     *
     * E.g. www.example.com
     *
     * @return string The current host.
     *
     */
    public function getSelfHost(): string
    {
        $decomposed = explode(':', $this->getSelfHostWithNonStandardPort());
        return array_shift($decomposed);
    }


    /**
     * Retrieve our own host, including the port in case the it is not standard for the protocol in use. That is port
     * 80 for HTTP and port 443 for HTTPS.
     *
     * E.g. www.example.com:8080
     *
     * @return string The current host, followed by a colon and the port number, in case the port is not standard for
     * the protocol.
     *
     */
    public function getSelfHostWithNonStandardPort(): string
    {
        $url = $this->getBaseURL();

        /** @var int $colon getBaseURL() will always return a valid URL */
        $colon = strpos($url, '://');
        $start = $colon + 3;
        $length = strcspn($url, '/', $start);

        return substr($url, $start, $length);
    }


    /**
     * Retrieve our own host together with the URL path. Please note this function will return the base URL for the
     * current SP, as defined in the global configuration.
     *
     * @return string The current host (with non-default ports included) plus the URL path.
     *
     */
    public function getSelfHostWithPath(): string
    {
        $baseurl = explode("/", $this->getBaseURL());
        $elements = array_slice($baseurl, 3 - count($baseurl), count($baseurl) - 4);
        $path = implode("/", $elements);
        return $this->getSelfHostWithNonStandardPort() . "/" . $path;
    }


    /**
     * Retrieve the current URL using the base URL in the configuration, if possible.
     *
     * This method will try to see if the current script is part of SimpleSAMLphp. In that case, it will use the
     * 'baseurlpath' configuration option to rebuild the current URL based on that. If the current script is NOT
     * part of SimpleSAMLphp, it will just return the current URL.
     *
     * Note that this method does NOT make use of the HTTP X-Forwarded-* set of headers.
     *
     * @return string The current URL, including query parameters.
     *
     */
    public function getSelfURL(): string
    {
        $cfg = Configuration::getInstance();
        $baseDir = $cfg->getBaseDir();
        $cur_path = realpath($_SERVER['SCRIPT_FILENAME']);
        // make sure we got a string from realpath()
        $cur_path = is_string($cur_path) ? $cur_path : '';
        // find the path to the current script relative to the public/ directory of SimpleSAMLphp
        $rel_path = str_replace($baseDir . 'public' . DIRECTORY_SEPARATOR, '', $cur_path);
        // convert that relative path to an HTTP query
        $url_path = str_replace(DIRECTORY_SEPARATOR, '/', $rel_path);
        // find where the relative path starts in the current request URI
        $uri_pos = (!empty($url_path)) ? strpos($_SERVER['REQUEST_URI'] ?? '', $url_path) : false;

        if ($cur_path == $rel_path || $uri_pos === false) {
            /*
             * We were accessed from an external script. This can happen in the following cases:
             *
             * - $_SERVER['SCRIPT_FILENAME'] points to a script that doesn't exist. E.g. functional testing. In this
             *   case, realpath() returns false and str_replace an empty string, so we compare them loosely.
             *
             * - The URI requested does not belong to a script in the public/ directory of SimpleSAMLphp. In that case,
             *   removing SimpleSAMLphp's base dir from the current path yields the same path, so $cur_path and
             *   $rel_path are equal.
             *
             * - The request URI does not match the current script. Even if the current script is located in the
             *   public/ directory of SimpleSAMLphp, the URI does not contain its relative path, and $uri_pos is false.
             *
             * It doesn't matter which one of those cases we have. We just know we can't apply our base URL to the
             * current URI, so we need to build it back from the PHP environment, unless we have a base URL specified
             * for this case in the configuration. First, check if that's the case.
             */
            $appcfg = $cfg->getOptionalConfigItem('application', null);
            $appurl = ($appcfg !== null) ? $appcfg->getOptionalString('baseURL', null) : null;

            if (!empty($appurl)) {
                $protocol = parse_url($appurl, PHP_URL_SCHEME);
                $hostname = parse_url($appurl, PHP_URL_HOST);
                $port = parse_url($appurl, PHP_URL_PORT);
                $port = !empty($port) ? ':' . $port : '';
            } else {
                // no base URL specified for app, just use the current URL
                $protocol = $this->getServerHTTPS() ? 'https' : 'http';
                $hostname = $this->getServerHost();
                $port = $this->getServerPort();
            }
            return $protocol . '://' . $hostname . $port . $_SERVER['REQUEST_URI'];
        }

        return $this->getBaseURL() . $url_path . substr($_SERVER['REQUEST_URI'], $uri_pos + strlen($url_path));
    }


    /**
     * Retrieve the current URL using the base URL in the configuration, containing the protocol, the host and
     * optionally, the port number.
     *
     * @return string The current URL without path or query parameters.
     *
     */
    public function getSelfURLHost(): string
    {
        $url = $this->getSelfURL();

        /** @var int $colon getBaseURL() will always return a valid URL */
        $colon = strpos($url, '://');
        $start = $colon + 3;
        $length = strcspn($url, '/', $start) + $start;
        return substr($url, 0, $length);
    }


    /**
     * Retrieve the current URL using the base URL in the configuration, without the query parameters.
     *
     * @return string The current URL, not including query parameters.
     *
     */
    public function getSelfURLNoQuery(): string
    {
        $url = $this->getSelfURL();
        $pos = strpos($url, '?');
        if (!$pos) {
            return $url;
        }
        return substr($url, 0, $pos);
    }


    /**
     * This function checks if we are using HTTPS as protocol.
     *
     * @return boolean True if the HTTPS is used, false otherwise.
     *
     */
    public function isHTTPS(): bool
    {
        return strpos($this->getSelfURL(), 'https://') === 0;
    }


    /**
     * Normalizes a URL to an absolute URL and validate it. In addition to resolving the URL, this function makes sure
     * that it is a link to an http or https site.
     *
     * @param string $url The relative URL.
     *
     * @return string An absolute URL for the given relative URL.
     * @throws \InvalidArgumentException If $url is not a string or a valid URL.
     *
     */
    public function normalizeURL(string $url): string
    {
        $url = $this->resolveURL($url, $this->getSelfURL());

        // verify that the URL is to a http or https site
        if (!preg_match('@^https?://@i', $url)) {
            throw new \InvalidArgumentException('Invalid URL: ' . $url);
        }

        return $url;
    }


    /**
     * Parse a query string into an array.
     *
     * This function parses a query string into an array, similar to the way the builtin 'parse_str' works, except it
     * doesn't handle arrays, and it doesn't do "magic quotes".
     *
     * Query parameters without values will be set to an empty string.
     *
     * @param string $query_string The query string which should be parsed.
     *
     * @return array The query string as an associative array.
     * @throws \InvalidArgumentException If $query_string is not a string.
     *
     */
    public function parseQueryString(string $query_string): array
    {
        $res = [];
        if (empty($query_string)) {
            return $res;
        }

        foreach (explode('&', $query_string) as $param) {
            $param = explode('=', $param);
            $name = urldecode($param[0]);
            if (count($param) === 1) {
                $value = '';
            } else {
                $value = urldecode($param[1]);
            }
            $res[$name] = $value;
        }
        return $res;
    }


    /**
     * This function redirects to the specified URL without performing any security checks. Please, do NOT use this
     * function with user supplied URLs.
     *
     * This function will use the "HTTP 303 See Other" redirection if the current request used the POST method and the
     * HTTP version is 1.1. Otherwise, a "HTTP 302 Found" redirection will be used.
     *
     * The function will also generate a simple web page with a clickable  link to the target URL.
     *
     * @param string   $url The URL we should redirect to. This URL may include query parameters. If this URL is a
     * relative URL (starting with '/'), then it will be turned into an absolute URL by prefixing it with the absolute
     * URL to the root of the website.
     * @param string[] $parameters An array with extra query string parameters which should be appended to the URL. The
     * name of the parameter is the array index. The value of the parameter is the value stored in the index. Both the
     * name and the value will be urlencoded. If the value is NULL, then the parameter will be encoded as just the
     * name, without a value.
     *
     * @throws \InvalidArgumentException If $url is not a string or $parameters is not an array.
     *
     */
    public function redirectTrustedURL(string $url, array $parameters = []): void
    {
        $url = $this->normalizeURL($url);
        $this->redirect($url, $parameters);
    }


    /**
     * This function redirects to the specified URL after performing the appropriate security checks on it.
     * Particularly, it will make sure that the provided URL is allowed by the 'trusted.url.domains' directive in the
     * configuration.
     *
     * If the aforementioned option is not set or the URL does correspond to a trusted site, it performs a redirection
     * to it. If the site is not trusted, an exception will be thrown.
     *
     * @param string   $url The URL we should redirect to. This URL may include query parameters. If this URL is a
     * relative URL (starting with '/'), then it will be turned into an absolute URL by prefixing it with the absolute
     * URL to the root of the website.
     * @param string[] $parameters An array with extra query string parameters which should be appended to the URL. The
     * name of the parameter is the array index. The value of the parameter is the value stored in the index. Both the
     * name and the value will be urlencoded. If the value is NULL, then the parameter will be encoded as just the
     * name, without a value.
     *
     * @throws \InvalidArgumentException If $url is not a string or $parameters is not an array.
     *
     */
    public function redirectUntrustedURL(string $url, array $parameters = []): void
    {
        $url = $this->checkURLAllowed($url);
        $this->redirect($url, $parameters);
    }


    /**
     * Resolve a (possibly relative) URL relative to a given base URL.
     *
     * This function supports these forms of relative URLs:
     * - ^\w+: Absolute URL. E.g. "http://www.example.com:port/path?query#fragment".
     * - ^// Same protocol. E.g. "//www.example.com:port/path?query#fragment"
     * - ^/ Same protocol and host. E.g. "/path?query#fragment".
     * - ^? Same protocol, host and path, replace query string & fragment. E.g. "?query#fragment".
     * - ^# Same protocol, host, path and query, replace fragment. E.g. "#fragment".
     * - The rest: Relative to the base path.
     *
     * @param string $url The relative URL.
     * @param string $base The base URL. Defaults to the base URL of this installation of SimpleSAMLphp.
     *
     * @return string An absolute URL for the given relative URL.
     * @throws \InvalidArgumentException If the base URL cannot be parsed into a valid URL, or the given parameters
     *     are not strings.
     *
     */
    public function resolveURL(string $url, string $base = null): string
    {
        if ($base === null) {
            $base = $this->getBaseURL();
        }

        if (!preg_match('/^((((\w+:)\/\/[^\/]+)(\/[^?#]*))(?:\?[^#]*)?)(?:#.*)?/', $base, $baseParsed)) {
            throw new \InvalidArgumentException('Unable to parse base url: ' . $base);
        }

        $baseDir = dirname($baseParsed[5] . 'filename');
        $baseScheme = $baseParsed[4];
        $baseHost = $baseParsed[3];
        $basePath = $baseParsed[2];
        $baseQuery = $baseParsed[1];

        if (preg_match('$^\w+:$', $url)) {
            return $url;
        }

        if (substr($url, 0, 2) === '//') {
            return $baseScheme . $url;
        }

        if ($url[0] === '/') {
            return $baseHost . $url;
        }
        if ($url[0] === '?') {
            return $basePath . $url;
        }
        if ($url[0] === '#') {
            return $baseQuery . $url;
        }

        // we have a relative path. Remove query string/fragment and save it as $tail
        $queryPos = strpos($url, '?');
        $fragmentPos = strpos($url, '#');
        if ($queryPos !== false || $fragmentPos !== false) {
            if ($queryPos === false) {
                $tailPos = $fragmentPos;
            } elseif ($fragmentPos === false) {
                $tailPos = $queryPos;
            } elseif ($queryPos < $fragmentPos) {
                $tailPos = $queryPos;
            } else {
                $tailPos = $fragmentPos;
            }

            $tail = substr($url, $tailPos);
            $dir = substr($url, 0, $tailPos);
        } else {
            $dir = $url;
            $tail = '';
        }

        $sysUtils = new System();
        $dir = $sysUtils->resolvePath($dir, $baseDir);

        return $baseHost . $dir . $tail;
    }


    /**
     * Set a cookie.
     *
     * @param string      $name The name of the cookie.
     * @param string|NULL $value The value of the cookie. Set to NULL to delete the cookie.
     * @param array|NULL  $params Cookie parameters.
     * @param bool        $throw Whether to throw exception if setcookie() fails.
     *
     * @throws \InvalidArgumentException If any parameter has an incorrect type.
     * @throws \SimpleSAML\Error\CannotSetCookie If the headers were already sent and the cookie cannot be set.
     *
     *
     */
    public function setCookie(string $name, ?string $value, array $params = null, bool $throw = true): void
    {
        $default_params = [
            'lifetime' => 0,
            'expire'   => null,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httponly' => true,
            'raw'      => false,
            'samesite' => null,
        ];

        if ($params !== null) {
            $params = array_merge($default_params, $params);
        } else {
            $params = $default_params;
        }

        // Do not set secure cookie if not on HTTPS
        if ($params['secure'] && !$this->isHTTPS()) {
            if ($throw) {
                throw new Error\CannotSetCookie(
                    'Setting secure cookie on plain HTTP is not allowed.',
                    Error\CannotSetCookie::SECURE_COOKIE
                );
            }
            Logger::warning('Error setting cookie: setting secure cookie on plain HTTP is not allowed.');
            return;
        }

        if ($value === null) {
            $expire = time() - 365 * 24 * 60 * 60;
            $value = strval($value);
        } elseif (isset($params['expire'])) {
            $expire = intval($params['expire']);
        } elseif ($params['lifetime'] === 0) {
            $expire = 0;
        } else {
            $expire = time() + intval($params['lifetime']);
        }

        if ($params['raw']) {
            /** @psalm-suppress InvalidArgument */
            $success = @setrawcookie(
                $name,
                $value,
                [
                    'expires' => $expire,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'],
                ]
            );
        } else {
            /** @psalm-suppress InvalidArgument */
            $success = @setcookie(
                $name,
                $value,
                [
                    'expires' => $expire,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'],
                ]
            );
        }

        if (!$success) {
            if ($throw) {
                throw new Error\CannotSetCookie(
                    'Headers already sent.',
                    Error\CannotSetCookie::HEADERS_SENT
                );
            }
            Logger::warning('Error setting cookie: headers already sent.');
        }
    }


    /**
     * Submit a POST form to a specific destination.
     *
     * This function never returns.
     *
     * @param string $destination The destination URL.
     * @param array  $data An associative array with the data to be posted to $destination.
     *
     * @throws \InvalidArgumentException If $destination is not a string or $data is not an array.
     * @throws \SimpleSAML\Error\Exception If $destination is not a valid HTTP URL.
     *
     *
     */
    public function submitPOSTData(string $destination, array $data): void
    {
        if (!$this->isValidURL($destination)) {
            throw new Error\Exception('Invalid destination URL: ' . $destination);
        }

        $config = Configuration::getInstance();
        $allowed = $config->getOptionalBoolean('enable.http_post', false);

        if ($allowed && preg_match("#^http:#", $destination) && $this->isHTTPS()) {
            // we need to post the data to HTTP
            $this->redirect($this->getSecurePOSTRedirectURL($destination, $data));
        }

        $p = new Template($config, 'post.twig');
        $p->data['destination'] = $destination;
        $p->data['post'] = $data;
        $p->send();
        exit(0);
    }
}
