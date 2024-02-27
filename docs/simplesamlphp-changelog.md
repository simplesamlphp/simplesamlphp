# SimpleSAMLphp changelog

[TOC]

This document lists the changes between versions of SimpleSAMLphp.
See the upgrade notes for specific information about upgrading.

## Version 2.1.1

Released 2023-11-28

* Fix static call for non-static method in bin/initMDSPdo.php (#1892)
* Restore logout-behaviour for IdP's that do not send a saml:NameID in their LogoutRequest (#1894)
* Fix code error in docs (#1895)
* Fixed a TypeError when accessing the module.php endpoint without specifying a module (#1907)
* Fixed the precondition-functionality for more complex authproc-filters (#1905)
* Reverted a case of 'constructor property promotion' in SAMLParser, causing the validators to be empty (#1904)
* Drop ext-intl in favour of a polyfill (#1908)

`multiauth`

* Fix TypeError due to missing keys in source array (#1900)

## Version 2.1.0

Released 2023-07-31

* Functionality that before was provided by the sanitycheck-module is now implicit.
  The module has been archived and should no longer be used. (#1843)
* Add support for TLS configuration for Redis Store (#1828)
* Add support for Metadata Deployment Profile for errorURL (#1841)
* Raised minimum Symfony version to 6.0
* Raise minimum PHP version to 8.0
* Specified the allowed HTTP methods for each route
* Our dependency on `composer/composer` was dropped in favour of a packaged phar-file.

## Version 2.0.8

Released 2023-11-28

* Fix static call for non-static method in bin/initMDSPdo.php (#1892)
* Restore logout-behaviour for IdP's that do not send a saml:NameID in their LogoutRequest (#1894)
* Fix code error in docs (#1895)
* Fixed a TypeError when accessing the module.php endpoint without specifying a module (#1907)
* Drop ext-intl in favour of a polyfill (#1908)

`multiauth`

* Fix TypeError due to missing keys in source array (#1900)

## Version 2.0.7

Released 2023-10-30

* Fixed a missing Accept-header for metadata queries (#1865)
* Update vulnerable composer (CVE-2023-43655; not affected)
* Fixed a potential XSS-through-DOM (3x; not affected)
* Fixed a warning in the RequestedAuthnContextSelector

## Version 2.0.6

Released 2023-09-07

* Fixed a legacy endpoint causing to break iframe-logout (#1846)
* Fixed an incorrect return-type in RunnableResponse
* Fix for admin.protectmetadata=true - it would show a blank page
* Fix default for entity attributes NameFormat in XML metadata to be 'URI'.
* Fix error message when invoking SSO endpoint without the required parameters.
* Security header regression
* Fixed a regression that made it impossible to configure metadata-signing on individual hosted IdP's (#1792)

## Version 2.0.5

* Never expose the cron-API if secret is not properly configured (#1831)
* Fixed a bug where IdP-initiated SLO using the HTTP-POST binding wasn't properly dealt with

## Version 2.0.4

Released TBD

* The source that was selected by the SourceSelector is now available in the state.
* The zone that was selected by the SourceIPSelector is now available in the state.
* The defaultSource for the SourceIPSelector can now be set to `null`. If none of the zones
  are matched, a NotFound exception will be thrown.
* It is now possible to set a default AuthnContext in the RequestedAuthnContextSelector.
* Fixed a bug in MDQ metadata expiration
* Resolved a possible object injection vulnerability in MDQ metadata cache
* Restored the possibility to use HTTP-Artifact binding on AuthnRequests

## Version 2.0.3

Released 2023-03-29

* Fix exception when translation-file does not exist
* Correct host in generated URLS for IdPs with 'host' config in admin/federation (#1774, #1781)
* Restore dev-autoloader (#1795)

`authyubikey`

* The module wasn't fully compatible with SSP 2.0 yet (v2.0.1)

`cas`

* Fixed a broken controller (simplesamlphp/simplesamlphp-module-cas#6) (v1.1.2)

`saml2debug`

* Fixed a broken controller (simplesamlphp/simplesamlphp-module-saml2debug#4) (v1.0.5)

## Version 2.0.2

Released 2023-03-10

* Fixed the broken 2.0.1 release by restoring an accidentally removed file

## Version 2.0.1

Released 2023-03-10

* The language-menu on mobile devices was fixed
* Fix some issues with logout (#1776, #1780, #1785)
* The `loginpage_links` functionality for authsources was restored and documented (#1770, #1773)
* Several issues regarding the use of the back-button were fixed (#1720)
* Many fixes in documentation
* Fixed config/authsources.php.dist so you can just rename it for new deployments to get you started (#1771)
* Fixed UTF-8 encoding for metadata output
* Fixed incompatibility with SSP 2.0 for the following modules;
  * consent
  * consentadmin
  * consentsimpleadmin
  * exampleattributeserver
  * expirycheck
  * memcachemonitor
  * memcookie
  * metaedit
  * negotiate
  * negotiateext
  * preprodwarning
  * saml2debug
  * sanitycheck
  * sqlauth

`authtwitter`

* A legacy route was added for backwards compatibility
* Docs have been updated

`ldap`

* Fixed the possibility to return ALL attributes (simplesamlphp/simplesamlphp-module-ldap#39)
* Restored the possibility to use anonymous bind (simplesamlphp/simplesamlphp-module-ldap#41)

`negotiate`

* Added support for multi-realm environments

`statistics`

* Fixed missing script-tag to load jQuery
* Fixed static calls to SSP utilities
* Docs have been updated

## Version 2.0.0

Released 2023-02-23

* Many changes, upgrades and improvements since the 1.x series.
* Most notably the new templating system based on Twig, a new
  localization system based on gettext.
* Most modules have been moved out of the core package but can
  easily be installed on-demand as required via composer.
* Better conformance by default to the SAML2INT standard.
* Code cleanups, improvements and simplifications.
* Improved test coverage and more use of standard libraries.
* Compatibility with modern versions of PHP.
* Various new features, including:
  * SAML SubjectID and Pairwise ID support
  * Accepting unsolicited responses can be disabled by setting `enable_unsolicited` to `false` in the SP authsource.
  * Certificates and private keys can now be retrieved from a database
  * Support for Redis sentinel was added.
* Please read the upgrade notes for 2.0 because this release breaks
  backwards compatibility in a number of places.
