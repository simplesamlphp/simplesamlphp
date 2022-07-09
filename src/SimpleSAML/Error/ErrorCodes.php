<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use SimpleSAML\Locale\Translate;

/**
 * Class that maps SimpleSAMLphp error codes to translateable strings.
 *
 * @package SimpleSAMLphp
 */
class ErrorCodes
{
    /**
     * Fetch all default translation strings for error code titles.
     *
     * @return array A map from error code to error code title
     */
    final public static function defaultGetAllErrorCodeTitles(): array
    {
        return [
            'ACSPARAMS' => Translate::noop('No SAML response provided'),
            'ARSPARAMS' => Translate::noop('No SAML message provided'),
            'AUTHSOURCEERROR' => Translate::noop('Authentication source error'),
            'BADREQUEST' => Translate::noop('Bad request received'),
            'CASERROR' => Translate::noop('CAS Error'),
            'CONFIG' => Translate::noop('Configuration error'),
            'CREATEREQUEST' => Translate::noop('Error creating request'),
            'DISCOPARAMS' => Translate::noop('Bad request to discovery service'),
            'GENERATEAUTHNRESPONSE' => Translate::noop('Could not create authentication response'),
            'INVALIDCERT' => Translate::noop('Invalid certificate'),
            'LDAPERROR' => Translate::noop('LDAP Error'),
            'LOGOUTINFOLOST' => Translate::noop('Logout information lost'),
            'LOGOUTREQUEST' => Translate::noop('Error processing the Logout Request'),
            'MEMCACHEDOWN' => Translate::noop('Cannot retrieve session data'),
            'METADATA' => Translate::noop('Error loading metadata'),
            'METADATANOTFOUND' => Translate::noop('Metadata not found'),
            'NOACCESS' => Translate::noop('No access'),
            'NOCERT' => Translate::noop('No certificate'),
            'NORELAYSTATE' => Translate::noop('No RelayState'),
            'NOSTATE' => Translate::noop('State information lost'),
            'NOTFOUND' => Translate::noop('Page not found'),
            'NOTFOUNDREASON' => Translate::noop('Page not found'),
            'NOTSET' => Translate::noop('Password not set'),
            'NOTVALIDCERT' => Translate::noop('Invalid certificate'),
            'PROCESSASSERTION' => Translate::noop('Error processing response from Identity Provider'),
            'PROCESSAUTHNREQUEST' => Translate::noop('Error processing request from Service Provider'),
            'RESPONSESTATUSNOSUCCESS' => Translate::noop('Error received from Identity Provider'),
            'SLOSERVICEPARAMS' => Translate::noop('No SAML message provided'),
            'SSOPARAMS' => Translate::noop('No SAML request provided'),
            'UNHANDLEDEXCEPTION' => Translate::noop('Unhandled exception'),
            'UNKNOWNCERT' => Translate::noop('Unknown certificate'),
            'USERABORTED' => Translate::noop('Authentication aborted'),
            'WRONGUSERPASS' => Translate::noop('Incorrect username or password'),
        ];
    }


    /**
     * Fetch all translation strings for error code titles.
     *
     * Extend this to add error codes.
     *
     * @return array A map from error code to error code title
     */
    public static function getAllErrorCodeTitles(): array
    {
        return self::defaultGetAllErrorCodeTitles();
    }


    /**
     * Fetch all default translation strings for error code descriptions.
     *
     * @return array A map from error code to error code description
     */
    final public static function defaultGetAllErrorCodeDescriptions(): array
    {
        return [
            'ACSPARAMS' => Translate::noop("" .
                "You accessed the Assertion Consumer Service interface, but did not " .
                "provide a SAML Authentication Response. Please note that this endpoint is" .
                " not intended to be accessed directly."),
            'ARSPARAMS' => Translate::noop("" .
                "You accessed the Artifact Resolution Service interface, but did not " .
                "provide a SAML ArtifactResolve message. Please note that this endpoint is" .
                " not intended to be accessed directly."),
            'AUTHSOURCEERROR' => Translate::noop("" .
                'Authentication error in source %AUTHSOURCE%. The reason was: %REASON%'),
            'BADREQUEST' => Translate::noop('There is an error in the request to this page. The reason was: %REASON%'),
            'CASERROR' => Translate::noop('Error when communicating with the CAS server.'),
            'CONFIG' => Translate::noop('SimpleSAMLphp appears to be misconfigured.'),
            'CREATEREQUEST' => Translate::noop("An error occurred when trying to create the SAML request."),
            'DISCOPARAMS' => Translate::noop("" .
                "The parameters sent to the discovery service were not according to " .
                "specifications."),
            'GENERATEAUTHNRESPONSE' => Translate::noop("" .
                "When this identity provider tried to create an authentication response, " .
                "an error occurred."),
            'INVALIDCERT' => Translate::noop("" .
                "Authentication failed: the certificate your browser sent is invalid or " .
                "cannot be read"),
            'LDAPERROR' => Translate::noop("" .
                "LDAP is the user database, and when you try to login, we need to contact " .
                "an LDAP database. An error occurred when we tried it this time."),
            'LOGOUTINFOLOST' => Translate::noop("" .
                "The information about the current logout operation has been lost. You " .
                "should return to the service you were trying to log out from and try to " .
                "log out again. This error can be caused by the logout information " .
                "expiring. The logout information is stored for a limited amount of time - " .
                "usually a number of hours. This is longer than any normal logout " .
                "operation should take, so this error may indicate some other error with " .
                "the configuration. If the problem persists, contact your service " .
                "provider."),
            'LOGOUTREQUEST' => Translate::noop('An error occurred when trying to process the Logout Request.'),
            'MEMCACHEDOWN' => Translate::noop("" .
                "Your session data cannot be retrieved right now due to technical " .
                "difficulties. Please try again in a few minutes."),
            'METADATA' => Translate::noop("" .
                "There is some misconfiguration of your SimpleSAMLphp installation. If you" .
                " are the administrator of this service, you should make sure your " .
                "metadata configuration is correctly setup."),
            'METADATANOTFOUND' => Translate::noop('Unable to locate metadata for %ENTITYID%'),
            'NOACCESS' => Translate::noop("" .
                "This endpoint is not enabled. Check the enable options in your " .
                "configuration of SimpleSAMLphp."),
            'NOCERT' => Translate::noop('Authentication failed: your browser did not send any certificate'),
            'NORELAYSTATE' => Translate::noop("" .
                "The initiator of this request did not provide a RelayState parameter " .
                "indicating where to go next."),
            'NOSTATE' => Translate::noop('State information lost, and no way to restart the request'),
            'NOTFOUND' => Translate::noop('The given page was not found. The URL was: %URL%'),
            'NOTFOUNDREASON' => Translate::noop("" .
                "The given page was not found. The reason was: %REASON%  The URL was: %URL%"),
            'NOTSET' => Translate::noop("" .
                "The password in the configuration (auth.adminpassword) is not changed " .
                "from the default value. Please edit the configuration file."),
            'NOTVALIDCERT' => Translate::noop('You did not present a valid certificate.'),
            'PROCESSASSERTION' => Translate::noop('We did not accept the response sent from the Identity Provider.'),
            'PROCESSAUTHNREQUEST' => Translate::noop("" .
                "This Identity Provider received an Authentication Request from a Service " .
                "Provider, but an error occurred when trying to process the request."),
            'RESPONSESTATUSNOSUCCESS' => Translate::noop("" .
                "The Identity Provider responded with an error. (The status code in the " .
                "SAML Response was not success)"),
            'SLOSERVICEPARAMS' => Translate::noop("" .
                "You accessed the SingleLogoutService interface, but did not provide a " .
                "SAML LogoutRequest or LogoutResponse. Please note that this endpoint is " .
                "not intended to be accessed directly."),
            'SSOPARAMS' => Translate::noop("" .
                "You accessed the Single Sign On Service interface, but did not provide a " .
                "SAML Authentication Request. Please note that this endpoint is not " .
                "intended to be accessed directly."),
            'UNHANDLEDEXCEPTION' => Translate::noop('An unhandled exception was thrown.'),
            'UNKNOWNCERT' => Translate::noop('Authentication failed: the certificate your browser sent is unknown'),
            'USERABORTED' => Translate::noop('The authentication was aborted by the user'),
            'WRONGUSERPASS' => Translate::noop("" .
                "Either no user with the given username could be found, or the password " .
                "you gave was wrong. Please check the username and try again."),
        ];
    }

    /**
     * Fetch all translation strings for error code descriptions.
     *
     * Extend this to add error codes.
     *
     * @return array A map from error code to error code description
     */
    public static function getAllErrorCodeDescriptions(): array
    {
        return self::defaultGetAllErrorCodeDescriptions();
    }


    /**
     * Get a map of both errorcode titles and descriptions
     *
     * Convenience-method for template-callers
     *
     * @return array An array containing both errorcode maps.
     */
    public static function getAllErrorCodeMessages(): array
    {
        return [
            'title' => self::getAllErrorCodeTitles(),
            'descr' => self::getAllErrorCodeDescriptions(),
        ];
    }


    /**
     * Fetch a translation string for a title for a given error code.
     *
     * @param string $errorCode The error code to look up
     *
     * @return string A string to translate
     */
    public static function getErrorCodeTitle(string $errorCode): string
    {
        if (array_key_exists($errorCode, self::getAllErrorCodeTitles())) {
            $errorCodeTitles = self::getAllErrorCodeTitles();
            return $errorCodeTitles[$errorCode];
        } else {
            return Translate::addTagPrefix($errorCode, 'title_');
        }
    }


    /**
     * Fetch a translation string for a description for a given error code.
     *
     * @param string $errorCode The error code to look up
     *
     * @return string A string to translate
     */
    public static function getErrorCodeDescription(string $errorCode): string
    {
        if (array_key_exists($errorCode, self::getAllErrorCodeTitles())) {
            $errorCodeDescriptions = self::getAllErrorCodeDescriptions();
            return $errorCodeDescriptions[$errorCode];
        } else {
            return Translate::addTagPrefix($errorCode, 'descr_');
        }
    }


    /**
     * Get both title and description for a specific error code
     *
     * Convenience-method for template-callers
     *
     * @param string $errorCode The error code to look up
     *
     * @return array An array containing both errorcode strings.
     */
    public static function getErrorCodeMessage(string $errorCode): array
    {
        return [
            'title' => self::getErrorCodeTitle($errorCode),
            'descr' => self::getErrorCodeDescription($errorCode),
        ];
    }
}
