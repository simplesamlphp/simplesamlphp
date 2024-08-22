# IdP hosted metadata reference

[TOC]

This is a reference for the metadata file `metadata/saml20-idp-hosted.php`.
The file has the following format:

```php
<?php
/* The index of the array is the entity ID of this IdP. */
$metadata['entity-id-1'] = [
    'host' => 'idp.example.org',
    /* Configuration options for the first IdP. */
];
$metadata['entity-id-2'] = [
    'host' => '__DEFAULT__',
    /* Configuration options for the default IdP. */
];
/* ... */
```

The entity ID must be a URI, that is unlikely to change for technical or
political reasons. We recommend it to be a domain name you own.
The URL does not have to resolve to actual content, it's
just an identifier. If your organization's domain is `example.org`:

`https://example.org/saml-idp`

For guidance in picking an entityID, see
[InCommon's best practice](https://spaces.at.internet2.edu/display/federation/saml-metadata-entityid)
on the matter.

The `host` option is the hostname of the IdP, and will be used to
select the correct configuration. One entry in the metadata-list can
have the host `__DEFAULT__`. This entry will be used when no other
entry matches.

## Common options

`auth`
:   Which authentication module should be used to authenticate users on
    this IdP.

`authproc`
:   Used to manipulate attributes, and limit access for each SP. See
    the [authentication processing filter manual](simplesamlphp-authproc).

`certificate`
:   Location of certificate data which should be used by this IdP, in PEM format.

`contacts`
:   Specify contacts in addition to the technical contact configured through config/config.php.
    For example, specifying a support contact:

```php
'contacts' => [
    [
        'ContactType'       => 'support',
        'EmailAddress'      => 'support@example.org',
        'GivenName'         => 'John',
        'surName'           => 'Doe',
        'TelephoneNumber'   => '+31(0)12345678',
        'Company'           => 'Example Inc.',
    ],
],
```

:   If you have support for a trust framework that requires extra attributes on the contact person element in your IdP metadata (for example, SIRTFI), you can specify an array of attributes on a contact.

```php
'contacts' => [
    [
        'ContactType'       => 'other',
        'EmailAddress'      => 'mailto:abuse@example.org',
        'GivenName'         => 'John',
        'SurName'           => 'Doe',
        'TelephoneNumber'   => '+31(0)12345678',
        'Company'           => 'Example Inc.',
        'attributes' => [
            [
                'namespaceURI' => 'http://refeds.org/metadata',
                'namespacePrefix' => 'remd',
                'attrName' => 'contactType',
                'attrValue' => 'http://refeds.org/metadata/contactType/security',
            ],
        ],
    ],
],
```

`errorURL`
:   Overrides the errorURL in the IDP's published metadata.

`host`
:   The hostname for this IdP. One IdP can also have the `host`-option
    set to `__DEFAULT__`, and that IdP will be used when no other
    entries in the metadata matches.

`logouttype`
:   The logout handler to use. Either `iframe` or `traditional`. `traditional` is the default.

`OrganizationName`, `OrganizationDisplayName`, `OrganizationURL`
:   The name and URL of the organization responsible for this IdP.
    You need to either specify *all three* or none of these options.

:   The Name does not need to be suitable for display to end users, the DisplayName should be.
    The URL is a website the user can access for more information about the organization.

:   This option can be translated into multiple languages by specifying the value as an array of language-code to translated name:

```php
'OrganizationName' => [
    'en' => 'Voorbeeld Organisatie Foundation b.a.',
    'nl' => 'Stichting Voorbeeld Organisatie b.a.',
],
'OrganizationDisplayName' => [
    'en' => 'Example organization',
    'nl' => 'Voorbeeldorganisatie',
],
'OrganizationURL' => [
    'en' => 'https://example.com',
    'nl' => 'https://example.com/nl',
],
```

`privatekey`
:   Location of private key data for this IdP, in PEM format.

`privatekey_pass`
:   Passphrase for the private key. Leave this option out if the
    private key is unencrypted.

`scope`
:   An array with scopes for this IdP.
    The scopes will be added to the generated XML metadata.
    A scope can either be a domain name or a regular expression
    matching a number of domains.

## SAML 2.0 options

The following SAML 2.0 options are available:

`assertion.encryption`
:   Whether assertions sent from this IdP should be encrypted. The default
    value is `FALSE`. When set to `TRUE` encryption will be enforced for all
    remote SP's and an exception is thrown if encryption fails.

:   Note that this option can be set for each SP in the SP-remote metadata.

:   Note that enforcement can be disabled by setting `encryption.optional` to `TRUE`.

`attributeencodings`
:   What encoding should be used for the different attributes. This is
    an array which maps attribute names to attribute encodings. There
    are three different encodings:

:   -   `string`: Will include the attribute as a normal string. This is
        the default.

:   -   `base64`: Store the attribute as a base64 encoded string.

:   -   `raw`: Store the attribute without any modifications. This
        makes it possible to include raw XML in the response.

:   Note that this option also exists in the SP-remote metadata, and
    any value in the SP-remote metadata overrides the one configured
    in the IdP metadata.

`attributes.NameFormat`
:   What value will be set in the Format field of attribute
    statements. This parameter can be configured multiple places, and
    the actual value used is fetched from metadata by the following
    priority:

:   1.  SP Remote Metadata
:   2.  IdP Hosted Metadata

:   The default value is:
    `urn:oasis:names:tc:SAML:2.0:attrname-format:uri`

:   Some examples of values specified in the SAML 2.0 Core
    Specification:

:   -   `urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified`
:   -   `urn:oasis:names:tc:SAML:2.0:attrname-format:uri` (The default
        in Shibboleth 2.0, mandatory as per SAML2INT)
:   -   `urn:oasis:names:tc:SAML:2.0:attrname-format:basic` (The
        default in Sun Access Manager)

:   You can also define your own value.

:   Note that this option also exists in the SP-remote metadata, and
    any value in the SP-remote metadata overrides the one configured
    in the IdP metadata.

`encryption.optional`
:   Whether or not we may continue to send an unencrypted assertion if the SP has no encryption certificate.
    The default value is `FALSE`.

`encryption.blacklisted-algorithms`
:   Blacklisted encryption algorithms. This is an array containing the algorithm identifiers.

:   Note that this option can be set for each SP in the [SP-remote metadata](./simplesamlphp-reference-sp-remote).

:   The RSA encryption algorithm with PKCS#1 v1.5 padding is blacklisted by default for security reasons. Any assertions
    encrypted with this algorithm will therefore fail to decrypt. You can override this limitation by defining an empty
    array in this option (or blacklisting any other algorithms not including that one). However, it is strongly
    discouraged to do so. For your own safety, please include the string 'http://www.w3.org/2001/04/xmlenc#rsa-1_5' if
    you make use of this option.

`https.certificate`
:   The certificate used by the webserver when handling connections.
    This certificate will be added to the generated metadata of the IdP,
    which is required by some SPs when using the HTTP-Artifact binding.

`nameid.encryption`
:   Whether NameIDs sent from this IdP should be encrypted. The default
    value is `FALSE`.

:   Note that this option can be set for each SP in the [SP-remote metadata](./simplesamlphp-reference-sp-remote).

`NameIDFormat`
:   The format(s) of the NameID supported by this IdP, as either an array or a string. If an array is given, the first
    value is used as the default if the incoming request does not specify a preference. Defaults to the `transient`
    format if unspecified.

:   This parameter can be configured in multiple places, and the actual value used is fetched from metadata with
    the following priority:

:   1.  SP Remote Metadata
:   2.  IdP Hosted Metadata

:   The three most commonly used values are:

:   1.  `urn:oasis:names:tc:SAML:2.0:nameid-format:transient`
:   2.  `urn:oasis:names:tc:SAML:2.0:nameid-format:persistent`
:   3.  `urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress`

:   The `transient` format will generate a new unique ID every time
    the user logs in.

:   To properly support the `persistent` and `emailAddress` formats,
    you should configure [NameID generation filters](./saml:nameid)
    on your IdP.

:   Note that the value(s) set here will be added to the metadata generated for this IdP,
    in the `NameIDFormat` element.

`RegistrationInfo`
:   Allows to specify information about the registrar of this SP. Please refer to the
    [MDRPI extension](./simplesamlphp-metadata-extensions-rpi) document for further information.

`saml20.ecp`
:   Set to `true` to enable the IdP to receive AuthnRequests and send responses according the Enhanced Client or Proxy (ECP) Profile. Note: authentication filters that require interaction with the user will not work with ECP.
    Defaults to `false`.

`saml20.hok.assertion`
:   Set to `TRUE` to enable the IdP to send responses according the [Holder-of-Key Web Browser SSO Profile](./simplesamlphp-hok-idp).
    Defaults to `FALSE`.

`saml20.sendartifact`
:   Set to `TRUE` to enable the IdP to send responses with the HTTP-Artifact binding.
    Defaults to `FALSE`.

:   Note that this requires a configured memcache server.

`saml20.sign.assertion`
:   Whether `<saml:Assertion>` elements should be signed.
    Defaults to `TRUE`.

:   Note that this option also exists in the SP-remote metadata, and
    any value in the SP-remote metadata overrides the one configured
    in the IdP metadata.

`saml20.sign.response`
:   Whether `<samlp:Response>` messages should be signed.
    Defaults to `TRUE`.

:   Note that this option also exists in the SP-remote metadata, and
    any value in the SP-remote metadata overrides the one configured
    in the IdP metadata.

`signature.algorithm`
:   The algorithm to use when signing any message generated by this identity provider. Defaults to RSA-SHA256.
:   Possible values:

* `http://www.w3.org/2000/09/xmldsig#rsa-sha1`
  *Note*: the use of SHA1 is **deprecated** and will be disallowed in the future.
* `http://www.w3.org/2001/04/xmldsig-more#rsa-sha256`
  The default.
* `http://www.w3.org/2001/04/xmldsig-more#rsa-sha384`
* `http://www.w3.org/2001/04/xmldsig-more#rsa-sha512`

`sign.logout`
:   Whether to sign logout messages sent from this IdP.

:   Note that this option also exists in the SP-remote metadata, and
    any value in the SP-remote metadata overrides the one configured
    in the IdP metadata.

`SingleSignOnService`
:   Override the default URL for the SingleSignOnService for this
    IdP. This is an absolute URL. The default value is
    `<SimpleSAMLphp-root>/module.php/saml/idp/singleSignOnService`

:   Note that this only changes the values in the generated
    metadata and in the messages sent to others. You must also
    configure your webserver to deliver this URL to the correct PHP
    page.

`SingleSignOnServiceBinding`
:   List of SingleSignOnService bindings that the IdP will claim support for.
:   Possible values:

* `urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect`
* `urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST`

:   Defaults to HTTP-Redirect binding. Please note that the order
    specified will be kept in the metadata, making the first binding
    the default one.

`SingleLogoutService`
:   Override the default URL for the SingleLogoutService for this
    IdP. This is an absolute URL. The default value is
    `<SimpleSAMLphp-root>/module.php/saml/idp/singleLogout`

:   Note that this only changes the values in the generated
    metadata and in the messages sent to others. You must also
    configure your webserver to deliver this URL to the correct PHP
    page.

`SingleLogoutServiceBinding`
:   List of SingleLogoutService bindings the IdP will claim support for.
:   Possible values:

* `urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect`
* `urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST`

:   Defaults to HTTP-Redirect binding. Please note that the order
    specified will be kept in the metadata, making the first binding
    the default one.

`validate.authnrequest`
:   Whether we require signatures on authentication requests sent to this IdP.
    Set it to:

:   true: authnrequest must be signed (and signature will be validated)
:   null: authnrequest may be signed, if it is, signature will be validated
:   false: authnrequest signature is never checked

:   Note that this option also exists in the SP-remote metadata, and
    any value in the SP-remote metadata overrides the one configured
    in the IdP metadata.

`validate.logout`
:   Whether we require signatures on logout messages sent to this IdP.

:   Note that this option also exists in the SP-remote metadata, and
    any value in the SP-remote metadata overrides the one configured
    in the IdP metadata.

### Fields for signing and validating messages

SimpleSAMLphp only signs authentication responses by default.
Signing of logout requests and logout responses can be enabled by
setting the `redirect.sign` option. Validation of received messages
can be enabled by the `redirect.validate` option.

These options set the default for this IdP, but options for each SP
can be set in `saml20-sp-remote`. Note that you need to add a
certificate for each SP to be able to validate signatures on
messages from that SP.

`redirect.sign`
:   Whether logout requests and logout responses sent from this IdP
    should be signed. The default is `FALSE`.

`redirect.validate`
:   Whether authentication requests, logout requests and logout
    responses received sent from this IdP should be validated. The
    default is `FALSE`

**Example: Configuration for signed messages**:

```php
'redirect.sign' => true,
```

## Metadata extensions

SimpleSAMLphp supports generating metadata with the MDUI, MDRPI and EntityAttributes metadata extensions.
See the documentation for those extensions for more details:

* [MDUI extension](./simplesamlphp-metadata-extensions-ui)
* [MDRPI extension](./simplesamlphp-metadata-extensions-rpi)
* [EntityAttributes](./simplesamlphp-metadata-extensions-attributes)

For other metadata extensions, you can use the `saml:Extensions` option:

`saml:Extensions`
:   An array of `\SAML2\XML\Chunk`s to include in the IdP metadata extensions, at the same level as `EntityAttributes`.

`Examples`:

These are some examples of IdP metadata

### Minimal SAML 2.0 IdP

```php
<?php

$metadata['https://example.org/saml-idp'] = [
    /*
     * We use '__DEFAULT__' as the hostname so we won't have to
     * enter a hostname.
     */
    'host' => '__DEFAULT__',

    /* The private key and certificate used by this IdP. */
    'certificate' => 'example.org.crt',
    'privatekey' => 'example.org.pem',

    /*
     * The authentication source for this IdP. Must be one
     * from config/authsources.php.
     */
    'auth' => 'example-userpass',
];
```

### A custom metadata extension (eduGAIN republish request)

```php
<?php

$dom = \SAML2\DOMDocumentFactory::create();
$republishRequest = $dom->createElementNS('http://eduid.cz/schema/metadata/1.0', 'eduidmd:RepublishRequest');
$republishTarget = $dom->createElementNS('http://eduid.cz/schema/metadata/1.0', 'eduidmd:RepublishTarget', 'http://edugain.org/');
$republishRequest->appendChild($republishTarget);
$ext = [new \SAML2\XML\Chunk($republishRequest)];

$metadata['https://example.org/saml-idp'] = [
    'host' => '__DEFAULT__',
    'certificate' => 'example.org.crt',
    'privatekey' => 'example.org.pem',
    'auth' => 'example-userpass',

    /*
     * The custom metadata extensions.
     */
    'saml:Extensions' => $ext,
];
```

this generates the following metadata:

```xml
<EntityDescriptor entityID="...">
  <Extensions xmlns="urn:oasis:names:tc:SAML:2.0:metadata">
    <eduidmd:RepublishRequest xmlns:eduidmd="http://eduid.cz/schema/metadata/1.0">
      <eduidmd:RepublishTarget>http://edugain.org/</eduidmd:RepublishTarget>
    </eduidmd:RepublishRequest>
  </Extensions>
  <!-- rest of metadata -->
</EntityDescriptor>
```
