<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\core\Controller\Login;

use function array_merge;
use function strval;

/**
 * Class that maps SimpleSAMLphp error codes to translateable strings.
 *
 * @package SimpleSAMLphp
 */
class ErrorCodes
{
    final public const ACSPARAMS = 'ACSPARAMS';
    final public const ARSPARAMS = 'ARSPARAMS';
    final public const AUTHSOURCEERROR = 'AUTHSOURCEERROR';
    final public const BADREQUEST = 'BADREQUEST';
    final public const CASERROR = 'CASERROR';
    final public const CONFIG = 'CONFIG';
    final public const CREATEREQUEST = 'CREATEREQUEST';
    final public const DISCOPARAMS = 'DISCOPARAMS';
    final public const GENERATEAUTHNRESPONSE = 'GENERATEAUTHNRESPONSE';
    final public const INVALIDCERT = 'INVALIDCERT';
    final public const LDAPERROR = 'LDAPERROR';
    final public const LOGOUTINFOLOST = 'LOGOUTINFOLOST';
    final public const LOGOUTREQUEST = 'LOGOUTREQUEST';
    final public const MEMCACHEDOWN = 'MEMCACHEDOWN';
    final public const METADATA = 'METADATA';
    final public const METADATANOTFOUND = 'METADATANOTFOUND';
    final public const METHODNOTALLOWED = 'METHODNOTALLOWED';
    final public const NOACCESS = 'NOACCESS';
    final public const NOCERT = 'NOCERT';
    final public const NORELAYSTATE = 'NORELAYSTATE';
    final public const NOSTATE = 'NOSTATE';
    final public const NOTFOUND = 'NOTFOUND';
    final public const NOTFOUNDREASON = 'NOTFOUNDREASON';
    final public const NOTSET = 'NOTSET';
    final public const ADMINNOTHASHED = 'ADMINNOTHASHED';
    final public const NOTVALIDCERT = 'NOTVALIDCERT';
    final public const PROCESSASSERTION = 'PROCESSASSERTION';
    final public const PROCESSAUTHNREQUEST = 'PROCESSAUTHNREQUEST';
    final public const RESPONSESTATUSNOSUCCESS = 'RESPONSESTATUSNOSUCCESS';
    final public const SLOSERVICEPARAMS = 'SLOSERVICEPARAMS';
    final public const SSOPARAMS = 'SSOPARAMS';
    final public const UNHANDLEDEXCEPTION = 'UNHANDLEDEXCEPTION';
    final public const UNKNOWNCERT = 'UNKNOWNCERT';
    final public const USERABORTED = 'USERABORTED';
    final public const WRONGUSERPASS = 'WRONGUSERPASS';
    final public const KEY_TITLE = 'title';
    final public const KEY_DESCRIPTION = 'descr';


    public function __construct()
    {
        // Automatically register instances of subclasses with Login to allow
        // custom ErrorCodes to work in a redirect environment
        Login::registerErrorCodeClass($this);
    }


    /**
     * Fetch all default translation strings for error code titles.
     *
     * @return array A map from error code to error code title
     */
    final public function getDefaultTitles(): array
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
            self::METHODNOTALLOWED => Translate::noop('Method not allowed'),
            self::NOACCESS => Translate::noop('No access'),
            self::NOCERT => Translate::noop('No certificate'),
            self::NORELAYSTATE => Translate::noop('No RelayState'),
            self::NOSTATE => Translate::noop('State information lost'),
            self::NOTFOUND => Translate::noop('Page not found'),
            self::NOTFOUNDREASON => Translate::noop('Page not found'),
            self::NOTSET => Translate::noop('Password not set'),
            self::ADMINNOTHASHED => Translate::noop('Admin password not set to a hashed value'),
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
     * Fetch all title translation strings for custom error codes.
     *
     * Extend this to define custom error codes and their title translations.
     *
     * @return array A map from custom error code to error code title
     */
    public function getCustomTitles(): array
    {
        return [];
    }


    /**
     * Fetch all translation strings for error code titles.
     *
     * @return array A map from error code to error code title
     */
    final public function getAllTitles(): array
    {
        return array_merge($this->getDefaultTitles(), $this->getCustomTitles());
    }


    /**
     * Fetch all default translation strings for error code descriptions.
     *
     * @return array A map from error code to error code description
     */
    final public function getDefaultDescriptions(): array
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
            self::METHODNOTALLOWED => Translate::noop('%MESSAGE%'),
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
            self::ADMINNOTHASHED => Translate::noop("" .
                "The password in the configuration (auth.adminpassword) is not a hashed value. " .
                "Full details on how to fix this are supplied at " .
                "https://github.com/simplesamlphp/simplesamlphp/wiki/" .
                "Frequently-Asked-Questions-(FAQ)#failed-to-login-to-the-" .
                "admin-page-with-and-error-message-admin-password-" .
                "not-set-to-a-hashed-value"),
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
     * Fetch all description translation strings for custom error codes.
     *
     * Extend this to define custom error codes and their description translations.
     *
     * @return array A map from error code to error code description
     */
    public function getCustomDescriptions(): array
    {
        return [];
    }


    public function getAllDescriptions(): array
    {
        return array_merge($this->getDefaultDescriptions(), $this->getCustomDescriptions());
    }


    /**
     * Get a map of both errorcode titles and descriptions
     *
     * Convenience-method for template-callers
     *
     * @return array An array containing both errorcode maps.
     */
    public function getAllMessages(): array
    {
        return [
            self::KEY_TITLE => $this->getAllTitles(),
            self::KEY_DESCRIPTION => $this->getAllDescriptions(),
        ];
    }


    /**
     * Fetch a translation string for a title for a given error code.
     *
     * @param string $errorCode The error code to look up
     *
     * @return string A string to translate
     */
    public function getTitle(string $errorCode): string
    {
        return strval($this->getAllTitles()[$errorCode] ?? Translate::addTagPrefix($errorCode, 'title_'));
    }


    /**
     * Fetch a translation string for a description for a given error code.
     *
     * @param string $errorCode The error code to look up
     *
     * @return string A string to translate
     */
    public function getDescription(string $errorCode): string
    {
        return strval($this->getAllDescriptions()[$errorCode] ?? Translate::addTagPrefix($errorCode, 'descr_'));
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
    public function getMessage(string $errorCode): array
    {
        return [
            self::KEY_TITLE => $this->getTitle($errorCode),
            self::KEY_DESCRIPTION => $this->getDescription($errorCode),
        ];
    }
}
