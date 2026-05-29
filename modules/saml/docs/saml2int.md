# SAML2Int policy configuration (`saml2int.conf.php`)

The SAML module supports enforcing selected requirements from the **SAML V2.0 Deployment Profile for Federation Interoperability (SAML2Int) v2.00**.

To keep SAML2Int policy settings separate from the global `config.php`, SAML2Int enforcement is configured in a dedicated configuration file:

- **Distribution template:** `config/saml2int.conf.php.dist`
- **Local configuration:** `config/saml2int.conf.php`

Create your local config by copying the dist file:

```bash
cp config/saml2int.conf.php.dist config/saml2int.conf.php
```

## Options

### `response.require_signed` (bool)

When set to `true`, the Service Provider (SP) will reject SAML `<Response>` messages that are not signed.

- **Default:** `false`
- **Enforces:** **[SDP-IDP30]** “Responses MUST be signed” (SAML2Int v2.00)

This is stricter than “assertion is signed”, because Response-level fields (for example `Destination`, `InResponseTo`, and `Status`) are not necessarily protected by an Assertion-only signature.

Example:

```php
<?php

declare(strict_types=1);

return [
    'response.require_signed' => true,
];
```

## Notes

- This configuration file is loaded from the standard SimpleSAMLphp configuration directory (usually `config/`).
- Only the settings in `saml2int.conf.php` are used for SAML2Int enforcement. Configure SAML2Int-related options here, not in `config.php`.
