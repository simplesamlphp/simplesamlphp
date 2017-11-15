<?php

/**
 * Does a reverse membership lookup on the logged in user,
 * looking for groups it is a member of and adds them to
 * a defined attribute, in DN format.
 *
 * @author Ryan Panning <panman@traileyes.com>
 * @package SimpleSAMLphp
 */
class sspmod_ldap_Auth_Process_AttributeAddUsersGroups extends sspmod_ldap_Auth_Process_BaseFilter
{
    /**
     * This is run when the filter is processed by SimpleSAML.
     * It will attempt to find the current users groups using
     * the best method possible for the LDAP product. The groups
     * are then added to the request attributes.
     *
     * @throws SimpleSAML_Error_Exception
     * @param $request
     */
    public function process(&$request)
    {
        assert('is_array($request)');
        assert('array_key_exists("Attributes", $request)');

        // Log the process
        SimpleSAML\Logger::debug(
            $this->title . 'Attempting to get the users groups...'
        );

        // Reference the attributes, just to make the names shorter
        $attributes =& $request['Attributes'];
        $map =& $this->attribute_map;

        // Get the users groups from LDAP
        $groups = $this->getGroups($attributes);

        // Make the array if it is not set already
        if (!isset($attributes[$map['groups']])) {
            $attributes[$map['groups']] = array();
        }

        // Must be an array, else cannot merge groups
        if (!is_array($attributes[$map['groups']])) {
            throw new SimpleSAML_Error_Exception(
                $this->title . 'The group attribute [' . $map['groups'] .
                '] is not an array of group DNs. ' . $this->var_export($attributes[$map['groups']])
            );
        }

        // Add the users group(s)
        $group_attribute =& $attributes[$map['groups']];
        $group_attribute = array_merge($group_attribute, $groups);
        $group_attribute = array_unique($group_attribute);

        // All done
        SimpleSAML\Logger::debug(
            $this->title . 'Added users groups to the group attribute [' .
            $map['groups'] . ']: ' . implode('; ', $groups)
        );
    }


    /**
     * This section of code was broken out because the child
     * filter AuthorizeByGroup can use this method as well.
     * Based on the LDAP product, it will do an optimized search
     * using the required attribute values from the user to
     * get their group membership, recursively.
     *
     * @throws SimpleSAML_Error_Exception
     * @param array $attributes
     * @return array
     */
    protected function getGroups(array $attributes)
    {
        // Reference the map, just to make the name shorter
        $map =& $this->attribute_map;

        // Log the request
        SimpleSAML\Logger::debug(
            $this->title . 'Checking for groups based on the best method for the LDAP product.'
        );

        // Based on the directory service, search LDAP for groups
        // If any attributes are needed, prepare them before calling search method
        switch ($this->product) {

            case 'ACTIVEDIRECTORY':

                // Log the AD specific search
                SimpleSAML\Logger::debug(
                    $this->title . 'Searching LDAP using ActiveDirectory specific method.'
                );

                // Make sure the defined dn attribute exists
                if (!isset($attributes[$map['dn']])) {
                    throw new SimpleSAML_Error_Exception(
                        $this->title . 'The DN attribute [' . $map['dn'] .
                        '] is not defined in the users Attributes: ' . implode(', ', array_keys($attributes))
                    );
                }

                // DN attribute must have a value
                if (!isset($attributes[$map['dn']][0]) || !$attributes[$map['dn']][0]) {
                    throw new SimpleSAML_Error_Exception(
                        $this->title . 'The DN attribute [' . $map['dn'] .
                        '] does not have a [0] value defined. ' . $this->var_export($attributes[$map['dn']])
                    );
                }

                // Pass to the AD specific search
                $groups = $this->searchActiveDirectory($attributes[$map['dn']][0]);
                break;
                
            case 'OPENLDAP':
                // Log the OpenLDAP specific search
                SimpleSAML\Logger::debug(
                    $this->title . 'Searching LDAP using OpenLDAP specific method.'
                );
                // Print group search string and search for all group names
                $openldap_base = $this->config->getString('ldap.basedn','ou=groups,dc=example,dc=com');
                SimpleSAML\Logger::debug(
                    $this->title . "Searching for groups in ldap.basedn ".$openldap_base." with filter (".$map['memberof']."=".$attributes[$map['username']][0].") and attributes ".$map['member']
                );
                $groups = array();
                try {
                    // Intention is to filter in 'ou=groups,dc=example,dc=com' for '(memberUid = <value of attribute.username>)' and take only the attributes 'cn' (=name of the group)
                    $all_groups = $this->getLdap()->searchformultiple( $openldap_base, array($map['memberof'] => $attributes[$map['username']][0]) , array($map['member']));
                } catch (SimpleSAML_Error_UserNotFound $e) {
                    break; // if no groups found return with empty (still just initialized) groups array
                }
                // run through all groups and add each to our groups array
                foreach ($all_groups as $group_entry) {
                    $groups[] .= $group_entry[$map['member']][0];
                }
                break;
                                
            default:

                // Log the general search
                SimpleSAML\Logger::debug(
                    $this->title . 'Searching LDAP using the default search method.'
                );

                // Make sure the defined memberOf attribute exists
                if (!isset($attributes[$map['memberof']])) {
                    throw new SimpleSAML_Error_Exception(
                        $this->title . 'The memberof attribute [' . $map['memberof'] .
                        '] is not defined in the users Attributes: ' . implode(', ', array_keys($attributes))
                    );
                }

                // MemberOf must be an array of group DN's
                if (!is_array($attributes[$map['memberof']])) {
                    throw new SimpleSAML_Error_Exception(
                        $this->title . 'The memberof attribute [' . $map['memberof'] .
                        '] is not an array of group DNs. ' . $this->var_export($attributes[$map['memberof']])
                    );
                }

                // Search for the users group membership, recursively
                $groups = $this->search($attributes[$map['memberof']]);
        }

        // All done
        SimpleSAML\Logger::debug(
            $this->title . 'User found to be a member of the groups:' . implode('; ', $groups)
        );
        return $groups;
    }


    /**
     * Looks for groups from the list of DN's passed. Also
     * recursively searches groups for further membership.
     * Avoids loops by only searching a DN once. Returns
     * the list of groups found.
     *
     * @param array $memberof
     * @return array
     */
    protected function search($memberof)
    {
        assert('is_array($memberof)');

        // Used to determine what DN's have already been searched
        static $searched = array();

        // Init the groups variable
        $groups = array();

        // Shorten the variable name
        $map =& $this->attribute_map;

        // Log the search
        SimpleSAML\Logger::debug(
            $this->title . 'Checking DNs for groups.' .
            ' DNs: '. implode('; ', $memberof) .
            ' Attributes: ' . $map['memberof'] . ', ' . $map['type'] .
            ' Group Type: ' . $this->type_map['group']
        );

        // Check each DN of the passed memberOf
        foreach ($memberof as $dn) {

            // Avoid infinite loops, only need to check a DN once
            if (isset($searched[$dn])) {
                continue;
            }

            // Track all DN's that are searched
            // Use DN for key as well, isset() is faster than in_array()
            $searched[$dn] = $dn;

            // Query LDAP for the attribute values for the DN
            try {
                $attributes = $this->getLdap()->getAttributes($dn, array($map['memberof'], $map['type']));
            } catch (SimpleSAML_Error_AuthSource $e) {
                continue; // DN must not exist, just continue. Logged by the LDAP object
            }

            // Only look for groups
            if (!in_array($this->type_map['group'], $attributes[$map['type']], true)) {
                continue;
            }

            // Add to found groups array
            $groups[] = $dn;

            // Recursively search "sub" groups
            $groups = array_merge($groups, $this->search($attributes[$map['memberof']]));
        }

        // Return only the unique group names
        return array_unique($groups);
    }


    /**
     * Searches LDAP using a ActiveDirectory specific filter,
     * looking for group membership for the users DN. Returns
     * the list of group DNs retrieved.
     *
     * @param string $dn
     * @return array
     */
    protected function searchActiveDirectory($dn)
    {
        assert('is_string($dn) && $dn != ""');

        // Shorten the variable name
        $map =& $this->attribute_map;

        // Log the search
        SimpleSAML\Logger::debug(
            $this->title . 'Searching ActiveDirectory group membership.' .
            ' DN: ' . $dn .
            ' DN Attribute: ' . $map['dn'] .
            ' Member Attribute: ' . $map['member'] .
            ' Type Attribute: ' . $map['type'] .
            ' Type Value: ' . $this->type_map['group'] .
            ' Base: ' . implode('; ', $this->base_dn)
        );

        // AD connections should have this set
        $this->getLdap()->setOption(LDAP_OPT_REFERRALS, 0);

        // Search AD with the specific recursive flag
        try {
            $entries = $this->getLdap()->searchformultiple(
                $this->base_dn,
                array($map['type'] => $this->type_map['group'], $map['member'] . ':1.2.840.113556.1.4.1941:' => $dn),
                array($map['dn'])
            );

        // The search may throw an exception if no entries
        // are found, unlikely but possible.
        } catch (SimpleSAML_Error_UserNotFound $e) {
            return array();
        }

        //Init the groups
        $groups = array();

        // Check each entry..
        foreach ($entries as $entry) {

            // Check for the DN using the original attribute name
            if (isset($entry[$map['dn']][0])) {
                $groups[] = $entry[$map['dn']][0];
                continue;
            }

            // Sometimes the returned attribute names are lowercase
            if (isset($entry[strtolower($map['dn'])][0])) {
                $groups[] = $entry[strtolower($map['dn'])][0];
                continue;
            }

            // AD queries also seem to return the objects dn by default
            if (isset($entry['dn'])) {
                $groups[] = $entry['dn'];
                continue;
            }

            // Could not find DN, log and continue
            SimpleSAML\Logger::notice(
                $this->title . 'The DN attribute [' .
                implode(', ', array($map['dn'], strtolower($map['dn']), 'dn')) .
                '] could not be found in the entry. ' . $this->var_export($entry)
            );
        }

        // All done
        return $groups;
    }
}
