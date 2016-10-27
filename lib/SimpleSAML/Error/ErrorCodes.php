<?php
/**
 * Class that maps SimpleSAMLphp error codes to translateable strings.
 *
 * @author Hanne Moa, UNINETT AS. <hanne.moa@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Error;

class ErrorCodes
{
    /**
     * Fetch all default translation strings for error code titles.
     *
     * @return array A map from error code to error code title
     */
    final public static function defaultGetAllErrorCodeTitles()
    {
        return array(
            'ACSPARAMS' => \SimpleSAML\Locale\Translate::noop('{error:title_ACSPARAMS}'),
            'ARSPARAMS' => \SimpleSAML\Locale\Translate::noop('{error:title_ARSPARAMS}'),
            'AUTHSOURCEERROR' => \SimpleSAML\Locale\Translate::noop('{error:title_AUTHSOURCEERROR}'),
            'BADREQUEST' => \SimpleSAML\Locale\Translate::noop('{error:title_BADREQUEST}'),
            'CASERROR' => \SimpleSAML\Locale\Translate::noop('{error:title_CASERROR}'),
            'CONFIG' => \SimpleSAML\Locale\Translate::noop('{error:title_CONFIG}'),
            'CREATEREQUEST' => \SimpleSAML\Locale\Translate::noop('{error:title_CREATEREQUEST}'),
            'DISCOPARAMS' => \SimpleSAML\Locale\Translate::noop('{error:title_DISCOPARAMS}'),
            'GENERATEAUTHNRESPONSE' => \SimpleSAML\Locale\Translate::noop('{error:title_GENERATEAUTHNRESPONSE}'),
            'INVALIDCERT' => \SimpleSAML\Locale\Translate::noop('{error:title_INVALIDCERT}'),
            'LDAPERROR' => \SimpleSAML\Locale\Translate::noop('{error:title_LDAPERROR}'),
            'LOGOUTINFOLOST' => \SimpleSAML\Locale\Translate::noop('{error:title_LOGOUTINFOLOST}'),
            'LOGOUTREQUEST' => \SimpleSAML\Locale\Translate::noop('{error:title_LOGOUTREQUEST}'),
            'MEMCACHEDOWN' => \SimpleSAML\Locale\Translate::noop('{error:title_MEMCACHEDOWN}'),
            'METADATA' => \SimpleSAML\Locale\Translate::noop('{error:title_METADATA}'),
            'METADATANOTFOUND' => \SimpleSAML\Locale\Translate::noop('{error:title_METADATANOTFOUND}'),
            'NOACCESS' => \SimpleSAML\Locale\Translate::noop('{error:title_NOACCESS}'),
            'NOCERT' => \SimpleSAML\Locale\Translate::noop('{error:title_NOCERT}'),
            'NORELAYSTATE' => \SimpleSAML\Locale\Translate::noop('{error:title_NORELAYSTATE}'),
            'NOSTATE' => \SimpleSAML\Locale\Translate::noop('{error:title_NOSTATE}'),
            'NOTFOUND' => \SimpleSAML\Locale\Translate::noop('{error:title_NOTFOUND}'),
            'NOTFOUNDREASON' => \SimpleSAML\Locale\Translate::noop('{error:title_NOTFOUNDREASON}'),
            'NOTSET' => \SimpleSAML\Locale\Translate::noop('{error:title_NOTSET}'),
            'NOTVALIDCERT' => \SimpleSAML\Locale\Translate::noop('{error:title_NOTVALIDCERT}'),
            'PROCESSASSERTION' => \SimpleSAML\Locale\Translate::noop('{error:title_PROCESSASSERTION}'),
            'PROCESSAUTHNREQUEST' => \SimpleSAML\Locale\Translate::noop('{error:title_PROCESSAUTHNREQUEST}'),
            'RESPONSESTATUSNOSUCCESS' => \SimpleSAML\Locale\Translate::noop('{error:title_RESPONSESTATUSNOSUCCESS}'),
            'SLOSERVICEPARAMS' => \SimpleSAML\Locale\Translate::noop('{error:title_SLOSERVICEPARAMS}'),
            'SSOPARAMS' => \SimpleSAML\Locale\Translate::noop('{error:title_SSOPARAMS}'),
            'UNHANDLEDEXCEPTION' => \SimpleSAML\Locale\Translate::noop('{error:title_UNHANDLEDEXCEPTION}'),
            'UNKNOWNCERT' => \SimpleSAML\Locale\Translate::noop('{error:title_UNKNOWNCERT}'),
            'USERABORTED' => \SimpleSAML\Locale\Translate::noop('{error:title_USERABORTED}'),
            'WRONGUSERPASS' => \SimpleSAML\Locale\Translate::noop('{error:title_WRONGUSERPASS}'),
        );
    }


    /**
     * Fetch all translation strings for error code titles.
     *
     * Extend this to add error codes.
     *
     * @return array A map from error code to error code title
     */
    public static function getAllErrorCodeTitles()
    {
        return self::defaultGetAllErrorCodeTitles();
    }


    /**
     * Fetch all default translation strings for error code descriptions.
     *
     * @return string A map from error code to error code description
     */
    final public static function defaultGetAllErrorCodeDescriptions()
    {
        return array(
            'ACSPARAMS' => \SimpleSAML\Locale\Translate::noop('{error:descr_ACSPARAMS}'),
            'ARSPARAMS' => \SimpleSAML\Locale\Translate::noop('{error:descr_ARSPARAMS}'),
            'AUTHSOURCEERROR' => \SimpleSAML\Locale\Translate::noop('{error:descr_AUTHSOURCEERROR}'),
            'BADREQUEST' => \SimpleSAML\Locale\Translate::noop('{error:descr_BADREQUEST}'),
            'CASERROR' => \SimpleSAML\Locale\Translate::noop('{error:descr_CASERROR}'),
            'CONFIG' => \SimpleSAML\Locale\Translate::noop('{error:descr_CONFIG}'),
            'CREATEREQUEST' => \SimpleSAML\Locale\Translate::noop('{error:descr_CREATEREQUEST}'),
            'DISCOPARAMS' => \SimpleSAML\Locale\Translate::noop('{error:descr_DISCOPARAMS}'),
            'GENERATEAUTHNRESPONSE' => \SimpleSAML\Locale\Translate::noop('{error:descr_GENERATEAUTHNRESPONSE}'),
            'INVALIDCERT' => \SimpleSAML\Locale\Translate::noop('{error:descr_INVALIDCERT}'),
            'LDAPERROR' => \SimpleSAML\Locale\Translate::noop('{error:descr_LDAPERROR}'),
            'LOGOUTINFOLOST' => \SimpleSAML\Locale\Translate::noop('{error:descr_LOGOUTINFOLOST}'),
            'LOGOUTREQUEST' => \SimpleSAML\Locale\Translate::noop('{error:descr_LOGOUTREQUEST}'),
            'MEMCACHEDOWN' => \SimpleSAML\Locale\Translate::noop('{error:descr_MEMCACHEDOWN}'),
            'METADATA' => \SimpleSAML\Locale\Translate::noop('{error:descr_METADATA}'),
            'METADATANOTFOUND' => \SimpleSAML\Locale\Translate::noop('{error:descr_METADATANOTFOUND}'),
            'NOACCESS' => \SimpleSAML\Locale\Translate::noop('{error:descr_NOACCESS}'),
            'NOCERT' => \SimpleSAML\Locale\Translate::noop('{error:descr_NOCERT}'),
            'NORELAYSTATE' => \SimpleSAML\Locale\Translate::noop('{error:descr_NORELAYSTATE}'),
            'NOSTATE' => \SimpleSAML\Locale\Translate::noop('{error:descr_NOSTATE}'),
            'NOTFOUND' => \SimpleSAML\Locale\Translate::noop('{error:descr_NOTFOUND}'),
            'NOTFOUNDREASON' => \SimpleSAML\Locale\Translate::noop('{error:descr_NOTFOUNDREASON}'),
            'NOTSET' => \SimpleSAML\Locale\Translate::noop('{error:descr_NOTSET}'),
            'NOTVALIDCERT' => \SimpleSAML\Locale\Translate::noop('{error:descr_NOTVALIDCERT}'),
            'PROCESSASSERTION' => \SimpleSAML\Locale\Translate::noop('{error:descr_PROCESSASSERTION}'),
            'PROCESSAUTHNREQUEST' => \SimpleSAML\Locale\Translate::noop('{error:descr_PROCESSAUTHNREQUEST}'),
            'RESPONSESTATUSNOSUCCESS' => \SimpleSAML\Locale\Translate::noop('{error:descr_RESPONSESTATUSNOSUCCESS}'),
            'SLOSERVICEPARAMS' => \SimpleSAML\Locale\Translate::noop('{error:descr_SLOSERVICEPARAMS}'),
            'SSOPARAMS' => \SimpleSAML\Locale\Translate::noop('{error:descr_SSOPARAMS}'),
            'UNHANDLEDEXCEPTION' => \SimpleSAML\Locale\Translate::noop('{error:descr_UNHANDLEDEXCEPTION}'),
            'UNKNOWNCERT' => \SimpleSAML\Locale\Translate::noop('{error:descr_UNKNOWNCERT}'),
            'USERABORTED' => \SimpleSAML\Locale\Translate::noop('{error:descr_USERABORTED}'),
            'WRONGUSERPASS' => \SimpleSAML\Locale\Translate::noop('{error:descr_WRONGUSERPASS}'),
        );
    }

    /**
     * Fetch all translation strings for error code descriptions.
     *
     * Extend this to add error codes.
     *
     * @return string A map from error code to error code description
     */
    public static function getAllErrorCodeDescriptions()
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
    public static function getAllErrorCodeMessages()
    {
        return array(
            'title' => self::getAllErrorCodeTitles(),
            'descr' => self::getAllErrorCodeDescriptions(),
        );
    }


    /**
     * Fetch a translation string for a title for a given error code.
     *
     * @param string $errorCode The error code to look up
     *
     * @return string A string to translate
     */
    public static function getErrorCodeTitle($errorCode)
    {
        $errorCodeTitles = self::getAllErrorCodeTitles();
        return $errorCodeTitles[$errorCode];
    }


    /**
     * Fetch a translation string for a description for a given error code.
     *
     * @param string $errorCode The error code to look up
     *
     * @return string A string to translate
     */
    public static function getErrorCodeDescription($errorCode)
    {
        $errorCodeDescriptions = self::getAllErrorCodeDescriptions();
        return $errorCodeDescriptions[$errorCode];
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
    public static function getErrorCodeMessage($errorCode)
    {
        return array(
            'title' => self::getErrorCodeTitle($errorCode),
            'descr' => self::getErrorCodeDescription($errorCode),
        );
    }
}
