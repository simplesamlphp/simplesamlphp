SAML V2.0 Metadata Extensions for Identity Provider Discovery Service Protocol and Profile
=============================

[TOC]

This is a reference for the SimpleSAMLphp implementation of the [SAML
V2.0 Metadata Extensions for Identity Provider Discovery Service Protocol and Profile](http://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-idp-discovery.pdf)
defined by OASIS.

The metadata extension is available to SP usage of SimpleSAMLphp. The entries are placed inside the relevant
entry in `authsources.php`.

An example:

    <?php
    $config = [

        'default-sp' => [
            'saml:SP',

            'DiscoveryResponse' => [
                [
                    'index' => 1,
                    'Binding' => 'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol',
                    'Location' => 'https://simplesamlphp.org/some/endpoint',
                    'isDefault' => true,
                ],
            ],
            /* ... */
        ],
    ];

Generated XML Metadata Examples
----------------

The example given above will generate the following XML metadata:

    <?xml version="1.0"?>
    <md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:idpdisc="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" entityID="https://example.com/saml-idp">
      <md:SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <md:Extensions>
          <idpdisc:DiscoveryResponse xmlns:idpdisc="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol" Binding="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol" Location="https://simplesamlphp.org/some/endpoint" index="1" isDefault="true" />
        </md:Extensions>
        <md:KeyDescriptor use="signing">
          <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
            <ds:X509Data>
            ...
