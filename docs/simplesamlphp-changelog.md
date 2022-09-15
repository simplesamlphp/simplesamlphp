# SimpleSAMLphp changelog

[TOC]

This document lists the changes between versions of SimpleSAMLphp.
See the upgrade notes for specific information about upgrading.

## Version 2.0.0

* Support for certificate fingerprints was removed
* Support for SAML 1.1 was removed
* Old-style PHP templates were removed
* Old-style dictionaries were removed
* The default value for attrname-format was changed to 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri'
  to comply with SAML2INT
* core:PairwiseID and core:SubjectID authprocs no longer support the 'scope' config-setting.
  Use 'scopeAttribute' instead to identify the attribute holding the scope.
* Accepting unsolicited responses can be disabled by setting `enable_unsolicited` to `false` in the SP authsource.
* Certificates and private keys can now be retrieved from a database
* Support for Redis sentinel was added (#1699)
