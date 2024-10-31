# Upgrade notes for SimpleSAMLphp 2.4

SimpleSAMLphp 2.4 is a minor new release which introduces a few new features.
The following changes are relevant for installers and/or developers.

- Where a configuration has multiple hosted IdPs, metadata is now associated with the entityId.
  This means that endpoints such as SingleSignOnService values will be taken from the
  entityId block in saml20-idp-hosted.php. See (<https://github.com/simplesamlphp/simplesamlphp/pull/2270>) for details.

## Deprecations

The following classes were marked `deprecated` and will be removed in a next major release.

- fixme

## BC break

- fixme
