# `saml:SP`

This authentication source is used to authenticate against SAML 2 IdPs.

## Metadata

The metadata for your SP will be available from the federation page on your SimpleSAMLphp installation.

SimpleSAMLphp supports generating metadata with the MDUI and MDRPI metadata extensions
and with entity attributes. See the documentation for those extensions for more details:

* [MDUI extension](../simplesamlphp-metadata-extensions-ui)
* [MDRPI extension](../simplesamlphp-metadata-extensions-rpi)
* [Attributes extension](../simplesamlphp-metadata-extensions-attributes)
* [DiscoveryResponse extension](../simplesamlphp-metadata-extensions-idpdisc)

**Parameters**:

These are parameters that can be used at runtime to control the authentication.
All these parameters override the equivalent option from the configuration.

`saml:AuthnContextClassRef`
:   The AuthnContextClassRef that will be sent in the login request.

`saml:AuthnContextComparison`
:   The Comparison attribute of the AuthnContext that will be sent in the login request.
    This parameter won't be used unless `saml:AuthnContextClassRef` is set and contains one or more values.
    Possible values:

    * `SimpleSAML\SAML2\Constants::COMPARISON_EXACT` (default)
    * `SimpleSAML\SAML2\Constants::COMPARISON_BETTER`
    * `SimpleSAML\SAML2\Constants::COMPARISON_MINIMUM`
    * `SimpleSAML\SAML2\Constants::COMPARISON_MAXIMUM`

`ForceAuthn`
:   Force authentication allows you to force re-authentication of users even if the user has a SSO session at the IdP.

`saml:idp`
:   The entity ID of the IdP we should send an authentication request to.

`isPassive`
:   Send a passive authentication request.

`IDPList`
:   List of IdP entity ids that should be sent in the AuthnRequest to the IdP in the IDPList element, part of the
    Scoping element.

`saml:Extensions`
:   The samlp:Extensions (an XML chunk) that will be sent in the login request.

`saml:logout:Extensions`
:   The samlp:Extensions (an XML chunk) that will be sent in the logout request.

`saml:NameID`
:   Add a Subject element with a NameID to the SAML AuthnRequest for the IdP.
    This must be a \SimpleSAML\SAML2\XML\saml\NameID object.

`saml:NameIDPolicy`
:   The format of the NameID we request from the IdP: an array in the form of
    `[ 'Format' => the format, 'AllowCreate' => true or false ]`.
    Set to an empty array `[]` to omit sending any specific NameIDPolicy element
    in the AuthnRequest.

`saml:Audience`
:   Add a Conditions element to the SAML AuthnRequest containing an
    AudienceRestriction with one or more audiences.

## Authentication data

Some SAML-specific attributes are available to the application after authentication.
To retrieve these attributes, the application can use the `getAuthData()`-function from the [SP API](./simplesamlphp-sp-api).
The following attributes are available:

`saml:sp:IdP`
:   The entityID of the IdP the user is authenticated against.

`saml:sp:NameID`
:   The NameID the user was issued by the IdP.
    This is a \SimpleSAML\SAML2\XML\saml\NameID object with the various fields from the NameID.

`saml:sp:SessionIndex`
:   The SessionIndex we received from the IdP.

**Options**:

`acs.Bindings`
: List of bindings the SP should support. If it is unset, all will be added.
: Possible values:

    * `urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST`
    * `urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact`
    * `urn:oasis:names:tc:SAML:2.0:profiles:holder-of-key:SSO:browser`

`assertion.encryption`
:   Whether assertions received by this SP must be encrypted. The default value is `false`.
    If this option is set to `true`, unencrypted assertions will be rejected.

:   Note that this option can be overridden for a specific IdP in saml20-idp-remote.

`AssertionConsumerService`
:   List of Assertion Consumer Services in the generated metadata. Specified in the format detailed in the
    [Metadata endpoints](../simplesamlphp-metadata-endpoints) documentation.
    Note that this list is taken at face value, so it's not useful to list
    anything here that the SP auth source does not actually support (unless the URLs point
    externally).

`AssertionConsumerServiceIndex`
:   The Assertion Consumer Service Index to be used in the AuthnRequest in place of the Assertion
    Service Consumer URL.

`attributes`
:   List of attributes this SP requests from the IdP.
    This list will be added to the generated metadata.

:   The attributes will be added without a `NameFormat` by default.
    Use the `attributes.NameFormat` option to specify the `NameFormat` for the attributes.

:   An associative array can be used, mixing both elements with and without keys. When a key is
    specified for an element of the array, it will be used as the friendly name of the attribute
    in the generated metadata.

:   *Note*: This list will only be added to the metadata if the `name`-option is also specified.

`attributes.NameFormat`
:   The `NameFormat` for the requested attributes.

`attributes.index`
:   The `index` attribute that is set in the md:AttributeConsumingService element. Integer value that defaults to `0`.

`attributes.isDefault`
:   If present, sets the `isDefault` attribute in the md:AttributeConsumingService element. Boolean value, when
    unset, the attribute will be omitted.

`attributes.required`
: If you have attributes added you can here specify which should be marked as required.
: The attributes should still be present in `attributes`.

`AuthnContextClassRef`
:   The SP can request authentication with one or more specific authentication context classses.
    One example of usage could be if the IdP supports both username/password authentication as well as software-PKI.
    Set this to a string for one class identifier or an array of requested class identifiers.

`AuthnContextComparison`
:   The Comparison attribute of the AuthnContext that will be sent in the login request.
    This parameter won't be used unless `saml:AuthnContextClassRef` is set and contains one or more values.
    Possible values:

    * `SimpleSAML\SAML2\Constants::COMPARISON_EXACT` (default)
    * `SimpleSAML\SAML2\Constants::COMPARISON_BETTER`
    * `SimpleSAML\SAML2\Constants::COMPARISON_MINIMUM`
    * `SimpleSAML\SAML2\Constants::COMPARISON_MAXIMUM`

`authproc`
:   Processing filters that should be run after SP authentication.
    See the [authentication processing filter manual](simplesamlphp-authproc).

`certData`
:   Base64 encoded certificate data. Can be used instead of the `certificate` option.

`certificate`
:   File name of certificate for this SP. This certificate will be included in generated metadata.

`contacts`
:   Specify contacts in addition to the `technical` contact configured through `config/config.php`.

:   For example, specifying a support contact:

        'contacts' => [
            [
                'contactType'       => 'support',
                'emailAddress'      => 'support@example.org',
                'givenName'         => 'John',
                'surName'           => 'Doe',
                'telephoneNumber'   => '+31(0)12345678',
                'company'           => 'Example Inc.',
            ],
        ],

:   Valid values for `contactType` are: `technical`, `support`, `administrative`, `billing` and `other`. All
    fields, except `contactType` are OPTIONAL.

`description`
:   A description of this SP.
    Will be added to the generated metadata, in an AttributeConsumingService element.

:   This option can be translated into multiple languages by specifying the value as an array of language-code to translated description:

        'description' => [
            'en' => 'A service',
            'no' => 'En tjeneste',
        ],

:   *Note*: For this to be added to the metadata, you must also specify the `attributes` and `name` options.

`disable_scoping`
:    Whether sending of samlp:Scoping elements in authentication requests should be suppressed. The default value is `false`.
     When set to `true`, no scoping elements will be sent. This does not comply with the SAML2 specification, but allows
     interoperability with ADFS which [does not support Scoping elements](https://docs.microsoft.com/en-za/azure/active-directory/develop/active-directory-single-sign-on-protocol-reference#scoping).

:   Note that this option also exists in the IdP remote configuration. An entry
    in the IdP-remote metadata overrides this the option in the SP
    configuration.

`enable_unsolicited`
:    Whether this SP is willing to process unsolicited responses. The default value is `true`.

`discoURL`
:   Set which IdP discovery service this SP should use.
    If this is unset, the IdP discovery service specified in the global option `idpdisco.url.saml20` in `config/config.php` will be used.
    If that one is also unset, the builtin default discovery service will be used.

`encryption.blacklisted-algorithms`
:   Blacklisted encryption algorithms. This is an array containing the algorithm identifiers.

:   Note that this option can be set for each IdP in the [IdP-remote metadata](../simplesamlphp-reference-idp-remote).

`entityID`
:   The entity ID this SP should use. (Must be set or an error will be generated.)

:   The entity ID must be a URI, that is unlikely to change for technical or political
    reasons. We recommend it to be a domain name, like above, if your organization's main
    domain is `example.org` and this SP is for the application `myapp`.
    The URL does not have to resolve to actual content, it's
    just an identifier. Hence you don't need to and should not change it if the actual domain
    of your application changes.

:   For guidance in picking an entityID, see
    [InCommon's best practice](https://spaces.at.internet2.edu/display/federation/saml-metadata-entityid)
    on the matter.

`ForceAuthn`
:   Force authentication allows you to force re-authentication of users even if the user has a SSO session at the IdP.

`idp`
:   The entity ID this SP should connect to.

:   If this option is unset, an IdP discovery service page will be shown.

`IsPassive`
:   IsPassive allows you to enable passive authentication by default for this SP.

`key_name`
:   The name of the certificate. It is possible the IDP requires your certificate to have a name.
    If provided, it will be exposed in the SAML 2.0 metadata as `KeyName` inside the `KeyDescriptor`. This also requires a certificate to be provided.

`name`
:   The name of this SP.
    Will be added to the generated metadata, in an AttributeConsumingService element.

:   This option can be translated into multiple languages by specifying the value as an array of language-code to translated name:

        'name' => [
            'en' => 'A service',
            'no' => 'En tjeneste',
        ],,

:   *Note*: You must also specify at least one attribute in the `attributes` option for this element to be added to the metadata.

`nameid.encryption`
:   Whether NameIDs sent from this SP should be encrypted. The default
    value is `false`.

:   Note that this option can be set for each IdP in the [IdP-remote metadata](../simplesamlphp-reference-idp-remote).

`NameIDFormat`
:   An array of the format(s) listed in the SP metadata that this SP will accept. Example:

        'NameIDFormat' => [
            \SimpleSAML\SAML2\Constants::NAMEID_PERSISTENT,
            \SimpleSAML\SAML2\Constants::NAMEID_TRANSIENT,
        ],

`NameIDPolicy`
:   The format of the NameID we request from the IdP in the AuthnRequest:
    an array in the form of
    `[ 'Format' => the format, 'AllowCreate' => true or false ]`.
    Set to an empty array `[]` to omit sending any specific NameIDPolicy element
    in the AuthnRequest. When the entire option or either array key is unset,
    the defaults are transient and true respectively.

`OrganizationName`, `OrganizationDisplayName`, `OrganizationURL`
:   The name and URL of the organization responsible for this IdP.
    You need to either specify *all three* or none of these options.

:   The Name does not need to be suitable for display to end users, the DisplayName should be.
    The URL is a website the user can access for more information about the organization.

:   This option can be translated into multiple languages by specifying the value as an array of language-code to translated name:

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

`privatekey`
:   File name of private key to be used for signing messages and decrypting messages from the IdP. This option is only required if you use encrypted assertions or if you enable signing of messages.

`privatekey_pass`
:   The passphrase for the private key, if it is encrypted. If the private key is unencrypted, this can be left out.

`ProviderName`
:   Human readable name of the local SP sent with the authentication request.

`ProtocolBinding`
:   The binding that should be used for SAML2 authentication responses.
    This option controls the binding that is requested through the AuthnRequest message to the IdP.
    By default the HTTP-Post binding is used.

`redirect.sign`
:   Whether authentication requests, logout requests and logout responses sent from this SP should be signed. The default is `false`.
    If set, the `AuthnRequestsSigned` attribute of the `SPSSODescriptor` element in SAML 2.0 metadata will contain its value. This
    option takes precedence over the `sign.authnrequest` option in any metadata generated for this SP.

`redirect.validate`
:   Whether logout requests and logout responses received by this SP should be validated. The default is `false`.

`RegistrationInfo`
:   Allows to specify information about the registrar of this SP. Please refer to the
    [MDRPI extension](../simplesamlphp-metadata-extensions-rpi) document for further information.

`RelayState`
:   The page the user should be redirected to after an IdP initiated SSO.

`RequestInitiation`
:   Enable the [Service Provider Request Initiation Protocol](https://wiki.oasis-open.org/security/RequestInitProtProf).
    To validate the `target` the `trusted.url.domains` configuration option has to be used.

`saml.SOAPClient.certificate`
:   A file with a certificate *and* private key that should be used when issuing SOAP requests from this SP.
    If this option isn't specified, the SP private key and certificate will be used.

:   This option can also be set to `false`, in which case no client certificate will be used.

`saml.SOAPClient.privatekey_pass`
:   The passphrase of the privatekey in `saml.SOAPClient.certificate`.

`saml20.hok.assertion`
:   Enable support for the SAML 2.0 Holder-of-Key SSO profile.
    See the documentation for the [Holder-of-Key profile](../simplesamlphp-hok-sp).

`sign.authnrequest`
:   Whether to sign authentication requests sent from this SP. If set, the `AuthnRequestsSigned` attribute of the
    `SPSSODescriptor` element in SAML 2.0 metadata will contain its value.

:   Note that this option also exists in the IdP-remote metadata, and
    any value in the IdP-remote metadata overrides the one configured
    in the SP configuration.

`sign.logout`
:   Whether to sign logout messages sent from this SP.

:   Note that this option also exists in the IdP-remote metadata, and
    any value in the IdP-remote metadata overrides the one configured
    in the SP configuration.

`signature.algorithm`
:   The algorithm to use when signing any message generated by this service provider. Defaults to RSA-SHA256.
:   Possible values:

    * `http://www.w3.org/2000/09/xmldsig#rsa-sha1`
       *Note*: the use of SHA1 is **deprecated** and will be disallowed in the future.
    * `http://www.w3.org/2001/04/xmldsig-more#rsa-sha256`
      The default.
    * `http://www.w3.org/2001/04/xmldsig-more#rsa-sha384`
    * `http://www.w3.org/2001/04/xmldsig-more#rsa-sha512`

`SingleLogoutServiceBinding`
:   List of SingleLogoutService bindings the SP will claim support for (can be empty).
:   Possible values:

    * `urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect`
    * `urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST`
    * `urn:oasis:names:tc:SAML:2.0:bindings:SOAP`

`SingleLogoutServiceLocation`
:   The Single Logout Service URL published in the generated metadata.

`validate.logout`
:   Whether we require signatures on logout messages sent to this SP.

:   Note that this option also exists in the IdP-remote metadata, and
    any value in the IdP-remote metadata overrides the one configured
    in the IdP metadata.

`WantAssertionsSigned`
:   Whether assertions received by this SP must be signed. The default value is `false`.
    The value set for this option will be used to set the `WantAssertionsSigned` attribute of the `SPSSODescriptor` element in
    the exported SAML 2.0 metadata.

**Examples**:

Here we will list some examples for this authentication source.

### Minimal

    'example-minimal' => [
        'saml:SP',
        'entityID' => 'https://myapp.example.org',
    ],

### Connecting to a specific IdP

    'example' => [
        'saml:SP',
        'entityID' => 'https://myapp.example.org',
        'idp' => 'https://example.net/saml-idp',
    ],

### Encryption and signing

    This SP will accept encrypted assertions, and will sign and validate all messages.

    'example-enc' => [
        'saml:SP',
        'entityID' => 'https://myapp.example.org',

        'certificate' => 'example.crt',
        'privatekey' => 'example.key',
        'privatekey_pass' => 'secretpassword',
        'redirect.sign' => true,
        'redirect.validate' => true,
    ],

### Specifying attributes and required attributes

    An SP that wants eduPersonPrincipalName and mail, where eduPersonPrincipalName should be listed as required:

    'example-attributes => [
        'saml:SP',
        'entityID' => 'https://myapp.example.org',
        'name' => [ // Name required for AttributeConsumingService-element.
            'en' => 'Example service',
            'no' => 'Eksempeltjeneste',
        ],
        'attributes' => [
            'eduPersonPrincipalName',
            'mail',
            // Specify friendly names for these attributes:
            'sn' => 'urn:oid:2.5.4.4',
            'givenName' => 'urn:oid:2.5.4.42',
        ],
        'attributes.required' => [
            'eduPersonPrincipalName',
        ],
        'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic',
    ],

### Limiting supported AssertionConsumerService endpoint bindings

    'example-acs-limit' => [
        'saml:SP',
        'entityID' => 'https://myapp.example.org',
        'acs.Bindings' => [
            'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ],
    ],

### Requesting a specific authentication method

    $auth = new \SimpleSAML\Auth\Simple('default-sp');
    $auth->login([
        'saml:AuthnContextClassRef' => 'urn:oasis:names:tc:SAML:2.0:ac:classes:Password',
    ]);

### Using samlp:Extensions

    $dom = \SimpleSAML\XML\DOMDocumentFactory::create();
    $ce = $dom->createElementNS('http://www.example.com/XFoo', 'xfoo:test', 'Test data!');
    $ext[] = new \SimpleSAML\SAML2\XML\Chunk($ce);

    $auth = new \SimpleSAML\Auth\Simple('default-sp');
    $auth->login([
        'saml:Extensions' => $ext,
    ]);
