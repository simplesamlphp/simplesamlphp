# Upgrade notes for SimpleSAMLphp 3.0

SimpleSAMLphp 3.0 introduces a routing architecture based on a single
global Symfony kernel and a front controller in `public/index.php`.
The following changes are relevant for installers, operators, and
module developers.

## Software requirements

- The minimum PHP version required is now PHP 8.5.

## Web entrypoints and routing

SimpleSAMLphp now handles supported web requests through
`public/index.php`.

- Dynamic module endpoints are exposed under `/module/<module>/...`.
- Module browser assets are published under `/assets/<module>/...`.
- The legacy `public/module.php` PHP entry script is no longer present;
  all requests are handled by `public/index.php`. (Legacy
  `module.php/...` *URLs* are still handled during the transition period — see
  [Legacy `module.php` URL compatibility](#legacy-modulephp-url-compatibility) below.)
- The legacy `public/saml2/idp/*.php` scripts are no longer supported.

If your deployment previously linked directly to `module.php` or other
public PHP scripts, update those URLs to the routed equivalents.

Examples:

- Admin interface:
  - old: `/simplesaml/module.php/admin/`
  - new: `/simplesaml/module/admin`
- Metadata converter:
  - old: `/simplesaml/module.php/admin/federation/metadata-converter`
  - new: `/simplesaml/module/admin/federation/metadata-converter`
- IdP SSO endpoint:
  - old: `/simplesaml/module.php/saml/idp/singleSignOnService`
  - new: `/simplesaml/module/saml/idp/singleSignOnService`
- SP metadata endpoint:
  - old: `/simplesaml/module.php/saml/sp/metadata.php/default-sp`
  - new: `/simplesaml/module/saml/sp/metadata/default-sp`
- Module asset:
  - old: `/simplesaml/module.php/mymodule/assets/app.css`
  - new: `/simplesaml/assets/mymodule/app.css`

### Legacy `module.php` URL compatibility

Changing the route of every endpoint also changes the URLs that appear
in SAML metadata — for example an IdP's SingleSignOnService, an SP's
AssertionConsumerService and SingleLogoutService, and the metadata
endpoints published by protocol modules such as `saml`, `oidc`, `casserver`
and `adfs`. Because authentication protocols typically work by exchanging
metadata between entities, a peer that still holds your pre-3.0 metadata
would otherwise send requests and responses to URLs that no longer exist,
breaking authentication until every peer refreshes its metadata.

To keep existing federations working across the upgrade, v3.0 serves
every module route under both the new `/module/<module>/...` path and
the legacy `/module.php/<module>/...` path. The legacy paths are served
**natively by the same controllers** — they are not HTTP redirects.

This compatibility layer is always on in v3.0 and requires no
configuration. However, **It is deprecated and scheduled for removal in a
future release.** Treat v3.0 as the transition window: republish your metadata
(and ask your federation peers to refresh theirs) with the new
`/module/...` URLs. Once all peers consume the updated metadata, the
legacy URLs are no longer needed, and a later release will remove them.
Also, make sure to update any hardcoded URLs in your own external applications,
templates, and modules, that call SimpleSAMLphp endpoints.

## Custom error rendering

The `errors.show_function` configuration option has been removed.
Custom error rendering is now handled through Symfony's event system.

To customize error pages, create a `kernel.exception` event subscriber
in your module and register it with a priority higher than 200 (which
is the priority used by SimpleSAMLphp's built-in error handler).

See [Error Handling](./simplesamlphp-errorhandling.md) for details and
a complete example.

## Installer and operator impact

Your web server must route non-file requests in `public/` to
`index.php`.

- Apache deployments should use the provided `public/.htaccess` file or
  equivalent rewrite rules in the virtual-host configuration.
- Nginx and other web servers must be configured with equivalent
  front-controller routing.

Static files under `public/assets/` continue to be served directly by
the web server. Dynamic requests should no longer be mapped to
individual PHP scripts inside `public/`.

After upgrading, clear the cache so that any stale routing or
controller metadata is rebuilt:

```sh
composer clear-symfony-cache
```

## Developer and module-author impact

SimpleSAMLphp now runs a single global kernel that loads the services,
routes, and controllers for all enabled modules.

- Dynamic module behavior must be implemented with routes and
  controllers.
- `modules/<module>/public/` is no longer a place for PHP entry
  scripts.
- Browser assets should live under `modules/<module>/public/assets/`
  and be published to `public/assets/<module>/`.
- Callers, templates, and external integrations should stop generating
  `module.php/...` URLs.

For module URLs, use the URL helpers provided by SimpleSAMLphp instead
of hardcoding legacy paths. Use `\SimpleSAML\Module::getModuleURL()`,
which builds a `/module/...` URL under the configured `baseurlpath`. The
`$resource` argument is the module-relative path (without a leading
slash), and optional query parameters can be passed as the second
argument:

```php
use SimpleSAML\Module;

// before (legacy): .../simplesaml/module.php/saml/sp/metadata.php/default-sp
// after:           .../simplesaml/module/saml/sp/metadata/default-sp
$url = Module::getModuleURL('saml/sp/metadata/default-sp');

// with query parameters
$url = Module::getModuleURL('core/logout', ['ReturnTo' => $returnTo]);
```

For assets, use the published asset URLs instead of the legacy
`module.php/<module>/...` paths. Use
`\SimpleSAML\Module::getModuleAssetUrl()`, which builds an
`/assets/<module>/...` URL under the configured `baseurlpath`:

```php
use SimpleSAML\Module;

// before (legacy): .../simplesaml/module.php/mymodule/assets/css/style.css
// after:           .../simplesaml/assets/mymodule/css/style.css
$url = Module::getModuleAssetUrl('mymodule', 'css/style.css');
```

In Twig templates, the same helpers are exposed as the `moduleURL()` and
`asset()` functions, so templates do not need to hardcode paths either:

```twig
<link rel="stylesheet" href="{{ asset('css/style.css', 'mymodule') }}">
<a href="{{ moduleURL('saml/sp/metadata/default-sp') }}">Metadata</a>
```

## General upgrade advice

When upgrading an existing deployment:

1. Update web server configuration so requests under the configured
   `baseurlpath` reach `public/index.php`.
2. Replace documented or hardcoded `module.php/...` URLs with
   `/module/...` URLs.
3. Replace legacy module asset URLs with `/assets/<module>/...`.
4. Review any custom modules for direct PHP entry scripts in
   `modules/<module>/public/` and move that behavior into routed
   controllers.
5. Remove `error.show_function` option from `config/config.php` if present.
   If you used custom error function, move the implementation to event
   subscribers instead.
6. Clear the Symfony cache after deployment.
