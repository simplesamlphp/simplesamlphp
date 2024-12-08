`saml:ScopedIssuer`
===================

Filter to insert a dynamic saml:Issuer based on a scoped attribute.
This is a requirement when serving multiple domains from one EntraID tenant.
See: [How to connect multiple domains for federation][specification].

[specification]: https://learn.microsoft.com/en-us/entra/identity/hybrid/connect/how-to-connect-install-multiple-domains#multiple-top-level-domain-support

This filter will take an attribute and a pattern as input and transforms this
into a scoped saml:Issuer that is used in the SAML assertion.

Only the first value of `scopeAttribute` is considered.

Examples
--------

```php
    'authproc' => [
        50 => [
            'class' => 'saml:ScopedIssuer',
            'scopeAttribute' => 'userPrincipalName',
            'pattern' => 'https://%1$s/issuer',
        ],
    ],
```
