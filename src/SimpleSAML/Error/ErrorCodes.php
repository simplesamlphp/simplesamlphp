<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use SimpleSAML\Locale\Translate;

use function array_key_exists;

/**
 * Class that maps SimpleSAMLphp error codes to translateable strings.
 *
 * @package SimpleSAMLphp
 */
class ErrorCodes
{
    // TODO PHPv8.1 - Consider moving to final consts for these default error codes to prevent overrides.
    public const ACSPARAMS = 'ACSPARAMS';
    public const ARSPARAMS = 'ARSPARAMS';
    public const AUTHSOURCEERROR = 'AUTHSOURCEERROR';
    public const BADREQUEST = 'BADREQUEST';
    public const CASERROR = 'CASERROR';
    public const CONFIG = 'CONFIG';
    public const CREATEREQUEST = 'CREATEREQUEST';
    public const DISCOPARAMS = 'DISCOPARAMS';
    public const GENERATEAUTHNRESPONSE = 'GENERATEAUTHNRESPONSE';
    public const INVALIDCERT = 'INVALIDCERT';
    public const LDAPERROR = 'LDAPERROR';
    public const LOGOUTINFOLOST = 'LOGOUTINFOLOST';
    public const LOGOUTREQUEST = 'LOGOUTREQUEST';
    public const MEMCACHEDOWN = 'MEMCACHEDOWN';
    public const METADATA = 'METADATA';
    public const METADATANOTFOUND = 'METADATANOTFOUND';
    public const NOACCESS = 'NOACCESS';
    public const NOCERT = 'NOCERT';
    public const NORELAYSTATE = 'NORELAYSTATE';
    public const NOSTATE = 'NOSTATE';
    public const NOTFOUND = 'NOTFOUND';
    public const NOTFOUNDREASON = 'NOTFOUNDREASON';
    public const NOTSET = 'NOTSET';
    public const NOTVALIDCERT = 'NOTVALIDCERT';
    public const PROCESSASSERTION = 'PROCESSASSERTION';
    public const PROCESSAUTHNREQUEST = 'PROCESSAUTHNREQUEST';
    public const RESPONSESTATUSNOSUCCESS = 'RESPONSESTATUSNOSUCCESS';
    public const SLOSERVICEPARAMS = 'SLOSERVICEPARAMS';
    public const SSOPARAMS = 'SSOPARAMS';
    public const UNHANDLEDEXCEPTION = 'UNHANDLEDEXCEPTION';
    public const UNKNOWNCERT = 'UNKNOWNCERT';
    public const USERABORTED = 'USERABORTED';
    public const WRONGUSERPASS = 'WRONGUSERPASS';


    /**
     * Fetch all default translation strings for error code titles.
     *
     * @return array A map from error code to error code title
     */
    final public static function defaultGetAllErrorCodeTitles(): array
    {
        return [
            self::ACSPARAMS => Translate::noop('No SAML response provided'),
            self::ARSPARAMS => Translate::noop('No SAML message provided'),
            self::AUTHSOURCEERROR => Translate::noop('Authentication source error'),
            self::BADREQUEST => Translate::noop('Bad request received'),
            self::CASERROR => Translate::noop('CAS Error'),
            self::CONFIG => Translate::noop('Configuration error'),
            self::CREATEREQUEST => Translate::noop('Error creating request'),
            self::DISCOPARAMS => Translate::noop('Bad request to discovery service'),
            self::GENERATEAUTHNRESPONSE => Translate::noop('Could not create authentication response'),
            self::INVALIDCERT => Translate::noop('Invalid certificate'),
            self::LDAPERROR => Translate::noop('LDAP Error'),
            self::LOGOUTINFOLOST => Translate::noop('Logout information lost'),
            self::LOGOUTREQUEST => Translate::noop('Error processing the Logout Request'),
            self::MEMCACHEDOWN => Translate::noop('Cannot retrieve session data'),
            self::METADATA => Translate::noop('Error loading metadata'),
            self::METADATANOTFOUND => Translate::noop('Metadata not found'),
            self::NOACCESS => Translate::noop('No access'),
            self::NOCERT => Translate::noop('No certificate'),
            self::NORELAYSTATE => Translate::noop('No RelayState'),
            self::NOSTATE => Translate::noop('State information lost'),
            self::NOTFOUND => Translate::noop('Page not found'),
            self::NOTFOUNDREASON => Translate::noop('Page not found'),
            self::NOTSET => Translate::noop('Password not set'),
            self::NOTVALIDCERT => Translate::noop('Invalid certificate'),
            self::PROCESSASSERTION => Translate::noop('Error processing response from Identity Provider'),
            self::PROCESSAUTHNREQUEST => Translate::noop('Error processing request from Service Provider'),
            self::RESPONSESTATUSNOSUCCESS => Translate::noop('Error received from Identity Provider'),
            self::SLOSERVICEPARAMS => Translate::noop('No SAML message provided'),
            self::SSOPARAMS => Translate::noop('No SAML request provided'),
            self::UNHANDLEDEXCEPTION => Translate::noop('Unhandled exception'),
            self::UNKNOWNCERT => Translate::noop('Unknown certificate'),
            self::USERABORTED => Translate::noop('Authentication aborted'),
            self::WRONGUSERPASS => Translate::noop('Incorrect username or password'),
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
            self::ACSPARAMS => Translate::noop("" .
                "You accessed the Assertion Consumer Service interface, but did not " .
                "provide a SAML Authentication Response. Please note that this endpoint is" .
                " not intended to be accessed directly."),
            self::ARSPARAMS => Translate::noop("" .
                "You accessed the Artifact Resolution Service interface, but did not " .
                "provide a SAML ArtifactResolve message. Please note that this endpoint is" .
                " not intended to be accessed directly."),
            self::AUTHSOURCEERROR => Translate::noop("" .
                'Authentication error in source %AUTHSOURCE%. The reason was: %REASON%'),
            self::BADREQUEST => Translate::noop('There is an error in the request to this page. The reason was: %REASON%'),
            self::CASERROR => Translate::noop('Error when communicating with the CAS server.'),
            self::CONFIG => Translate::noop('SimpleSAMLphp appears to be misconfigured.'),
            self::CREATEREQUEST => Translate::noop("An error occurred when trying to create the SAML request."),
            self::DISCOPARAMS => Translate::noop("" .
                "The parameters sent to the discovery service were not according to " .
                "specifications."),
            self::GENERATEAUTHNRESPONSE => Translate::noop("" .
                "When this identity provider tried to create an authentication response, " .
                "an error occurred."),
            self::INVALIDCERT => Translate::noop("" .
                "Authentication failed: the certificate your browser sent is invalid or " .
                "cannot be read"),
            self::LDAPERROR => Translate::noop("" .
                "LDAP is the user database, and when you try to login, we need to contact " .
                "an LDAP database. An error occurred when we tried it this time."),
            self::LOGOUTINFOLOST => Translate::noop("" .
                "The information about the current logout operation has been lost. You " .
                "should return to the service you were trying to log out from and try to " .
                "log out again. This error can be caused by the logout information " .
                "expiring. The logout information is stored for a limited amount of time - " .
                "usually a number of hours. This is longer than any normal logout " .
                "operation should take, so this error may indicate some other error with " .
                "the configuration. If the problem persists, contact your service " .
                "provider."),
            self::LOGOUTREQUEST => Translate::noop('An error occurred when trying to process the Logout Request.'),
            self::MEMCACHEDOWN => Translate::noop("" .
                "Your session data cannot be retrieved right now due to technical " .
                "difficulties. Please try again in a few minutes."),
            self::METADATA => Translate::noop("" .
                "There is some misconfiguration of your SimpleSAMLphp installation. If you" .
                " are the administrator of this service, you should make sure your " .
                "metadata configuration is correctly setup."),
            self::METADATANOTFOUND => Translate::noop('Unable to locate metadata for %ENTITYID%'),
            self::NOACCESS => Translate::noop("" .
                "This endpoint is not enabled. Check the enable options in your " .
                "configuration of SimpleSAMLphp."),
            self::NOCERT => Translate::noop('Authentication failed: your browser did not send any certificate'),
            self::NORELAYSTATE => Translate::noop("" .
                "The initiator of this request did not provide a RelayState parameter " .
                "indicating where to go next."),
            self::NOSTATE => Translate::noop('State information lost, and no way to restart the request'),
            self::NOTFOUND => Translate::noop('The given page was not found. The URL was: %URL%'),
            self::NOTFOUNDREASON => Translate::noop("" .
                "The given page was not found. The reason was: %REASON%  The URL was: %URL%"),
            self::NOTSET => Translate::noop("" .
                "The password in the configuration (auth.adminpassword) is not changed " .
                "from the default value. Please edit the configuration file."),
            self::NOTVALIDCERT => Translate::noop('You did not present a valid certificate.'),
            self::PROCESSASSERTION => Translate::noop('We did not accept the response sent from the Identity Provider.'),
            self::PROCESSAUTHNREQUEST => Translate::noop("" .
                "This Identity Provider received an Authentication Request from a Service " .
                "Provider, but an error occurred when trying to process the request."),
            self::RESPONSESTATUSNOSUCCESS => Translate::noop("" .
                "The Identity Provider responded with an error. (The status code in the " .
                "SAML Response was not success)"),
            self::SLOSERVICEPARAMS => Translate::noop("" .
                "You accessed the SingleLogoutService interface, but did not provide a " .
                "SAML LogoutRequest or LogoutResponse. Please note that this endpoint is " .
                "not intended to be accessed directly."),
            self::SSOPARAMS => Translate::noop("" .
                "You accessed the Single Sign On Service interface, but did not provide a " .
                "SAML Authentication Request. Please note that this endpoint is not " .
                "intended to be accessed directly."),
            self::UNHANDLEDEXCEPTION => Translate::noop('An unhandled exception was thrown.'),
            self::UNKNOWNCERT => Translate::noop('Authentication failed: the certificate your browser sent is unknown'),
            self::USERABORTED => Translate::noop('The authentication was aborted by the user'),
            self::WRONGUSERPASS => Translate::noop("" .
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
        if (array_key_exists($errorCode, self::getAllErrorCodeDescriptions())) {
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
