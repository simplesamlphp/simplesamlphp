SimpleSAMLphp Identity Provider Advanced Topics
===============================================

[TOC]

AJAX iFrame Single Log-Out
--------------------------

If you have read about the AJAX iFrame Single Log-Out approach at Andreas' blog and want to enable it, edit your saml20-idp-hosted.php metadata, and add this configuration line for the IdP:

```php
'logouttype' => 'iframe',
```

Attribute Release Consent
-------------------------

The attribute release consent is documented in a [separate document](/docs/contrib_modules/consent/consent.html).

Support for bookmarking the login page
--------------------------------------

Most SAML software crash fatally when users bookmark the login page and return later on when the cached session information is lost. This is natural as the login page happens in the middle of a SAML transaction, and the SAML software needs some references to the original request in order to be able to produce the SAML Response.

SimpleSAMLphp has implemented a graceful fallback to tackle this situation. When SimpleSAMLphp is not able to lookup a session during the login process, it falls back to the *IdP-first flow*, described in the next section, where the reference to the request is not needed.

What happens in the IdP-first flow is that a *SAML unsolicited response* is sent directly to the SP. An *unsolicited response* is a SAML Response with no reference to a SAML Request (no `InReplyTo` field).

When a SimpleSAMLphp IdP falls back to IdP-first flow, the `RelayState` parameter sent by the SP in the SAML request is also lost. The RelayState information contain a reference key for the SP to lookup where to send the user after successful authentication. The SimpleSAMLphp Service Provider supports configuring a static URL to redirect the user after a unsolicited response is received. See more information about the `RelayState` parameter in the next section: *IdP-first flow*.

IdP-first flow
--------------

If you do not want to start the SSO flow at the SP, you may use the IdP-first setup. To do this, redirect the user to the SSOService endpoint on the IdP with a `spentityid` parameter that matches the SP EntityID that the user should be authenticated for.

Here is an example of such a URL:

`https://idp.example.org/simplesaml/module.php/saml/idp/singleSignOnService?spentityid=urn:mace:feide.no:someservice`

You can also add a `RelayState` parameter to the IdP-first URL:

`https://idp.example.org/simplesaml/module.php/saml/idp/singleSignOnService?spentityid=urn:mace:feide.no:someservice&RelayState=https://sp.example.org/somepage`

The `RelayState` parameter is often used to carry the URL the SP should redirect to after authentication. It is also possible to specify the Assertion
Consumer URL with the `ConsumerURL` parameter.

For compatibility with certain SPs, SimpleSAMLphp will also accept the
`providerId`, `target` and `shire` parameters as aliases for `spentityid`,
`RelayState` and `ConsumerURL`, respectively.

IdP-initiated logout
--------------------

IdP-initiated logout can be initiated by visiting the URL:

`https://idp.example.org/simplesaml/saml2/idp/SingleLogoutService.php?ReturnTo=<URL to return to after logout>`

It will send a logout request to each SP, and afterwards return the user to the URL specified in the `ReturnTo` parameter. Bear in mind that IdPs might disallow redirecting to URLs other than those of their own for security reasons, so in order to get the redirection to work, it might be necessary to ask the IdP to whitelist the URL we are planning to redirect to.

Adding links to the login page
------------------------------

If you want to add some helpful links to the login page, you can add
the following to the `authsources.php` config of the authentication
source you are using:

```php
    'example-userpass' => [
        ...
        'core:loginpage_links' => [
            [
                'href' => 'https://example.com/reset',
                'text' => 'Forgot your password?',
            ],
            [
                'href' => 'https://example.com/news',
                'text' => 'Latest news about us',
            ],
        ],
        ...
    ],
```

The given text will also be translated via SimpleSAMLphp's translation
system if translations are available in the messages catalog.
