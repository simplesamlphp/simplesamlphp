SAML V2.0 Metadata Extensions for Registration and Publication Information
=============================

[TOC]

This is a reference for the SimpleSAMLphp implementation of the [SAML
V2.0 Metadata Extensions for Registration and Publication Information](http://docs.oasis-open.org/security/saml/Post2.0/saml-metadata-rpi/v1.0/saml-metadata-rpi-v1.0.html)
defined by OASIS.

This extension aims to provide information about the registrars and publishers of the metadata themselves, and it is therefore
available through different endpoints and modules that provide metadata all along SimpleSAMLphp. More specifically, this
extension can be used for:

* metadata published for a [hosted service provider](./saml:sp).
* metadata published for a [hosted identity provider](./simplesamlphp-reference-idp-hosted).
* metadata collected and published by means of the [`aggregator2`](./aggregator2:aggregator2) module.

Currently, only the `<mdrpi:RegistrationInfo>` element is supported.

Depending on the metadata set you want to add this extension to, you will have to configure it on the corresponding
configuration file:

* `metadata/saml20-idp-hosted.php` for hosted identity providers.
* `config/authsources.php` for hosted service providers.
* `config/module_aggregator2.php` for the `aggregator2` module.

RegistrationInfo Items
----------------------

The configuration is the same for all the different files, and consists of a single directive called `RegistrationInfo`, which
**must** be an indexed array with the following options:

`RegistrationAuthority`
:   A string containing an identifier of the authority who has registered this metadata. This parameter is **mandatory**.

`RegistrationInstant`
:   A string containing the instant when the entity or entities where registered by the authority. This parameter is
    optional, and must be expressed in the UTC timezone with the *zulu* (`Z`) timezone identifier. If omitted, there will be no
    `registrationInstant` in the resulting metadata, except in the `aggregator2` module, which will use the instant when the metadata
    was generated.

`RegistrationPolicy`
:   An indexed array containing URLs pointing to the policy under which the entity or entities where registered. Each
    index must be the language code corresponding to the language of the URL. This parameter is optional, and will be omitted in the
    resulting metadata if not configured.

Examples
--------

Service Provider:

    'default-sp' => [
        'saml:SP',
        'entityID' => NULL,
        ...
        'RegistrationInfo' => [
            'RegistrationAuthority' => 'urn:mace:sp.example.org',
            'RegistrationInstant' => '2008-01-17T11:28:03.577Z',
            'RegistrationPolicy' => ['en' => 'http://sp.example.org/policy', 'es' => 'http://sp.example.org/politica'],
        ],
    ],

Identity Provider:

    $metadata['https://example.org/saml-idp'] = [
        'host' => '__DEFAULT__',
        ...
        'RegistrationInfo' => [
            'RegistrationAuthority' => 'urn:mace:idp.example.org',
            'RegistrationInstant' => '2008-01-17T11:28:03.577Z',
        ],
    ];

`aggregator2` module:

    $config = [
        'example.org' => [
            'sources' => [
                ...
            ],
            'RegistrationInfo' => [
                'RegistrationAuthority' => 'urn:mace:example.federation',
                'RegistrationPolicy' => ['en' => 'http://example.org/federation_policy', 'es' => 'https://example.org/politica_federacion'],
            ],
        ],
    ];
