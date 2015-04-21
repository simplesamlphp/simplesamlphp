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
}