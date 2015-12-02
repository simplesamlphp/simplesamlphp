<?php

/*
 * Filter callbacks and callback factories that are useful to most SSP users
 */

class sspmod_metarefresh_CommonFilters
{


    /**
     * Creates a filter that checks the <mdrpi:RegistrationInfo/> element of an entity.
     * @param $authority The registration authority the entity should match.
     * @return Closure A closure that will return true if called with an entity registered by $authority.
     */
    public static function registeredAuthorityFilterFactory($authority)
    {
        return function (SimpleSAML_Metadata_SAMLParser $entityDesc) use ($authority) {
            $metaData = sspmod_metarefresh_CommonFilters::getMetadata($entityDesc);
            return isset($metaData['RegistrationInfo']['registrationAuthority']) && $metaData['RegistrationInfo']['registrationAuthority'] === $authority;
        };
    }

    /**
     * Creates a filter that checks the <mdattr:EntityAttributes> element of an entity.
     * @param $name The name of the attribute to check.
     * @param $value A value that the attribute should contain.
     * @return Closure A closure that will return true if called with an entity that has an attribute with name $name
     *  and value $value.
     */
    public static function entityAttributeFactory($name, $value)
    {
        return function (SimpleSAML_Metadata_SAMLParser $entityDesc) use ($name, $value) {
            $metaData = sspmod_metarefresh_CommonFilters::getMetadata($entityDesc);
            return isset($metaData['EntityAttributes'][$name]) && in_array($value, $metaData['EntityAttributes'][$name], true);
        };
    }

    /**
     * An internal helper function. Limitations in php 5.3 prevent referencing this helper function from anonymous methods
     * unless it is public.
     * @param SimpleSAML_Metadata_SAMLParser $entity
     * @return array
     */
    public static function getMetadata(SimpleSAML_Metadata_SAMLParser $entity)
    {
        $metaData = $entity->getMetadata20SP();
        if (!isset($metaData)) {
            $metaData = $entity->getMetadata20IdP();
        }
        if (!isset($metaData)) {
            $metaData = $entity->getMetadata1xSP();
        }
        if (!isset($metaData)) {
            $metaData = $entity->getMetadata1xIdP();
        }
        return $metaData;
    }
}