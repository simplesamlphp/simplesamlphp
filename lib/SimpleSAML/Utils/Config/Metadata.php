<?php
/**
 * Class with utilities to fetch different configuration objects from metadata configuration arrays.
 *
 * @package SimpleSAMLphp
 * @author Jaime Pérez Crespo, UNINETT AS <jaime.perez@uninett.no>
 */
class SimpleSAML_Utils_Config_Metadata {

    /**
     * Parse and sanitize a contact from an array.
     *
     * Accepts an array with the following elements:
     * - contactType     The type of the contact (as string). Mandatory.
     * - emailAddress    Email address (as string), or array of email addresses. Optional.
     * - telephoneNumber Telephone number of contact (as string), or array of telephone numbers. Optional.
     * - name            Full name of contact, either as <GivenName> <SurName>, or as <SurName>, <GivenName>. Optional.
     * - surName         Surname of contact (as string). Optional.
     * - givenName       Givenname of contact (as string). Optional.
     * - company         Company name of contact (as string). Optional.
     *
     * The following values are allowed as "contactType":
     * - technical
     * - support
     * - administrative
     * - billing
     * - other
     *
     * If given a "name" it will try to decompose it into its given name and surname, only if neither givenName nor
     * surName are present. It works as follows:
     * - "surname1 surname2, given_name1 given_name2"
     *      givenName: "given_name1 given_name2"
     *      surname: "surname1 surname2"
     * - "given_name surname"
     *      givenName: "given_name"
     *      surname: "surname"
     *
     * otherwise it will just return the name as "givenName" in the resulting array.
     *
     * @param array $contact The contact to parse and sanitize.
     * @return array An array holding valid contact configuration options. If a key 'name' was part of the input array,
     * it will try to decompose the name into its parts, and place the parts into givenName and surName, if those are
     * missing.
     * @throws InvalidArgumentException if the contact does not conform to valid configuration rules for contacts.
     */
    public static function getContact($contact)
    {
        assert('is_array($contact) || is_null($contact)');
        $valid_options = array('contactType', 'emailAddress', 'givenName', 'surName', 'telephoneNumber', 'company');
        $valid_types   = array('technical', 'support', 'administrative', 'billing', 'other');

        // check the type
        if (!isset($contact['contactType']) || !in_array($contact['contactType'], $valid_types, true)) {
            $types = join(', ', array_map(
                function($t) {
                    return '"'.$t.'"';
                },
                $valid_types
            ));
            throw new InvalidArgumentException('"contactType" is mandatory and must be one of '. $types.".");
        }

        // try to fill in givenName and surName from name
        if (isset($contact['name']) && !isset($contact['givenName']) && !isset($contact['surName'])) {
            // first check if it's comma separated
            $names = explode(',', $contact['name'], 2);
            if (count($names) === 2) {
                $contact['surName'] = preg_replace('/\s+/', ' ', trim($names[0]));
                $contact['givenName'] = preg_replace('/\s+/', ' ', trim($names[1]));
            } else {
                // check if it's in "given name surname" format
                $names = explode(' ', preg_replace('/\s+/', ' ', trim($contact['name'])));
                if (count($names) === 2) {
                    $contact['givenName'] = preg_replace('/\s+/', ' ', trim($names[0]));
                    $contact['surName'] = preg_replace('/\s+/', ' ', trim($names[1]));
                } else {
                    // nothing works, return it as given name
                    $contact['givenName'] = preg_replace('/\s+/', ' ', trim($contact['name']));
                }
            }
        }

        // check givenName
        if (isset($contact['givenName']) && (
                empty($contact['givenName']) || !is_string($contact['givenName'])
            )) {
            throw new InvalidArgumentException('"givenName" must be a string and cannot be empty.');
        }

        // check surName
        if (isset($contact['surName']) && (
                empty($contact['surName']) || !is_string($contact['surName'])
            )) {
            throw new InvalidArgumentException('"surName" must be a string and cannot be empty.');
        }

        // check company
        if (isset($contact['company']) && (
                empty($contact['company']) || !is_string($contact['company'])
            )) {
            throw new InvalidArgumentException('"company" must be a string and cannot be empty.');
        }

        // check emailAddress
        if (isset($contact['emailAddress'])) {
            if (empty($contact['emailAddress']) ||
                !(is_string($contact['emailAddress']) || is_array($contact['emailAddress']))) {
                throw new InvalidArgumentException('"emailAddress" must be a string or an array and cannot be empty.');
            }
            if (is_array($contact['emailAddress'])) {
                foreach ($contact['emailAddress'] as $address) {
                    if (!is_string($address) || empty($address)) {
                        throw new InvalidArgumentException('Email addresses must be a string and cannot be empty.');
                    }
                }
            }
        }

        // check telephoneNumber
        if (isset($contact['telephoneNumber'])) {
            if (empty($contact['telephoneNumber']) ||
                !(is_string($contact['telephoneNumber']) || is_array($contact['telephoneNumber']))) {
                throw new InvalidArgumentException('"telephoneNumber" must be a string or an array and cannot be empty.');
            }
            if (is_array($contact['telephoneNumber'])) {
                foreach ($contact['telephoneNumber'] as $address) {
                    if (!is_string($address) || empty($address)) {
                        throw new InvalidArgumentException('Telephone numbers must be a string and cannot be empty.');
                    }
                }
            }
        }

        // make sure only valid options are outputted
        return array_intersect_key($contact, array_flip($valid_options));
    }

}
