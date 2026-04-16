# Upgrade notes for SimpleSAMLphp 3.0

SimpleSAMLphp 3.0 introduces a routing architecture based on a single
global Symfony kernel and a front controller in `public/index.php`.
The following changes are relevant for installers, operators, and
module developers.

## Web entrypoints and routing

SimpleSAMLphp now handles supported web requests through
`public/index.php`.

- Dynamic module endpoints are exposed under `/module/<module>/...`.
- Module browser assets are published under `/assets/<module>/...`.
- The legacy `public/module.php` entrypoint is no longer supported.
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

Some legacy routes still redirect to their routed equivalents to ease
transition, but 3.0 documentation should treat the routed paths as the
supported interface.

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
of hardcoding legacy paths. For assets, use the published asset URLs.

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
5. Clear the Symfony cache after deployment.
