<?php
namespace SimpleSAML\Utils;


/**
 * HTTP-related utility methods.
 *
 * @package SimpleSAMLphp
 */
class HTTP
{

    /**
     * Retrieve Host value from $_SERVER environment variables.
     *
     * @return string The current host name, including the port if needed. It will use localhost when unable to
     *     determine the current host.
     *
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    private static function getServerHost()
    {
        if (array_key_exists('HTTP_HOST', $_SERVER)) {
            $current = $_SERVER['HTTP_HOST'];
        } elseif (array_key_exists('SERVER_NAME', $_SERVER)) {
            $current = $_SERVER['SERVER_NAME'];
        } else {
            // almost certainly not what you want, but...
            $current = 'localhost';
        }

        if (strstr($current, ":")) {
            $decomposed = explode(":", $current);
            $port = array_pop($decomposed);
            if (!is_numeric($port)) {
                array_push($decomposed, $port);
            }
            $current = implode($decomposed, ":");
        }
        return $current;
    }


    /**
     * Retrieve HTTPS status from $_SERVER environment variables.
     *
     * @return boolean True if the request was performed through HTTPS, false otherwise.
     *
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    private static function getServerHTTPS()
    {
        if (!array_key_exists('HTTPS', $_SERVER)) {
            // not an https-request
            return false;
        }

        if ($_SERVER['HTTPS'] === 'off') {
            // IIS with HTTPS off
            return false;
        }

        // otherwise, HTTPS will be a non-empty string
        return $_SERVER['HTTPS'] !== '';
    }


    /**
     * Retrieve the port number from $_SERVER environment variables.
     *
     * @return string The port number prepended by a colon, if it is different than the default port for the protocol
     *     (80 for HTTP, 443 for HTTPS), or an empty string otherwise.
     *
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    private static function getServerPort()
    {
        $port = (isset($_SERVER['SERVER_PORT'])) ? $_SERVER['SERVER_PORT'] : '80';
        if (self::getServerHTTPS()) {
            if ($port !== '443') {
                $port = ':'.$port;
            }
        } else {
            if ($port !== '80') {
                $port = ':'.$port;
            }
        }
        return $port;
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
     * @return void This function never returns.
     * @throws \SimpleSAML_Error_Exception If $url is not a string or is empty, or $parameters is not an array.
     *
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     * @author Mads Freek Petersen
     * @author Jaime Perez, UNINETT AS <jaime.perez@uninett.no>
     */
    private static function redirect($url, $parameters = array())
    {
        if (!is_string($url) || empty($url) || !is_array($parameters)) {
            throw new \SimpleSAML_Error_Exception('Invalid input parameters.');
        }
        if (!empty($parameters)) {
            $url = self::addURLParameters($url, $parameters);
        }

        /* Set the HTTP result code. This is either 303 See Other or
         * 302 Found. HTTP 303 See Other is sent if the HTTP version
         * is HTTP/1.1 and the request type was a POST request.
         */
        if ($_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1' &&
            $_SERVER['REQUEST_METHOD'] === 'POST'
        ) {
            $code = 303;
        } else {
            $code = 302;
        }

        if (strlen($url) > 2048) {
            \SimpleSAML_Logger::warning('Redirecting to a URL longer than 2048 bytes.');
        }

        // set the location header
        header('Location: '.$url, true, $code);

        // disable caching of this response
        header('Pragma: no-cache');
        header('Cache-Control: no-cache, must-revalidate');

        // show a minimal web page with a clickable link to the URL
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"';
        echo ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
        echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
        echo "  <head>\n";
        echo '    <meta http-equiv="content-type" content="text/html; charset=utf-8">'."\n";
        echo "    <title>Redirect</title>\n";
        echo "  </head>\n";
        echo "  <body>\n";
        echo "    <h1>Redirect</h1>\n";
        echo '      <p>You were redirected to: <a id="redirlink" href="'.htmlspecialchars($url).'">';
        echo htmlspecialchars($url)."</a>\n";
        echo '        <script type="text/javascript">document.getElementById("redirlink").focus();</script>'."\n";
        echo "      </p>\n";
        echo "  </body>\n";
        echo '</html>';

        // end script execution
        exit;
    }


    /**
     * Add one or more query parameters to the given URL.
     *
     * @param string $url The URL the query parameters should be added to.
     * @param array  $parameters The query parameters which should be added to the url. This should be an associative
     *     array.
     *
     * @return string The URL with the new query parameters.
     * @throws \SimpleSAML_Error_Exception If $url is not a string or $parameters is not an array.
     *
     * @author Andreas Solberg, UNINETT AS <andreas.solberg@uninett.no>
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function addURLParameters($url, $parameters)
    {
        if (!is_string($url) || !is_array($parameters)) {
            throw new \SimpleSAML_Error_Exception('Invalid input parameters.');
        }

        $queryStart = strpos($url, '?');
        if ($queryStart === false) {
            $oldQuery = array();
            $url .= '?';
        } else {
            $oldQuery = substr($url, $queryStart + 1);
            if ($oldQuery === false) {
                $oldQuery = array();
            } else {
                $oldQuery = self::parseQueryString($oldQuery);
            }
            $url = substr($url, 0, $queryStart + 1);
        }

        $query = array_merge($oldQuery, $parameters);
        $url .= http_build_query($query, '', '&');

        return $url;
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
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function getAcceptLanguage()
    {
        if (!array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) {
            // no Accept-Language header, return an empty set
            return array();
        }

        $languages = explode(',', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));

        $ret = array();

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
     * Retrieve the base URL of the SimpleSAMLphp installation. The URL will always end with a '/'. For example:
     *      https://idp.example.org/simplesaml/
     *
     * @return string The absolute base URL for the simpleSAMLphp installation.
     * @throws \SimpleSAML_Error_Exception If 'baseurlpath' has an invalid format.
     *
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function getBaseURL()
    {
        $globalConfig = \SimpleSAML_Configuration::getInstance();
        $baseURL = $globalConfig->getString('baseurlpath', 'simplesaml/');

        if (preg_match('#^https?://.*/$#D', $baseURL, $matches)) {
            // full URL in baseurlpath, override local server values
            return $baseURL;
        } elseif (
            (preg_match('#^/?([^/]?.*/)$#D', $baseURL, $matches)) ||
            (preg_match('#^\*(.*)/$#D', $baseURL, $matches)) ||
            ($baseURL === '')
        ) {
            // get server values
            $protocol = 'http';
            $protocol .= (self::getServerHTTPS()) ? 's' : '';
            $protocol .= '://';

            $hostname = self::getServerHost();
            $port = self::getServerPort();
            $path = '/'.$globalConfig->getBaseURL();

            return $protocol.$hostname.$port.$path;
        } else {
            throw new \SimpleSAML_Error_Exception('Invalid value for \'baseurlpath\' in '.
                'config.php. Valid format is in the form: '.
                '[(http|https)://(hostname|fqdn)[:port]]/[path/to/simplesaml/]. '.
                'It must end with a \'/\'.');
        }
    }


    /**
     * Retrieve the first element of the URL path.
     *
     * @param boolean $trailingslash Whether to add a trailing slash to the element or not. Defaults to true.
     *
     * @return string The first element of the URL path, with an optional, trailing slash.
     *
     * @author Andreas Solberg, UNINETT AS <andreas.solberg@uninett.no>
     */
    public static function getFirstPathElement($trailingslash = true)
    {
        if (preg_match('|^/(.*?)/|', $_SERVER['SCRIPT_NAME'], $matches)) {
            return ($trailingslash ? '/' : '').$matches[1];
        }
        return '';
    }


    /**
     * Retrieve our own host.
     *
     * @return string The current host (with non-default ports included).
     *
     * @author Andreas Solberg, UNINETT AS <andreas.solberg@uninett.no>
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function getSelfHost()
    {
        $url = self::getBaseURL();

        $start = strpos($url, '://') + 3;
        $length = strcspn($url, '/:', $start);

        return substr($url, $start, $length);
    }


    /**
     * Retrieve our own host together with the URL path. Please note this function will return the base URL for the
     * current SP, as defined in the global configuration.
     *
     * @return string The current host (with non-default ports included) plus the URL path.
     *
     * @author Andreas Solberg, UNINETT AS <andreas.solberg@uninett.no>
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function getSelfHostWithPath()
    {
        $baseurl = explode("/", self::getBaseURL());
        $elements = array_slice($baseurl, 3 - count($baseurl), count($baseurl) - 4);
        $path = implode("/", $elements);
        return self::getSelfHost()."/".$path;
    }


    /**
     * Retrieve a URL containing the protocol, the current host and optionally, the port number.
     *
     * @return string The current URL without a URL path or query parameters.
     *
     * @author Andreas Solberg, UNINETT AS <andreas.solberg@uninett.no>
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function getSelfURLHost()
    {
        $url = self::getBaseURL();
        $start = strpos($url, '://') + 3;
        $length = strcspn($url, '/', $start) + $start;
        return substr($url, 0, $length);
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
     * @throws \SimpleSAML_Error_Exception If $query_string is not a string.
     *
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function parseQueryString($query_string)
    {
        if (!is_string($query_string)) {
            throw new \SimpleSAML_Error_Exception('Invalid input parameters.');
        }

        $res = array();
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
     * Resolve a (possibly) relative path from the given base path.
     *
     * A path which starts with a '/' is assumed to be absolute, all others are assumed to be
     * relative. The default base path is the root of the SimpleSAMPphp installation.
     *
     * @param string      $path The path we should resolve.
     * @param string|null $base The base path, where we should search for $path from. Default value is the root of the
     *     SimpleSAMLphp installation.
     *
     * @return string An absolute path referring to $path.
     *
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function resolvePath($path, $base = null)
    {
        if ($base === null) {
            $config = \SimpleSAML_Configuration::getInstance();
            $base = $config->getBaseDir();
        }

        // remove trailing slashes from $base
        while (substr($base, -1) === '/') {
            $base = substr($base, 0, -1);
        }

        // check for absolute path
        if (substr($path, 0, 1) === '/') {
            // absolute path. */
            $ret = '/';
        } else {
            // path relative to base
            $ret = $base;
        }

        $path = explode('/', $path);
        foreach ($path as $d) {
            if ($d === '.') {
                continue;
            } elseif ($d === '..') {
                $ret = dirname($ret);
            } else {
                if (substr($ret, -1) !== '/') {
                    $ret .= '/';
                }
                $ret .= $d;
            }
        }

        return $ret;
    }
}