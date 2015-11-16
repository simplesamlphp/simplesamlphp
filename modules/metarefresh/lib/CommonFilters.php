<?php
/*
 * Filter callbacks and callback factories that are useful to most SSP users
 */
class sspmod_metarefresh_CommonFilters {


    public static function registeredAuthorityFilterFactory($authority) {
        return function(SimpleSAML_Metadata_SAMLParser $entityDesc) use ($authority) {
            $metaData = self::getMetadata($entityDesc);
            return isset($metaData['RegistrationInfo']['registrationAuthority']) && $metaData['RegistrationInfo']['registrationAuthority'] === $authority;
        };
    }

    public static function entityAttributeFactory($name, $value) {
        return function(SimpleSAML_Metadata_SAMLParser $entityDesc) use ($name, $value) {
            $metaData = self::getMetadata($entityDesc);
            return isset($metaData['EntityAttributes'][$name]) && in_array($value,$metaData['EntityAttributes'][$name], true);
        };
    }


    private static function getMetadata(SimpleSAML_Metadata_SAMLParser $entity) {
        $metaData = $entity->getMetadata20SP();
        if (!isset($metaData)) {
            $metaData = $entity->getMetadata20IdP();
        }
        return $metaData;
    }
}