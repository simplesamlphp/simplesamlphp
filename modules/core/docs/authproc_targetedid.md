`core:TargetedID`
=================

This filter generates the `eduPersonTargetedID` attribute for the user.

This filter will use the contents of the attribute set by the `identifyingAttribute` option as the unique user ID.

Parameters
----------

`identifyingAttribute`
:   The name of the attribute we should use for the unique user identifier.

    Note: only the first value of the specified attribute is being used for the generation of the identifier.

`nameId`
:   Set this option to `true` to generate the attribute as in SAML 2 NameID format.
    This can be used to generate an Internet2 compatible `eduPersonTargetedID` attribute.
    Optional, defaults to `false`.

Examples
--------

A custom attribute:

    'authproc' => [
        50 => [
            'class' => 'core:TargetedID',
            'identifyingAttribute' => 'eduPersonPrincipalName'
        ],
    ],

Internet2 compatible `eduPersontargetedID`:

    /* In saml20-idp-hosted.php. */
    $metadata['urn:x-simplesamlphp:example-idp'] = [
        'host' => '__DEFAULT__',
        'auth' => 'example-static',

        'authproc' => [
            60 => [
                'class' => 'core:TargetedID',
                'nameId' => true,
            ],
            90 => [
                'class' => 'core:AttributeMap',
                'name2oid',
            ],
        ],
        'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
        'attributeencodings' => [
            'urn:oid:1.3.6.1.4.1.5923.1.1.1.10' => 'raw', /* eduPersonTargetedID with oid NameFormat. */
        ],
    ];
