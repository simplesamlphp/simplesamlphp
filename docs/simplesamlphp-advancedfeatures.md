# SimpleSAMLphp Advanced Features

[TOC]

## SimpleSAMLphp documentation

This document is part of the SimpleSAMLphp documentation suite.

- [List of all SimpleSAMLphp documentation](http://simplesamlphp.org/docs)

This document assumes that you already have a installation of
SimpleSAMLphp running, configured and working. This is the next
step :)

## Bridging between protocols

A bridge between two protocols is built using both an IdP and an SP, connected together.
To let a SAML 2.0 SP talk to a SAML 1.1 IdP, you build a SimpleSAMLphp bridge from a SAML 2.0 IdP and a SAML 1.1 SP.
The SAML 2.0 SP talks to the SAML 2.0 IdP, which hands the request over to the SAML 1.1 SP, which forwards it to the SAML 1.1 IdP.

If you have followed the instructions for setting up an SP, and have configured an authentication source, all you need to do is to add that authentication source to the IdP.

### Example of bridge configuration

In `metadata/saml20-idp-hosted.php`:

```php
'auth' => 'default-sp',
```

In `config/authsources.php`:

```php
'default-sp' => [
    'saml:SP',
],
```

## Attribute control

Filtering, mapping, etc can be performed by using existing or create new *Authentication Processing Filters*. For more information, read:

- [Authentication Processing Filters in SimpleSAMLphp](simplesamlphp-authproc)

## Automatic update of SAML 2.0 Metadata XML from HTTPS

The `metarefresh` module is the preferred method for doing this.
Please see the [metarefresh documentation](/docs/contrib_modules/metarefresh/simplesamlphp-automated_metadata).

## Using simpleSAMLphp on a web server requiring the use of a web proxy

Some modules in simpleSAMLphp may require fetching HTTP/HTTPS content from external websites (e.g. the metarefresh module needs to fetch the metadata from an external source).

simpleSAMLphp can be configured to send HTTP/S requests via such a proxy. The proxy can be configured in the config/config.php option "proxy". Should the proxy require authentication, this can be configured with "proxy.auth".

The default is not to use a proxy ('proxy' = null) and no username and password are used ('proxy.auth' = false).

## Metadata signing

SimpleSAMLphp supports signing of the metadata it generates.

Metadata signing is configured by four options:

- `metadata.sign.enable`: Whether metadata signing should be enabled or not. Set to `TRUE` to enable metadata signing. Defaults to `FALSE`.
- `metadata.sign.privatekey`: Location of the private key data which should be used to sign the metadata.
- `metadata.sign.privatekey_pass`: Passphrase which should be used to open the private key. This parameter is optional, and should be left out if the private key is unencrypted.
- `metadata.sign.certificate`: Location of certificate data which matches the private key.
- `metadata.sign.algorithm`: The algorithm to use when signing metadata for this entity. Defaults to RSA-SHA256. Possible values:
  - `http://www.w3.org/2000/09/xmldsig#rsa-sha1`
    *Note*: the use of SHA1 is **deprecated** and will be disallowed in the future.
  - `http://www.w3.org/2001/04/xmldsig-more#rsa-sha256`
    The default.
  - `http://www.w3.org/2001/04/xmldsig-more#rsa-sha384`
  - `http://www.w3.org/2001/04/xmldsig-more#rsa-sha512`

These options can be configured globally in the
`config/config.php`-file, or per SP/IdP by adding them to the hosted
metadata for the SP/IdP. The configuration in the metadata for the
SP/IdP takes precedence over the global configuration. Note that if
wish to set the metadata.sign.privatekey and metadata.sign.certificate
in a metadata file you need to also set metadata.sign.enable=true in
that metadata file.

## Session checking function

Optional session checking function, called on session init and loading, defined with 'session.check_function' in config.php.

A simple example that will logout a specific user and also prevent
them from logging in. The code should be placed in in a file
`src/SimpleSAML/CustomCode.php` in the main repository.

```php
    'session.check_function' => ['\SimpleSAML\CustomCode', 'checkSession'],
```

In the `src/SimpleSAML/CustomCode.php` file we check for a specific
`uid` who we know is a bad boy in a known auth source and stop them
from doing anything.

```php
declare(strict_types=1);

namespace SimpleSAML;

class CustomCode
{
    /**
     * The session.check_function can be used to throw away a session object
     * during normal processing. If we throw it away by returning `false` then
     * the user will be forced to create a new session.
     * 
     * There are two call modes: during session init which can not fail and 
     * during testing. When testing returning false will cause the session to 
     * be discarded.
     *
     * @param \SimpleSAML\Session $session The session to approve/reject
     * @param bool $init true if called during session init.
     */
    public static function checkSession(\SimpleSAML\Session $session, bool $init = false)
    {
        $authority = "default-sp";
        
        if( $init ) {
            // init can not fail
            // return value is ignored
            return true;
        }
        
        $ad = $session->getAuthData($authority,"Attributes");
        if( !$ad ) {
            return true;
        }
        $uid = $ad["uid"];
        
        if( in_array("badboy@localhost.localdomain",$uid)) {
            // drop the session
            return false;
        }

        // normal functionality
        return true;
    }
};

```

A more complex example which performs a GeoIP country check on the
session to make sure the user is in the same country as they were when
they authenticated.


```php
public static function checkSession(\SimpleSAML\Session $session, bool $init = false)
{
    $data_type = 'example:check_session';
    $data_key = 'remote_addr';

    $remote_addr = strval($_SERVER['REMOTE_ADDR']);

    if ($init) {
        $session->setData(
            $data_type,
            $data_key,
            $remote_addr,
            \SimpleSAML\Session::DATA_TIMEOUT_SESSION_END
        );
        return;
    }

    if (!function_exists('geoip_country_code_by_name')) {
        \SimpleSAML\Logger::warning('geoip php module required.');
        return true;
    }

    $stored_remote_addr = $session->getData($data_type, $data_key);
    if ($stored_remote_addr === null) {
        \SimpleSAML\Logger::warning('Stored data not found.');
        return false;
    }

    $country_a = geoip_country_code_by_name($remote_addr);
    $country_b = geoip_country_code_by_name($stored_remote_addr);

    if ($country_a === $country_b) {
        if ($stored_remote_addr !== $remote_addr) {
            $session->setData(
                $data_type,
                $data_key,
                $remote_addr,
                \SimpleSAML\Session::DATA_TIMEOUT_SESSION_END
            );
        }

        return true;
    }

    return false;
}
```



## Support

If you need help to make this work, or want to discuss
SimpleSAMLphp with other users of the software, you are fortunate:
Around SimpleSAMLphp there is a great Open source community, and
you are welcome to join! The forums are open for you to ask
questions, contribute answers other further questions, request
improvements or contribute with code or plugins of your own.

- [SimpleSAMLphp homepage](https://simplesamlphp.org)
- [List of all available SimpleSAMLphp documentation](https://simplesamlphp.org/docs/)
- [Join the SimpleSAMLphp user's mailing list](https://simplesamlphp.org/lists)
