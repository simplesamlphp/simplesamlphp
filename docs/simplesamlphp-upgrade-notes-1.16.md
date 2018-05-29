Upgrade notes for SimpleSAMLphp 1.16
====================================

The default signature algoritm is now SHA-256 (SHA-1 has been considered
obsolete since 2014). For entities that need it, you can switch back to
SHA-1 by setting the `signature.algorithm` option in the entity metadata.

The class `SimpleSAML_Error_BadUserInnput` has been renamed to
`SimpleSAML_Error_BadUserInput`.

The `authmyspace` module has been removed since the service is no longer
available. 
