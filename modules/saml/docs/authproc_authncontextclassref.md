`saml:AuthnContextClassRef`
===========================

IDP-side filter for setting the `AuthnContextClassRef` element in the authentication response.

Examples
--------

    'authproc.idp' => [
      92 => [
        'class' => 'saml:AuthnContextClassRef',
        'AuthnContextClassRef' => 'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport',
      ],
    ],
