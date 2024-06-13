Metadata endpoints
==================

This document gives a short introduction to the various methods forms metadata endpoints can take in SimpleSAMLphp.

The endpoints we have are:

Endpoint                       | Indexed | Default binding
-------------------------------|---------|----------------
`ArtifactResolutionService`    | Y       | SOAP
`AssertionConsumerService`     | Y       | HTTP-POST
`SingleLogoutService`          | N       | HTTP-Redirect
`SingleSignOnService`          | N       | HTTP-Redirect

The various endpoints can be specified in the following format:

    'AssertionConsumerService' => [
        [
            'index' => 1,
            'isDefault' => true,
            'Location' => 'https://sp.example.org/ACS',
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ],
        [
            'index' => 2,
            'Location' => 'https://sp.example.org/ACS',
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
        ],
    ],

This endpoint format allows for specifying multiple endpoints with different bindings.
It can also be used to specify the ResponseLocation attribute on endpoints, e.g. on `SingleLogoutService`:

    'SingleLogoutService' => [
        [
            'Location' => 'https://sp.example.org/LogoutRequest',
            'ResponseLocation' => 'https://sp.example.org/LogoutResponse',
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ],
    ],
