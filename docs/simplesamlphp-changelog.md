# SimpleSAMLphp changelog

[TOC]

This document lists the changes between versions of SimpleSAMLphp.
See the upgrade notes for specific information about upgrading.

## Version 2.0.0

* Many changes, upgrades and improvements since the 1.x series.
* Most notably the new templating system based on Twig, a new
  localization system based on gettext.
* Most modules have been moved out of the core package but can
  easily be installed on-demand as required via composer.
* Better conformance by default to the SAML2INT standard.
* Code cleanups, improvements and simplifications.
* Improved test coverage and more use of standard libraries.
* Compatibility with modern versions of PHP.
* Variouw new features, including:
  * SAML SubjectID and Pairwise ID support
  * Accepting unsolicited responses can be disabled by setting `enable_unsolicited` to `false` in the SP authsource.
  * Certificates and private keys can now be retrieved from a database
  * Support for Redis sentinel was added.
* Please read the upgrade notes for 2.0 because this release breaks
  backwards compatibility in a number of places.
