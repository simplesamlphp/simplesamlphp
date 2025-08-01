# IdP remote metadata reference

[TOC]

This is a reference for metadata options available for `metadata/saml20-idp-remote.php`. The file has the following format:

```php
<?php
/* The index of the array is the entity ID of this IdP. */
$metadata['entity-id-1'] = [
    /* Configuration options for the first IdP. */
];
$metadata['entity-id-2'] = [
    /* Configuration options for the second IdP. */
];
/* ... */
```

## Options

`AuthnContextClassRef`
:   The AuthnContextClassRef that will be sent in the login request.

:   Note that this option also exists in the SP configuration. This
    entry in the IdP-remote metadata overrides the option in the
    [SP configuration](./saml:sp).

`AuthnContextComparison`

:   The Comparison attribute of the AuthnContext that will be sent in the login request. This parameter won't be used unless AuthnContextClassRef is set and contains one or more values. Possible values:

* SAML2\Constants::COMPARISON_EXACT (default)
* SAML2\Constants::COMPARISON_BETTER
* SAML2\Constants::COMPARISON_MINIMUM
* SAML2\Constants::COMPARISON_MAXIMUM

:   Note that this option also exists in the SP configuration. This
    entry in the IdP-remote metadata overrides the option in the
    [SP configuration](./saml:sp).

`authproc`
:   Used to manipulate attributes, and limit access for each IdP. See the [authentication processing filter manual](simplesamlphp-authproc).

`base64attributes`
:   Whether attributes received from this IdP should be base64 decoded. The default is `FALSE`.

`certData`
:   The base64 encoded certificate for this IdP. This is an alternative to storing the certificate in a file on disk and specifying the filename in the `certificate`-option.

`certificate`
:   Location of certificate data for this IdP.

`description`
:   A description of this IdP. Will be used by various modules when they need to show a description of the IdP to the user.

:   This option can be translated into multiple languages in the same way as the `name`-option.

`disable_scoping`
:   Whether sending of samlp:Scoping elements in authentication requests should be suppressed. The default value is `FALSE`.
    When set to `TRUE`, no scoping elements will be sent. This does not comply with the SAML2 specification, but allows
    interoperability with ADFS which [does not support Scoping elements](https://docs.microsoft.com/en-za/azure/active-directory/develop/active-directory-single-sign-on-protocol-reference#scoping).

:   Note that this option also exists in the SP configuration. This
    entry in the IdP-remote metadata overrides the option in the
    [SP configuration](./saml:sp).

`encryption.blacklisted-algorithms`
:   Blacklisted encryption algorithms. This is an array containing the algorithm identifiers.

:   Note that this option also exists in the SP configuration. This
    entry in the IdP-remote metadata overrides the option in the
    [SP configuration](./saml:sp).

:   The RSA encryption algorithm with PKCS#1 v1.5 padding is blacklisted by default for security reasons. Any assertions
    encrypted with this algorithm will therefore fail to decrypt. You can override this limitation by defining an empty
    array in this option (or blacklisting any other algorithms not including that one). However, it is strongly
    discouraged to do so. For your own safety, please include the string `http://www.w3.org/2001/04/xmlenc#rsa-1_5` if
    you make use of this option.

`hide.from.discovery`
:   Whether to hide hide this IdP from the local discovery or not. Set to true to hide it. Defaults to false.

`icon`
:   A logo which will be shown next to this IdP in the discovery service.

`IDPList`
:   The IdP is allowed to respond to an `AuthNRequest` originally sent to entityIDs in this list.

`name`
:   The name of this IdP. Will be used by various modules when they need to show a name of the SP to the user.

:   If this option is unset, the organization name will be used instead (if it is available).

:   This option can be translated into multiple languages by specifying the value as an array of language-code to translated name:

```php
'name' => [
    'en' => 'A service',
    'no' => 'En tjeneste',
],
```

`nameid.encryption`
:   Whether NameIDs sent to this IdP should be encrypted. The default
    value is `FALSE`.

:   Note that this option also exists in the SP configuration. This
    entry in the IdP-remote metadata overrides the option in the
    [SP configuration](./saml:sp).

`NameIDPolicy`
:   The format of the NameID we request from this IdP: an array in the form of
    `[ 'Format' => the format, 'AllowCreate' => true or false ]`.
    Set to an empty array `[]` to omit sending any specific NameIDPolicy element
    in the AuthnRequest. When the entire option or either array key is unset,
    the defaults are transient and true respectively.

`OrganizationName`
:   The name of the organization responsible for this SPP.
    This name does not need to be suitable for display to end users.

:   This option can be translated into multiple languages by specifying the value as an array of language-code to translated name:

```php
'OrganizationName' => [
    'en' => 'Example organization',
    'no' => 'Eksempel organisation',
],
```

:   *Note*: If you specify this option, you must also specify the `OrganizationURL` option.

`OrganizationDisplayName`
:   The name of the organization responsible for this IdP.
    This name must be suitable for display to end users.
    If this option isn't specified, `OrganizationName` will be used instead.

:   This option can be translated into multiple languages by specifying the value as an array of language-code to translated name.

:   *Note*: If you specify this option, you must also specify the `OrganizationName` option.

`OrganizationURL`
:   A URL the end user can access for more information about the organization.

:   This option can be translated into multiple languages by specifying the value as an array of language-code to translated URL.

:   *Note*: If you specify this option, you must also specify the `OrganizationName` option.

`scope`
:   An array with scopes valid for this IdP.
    The IdP will send scopes in scoped attributes, that is, attributes containing a value with an `@` sign and a domain name
    after it.

:   When the `saml:FilterScopes` authentication processing filter is used, this list of scopes will determine the valid
    scopes for attributes.

`signature.algorithm`
:   The algorithm to use when signing any message sent to this specific identity provider. Defaults to RSA-SHA256.
:   Note that this option also exists in the SP configuration.
    This value in the IdP remote metadata overrides the value in the SP configuration.
:   Possible values:

* `http://www.w3.org/2000/09/xmldsig#rsa-sha1`
  *Note*: the use of SHA1 is **deprecated** and will be disallowed in the future.
* `http://www.w3.org/2001/04/xmldsig-more#rsa-sha256`
  The default.
* `http://www.w3.org/2001/04/xmldsig-more#rsa-sha384`
* `http://www.w3.org/2001/04/xmldsig-more#rsa-sha512`

`sign.authnrequest`
:   Whether to sign authentication requests sent to this IdP.

:   Note that this option also exists in the SP configuration.
    This value in the IdP remote metadata overrides the value in the SP configuration.

`sign.logout`
:   Whether to sign logout messages sent to this IdP.

:   Note that this option also exists in the SP configuration.
    This value in the IdP remote metadata overrides the value in the SP configuration.

`SingleLogoutService`
:   Endpoint URL for logout requests and responses. You should obtain this from the IdP. Users who log out from your service is redirected to this URL with the LogoutRequest using HTTP-REDIRECT.

:   The value of this option is specified in the format shown in [endpoint format](./simplesamlphp-metadata-endpoints).

`SingleLogoutServiceResponse`
:   Endpoint URL for logout responses. Overrides the `SingleLogoutService`-option for responses.

`SingleSignOnService`
:   Endpoint URL for sign on. You should obtain this from the IdP. For SAML 2.0, SimpleSAMLphp will use the HTTP-Redirect binding when contacting this endpoint.

:   The value of this option is specified in the format shown in [endpoint format](./simplesamlphp-metadata-endpoints).

`SPNameQualifier`
:   This corresponds to the SPNameQualifier in the SAML 2.0 specification. It allows to give subjects a SP specific namespace. This option is rarely used, so if you don't need it, leave it out. When left out, SimpleSAMLphp assumes the entityID of your SP as the SPNameQualifier.

`validate.logout`
:   Whether we require signatures on logout messages sent from this IdP.

:   Note that this option also exists in the SP configuration.
    This value in the IdP remote metadata overrides the value in the SP configuration.

### Decrypting assertions

It is possible to decrypt the assertions received from an IdP. The supported algorithms are listed in `sharedkey_algorithm` below.

There are two modes of encryption supported by SimpleSAMLphp. One is symmetric encryption, in which case both the SP and the IdP needs to share a key. The other mode is the use of public key encryption. In that mode, the public key of the SP is extracted from the certificate of the SP.

`assertion.encryption`
:   Whether assertions received from this IdP must be encrypted. The default value is `FALSE`.
    If this option is set to `TRUE`, assertions from the IdP must be encrypted.
    Unencrypted assertions will be rejected.

:   Note that this option overrides the option with the same name in the SP configuration.

`sharedkey`
:   Symmetric key which should be used for decryption. This should be a 128-bit, 192-bit or 256-bit key based on the algorithm used. If this option is not specified, public key encryption will be used instead.

`sharedkey_algorithm`
:   Algorithm which should be used for decryption. Possible values are:

* `http://www.w3.org/2001/04/xmlenc#aes128-cbc`
* `http://www.w3.org/2001/04/xmlenc#aes192-cbc`
* `http://www.w3.org/2001/04/xmlenc#aes256-cbc`
* `http://www.w3.org/2009/xmlenc11#aes128-gcm`
* `http://www.w3.org/2009/xmlenc11#aes192-gcm`
* `http://www.w3.org/2009/xmlenc11#aes256-gcm`

### Fields for signing and validating messages

SimpleSAMLphp only signs authentication responses by default. Signing of authentication request, logout requests and logout responses can be enabled by setting the `redirect.sign` option. Validation of received messages can be enabled by the `redirect.validate` option.

`redirect.sign`
:   Whether authentication request, logout requests and logout responses sent to this IdP should be signed. The default is `FALSE`.

`redirect.validate`
:   Whether logout requests and logout responses received from this IdP should be validated. The default is `FALSE`.

**Example: Configuration for validating messages**:

```php
'redirect.validate' => true,
'certificate' => 'example.org.crt',
```
