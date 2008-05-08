<?php




function encodeIllegalChars($input) {
	return preg_replace("/[^a-zA-Z0-9_@=.]/", "_", $input);
}

function getRealmPart($userid) {

	$decomposedID = explode("@", $userid);
	if (isset($decomposedID[1])) {
		return self::encodeIllegalChars($decomposedID[1]);
	}
	return null;
}

function attributealter_groups(&$attributes, $spentityid = null, $idpentityid = null) {

	// We start off with an empty list of groups.
	$groups = array();
	
	/*
	 * Then we add the realm of the user. The part after the @ of the eduPersonPrincipalName
	 */
	$realmpart = getRealmPart($attributes['eduPersonPrincipalName']);
	if (isset($realmpart)) {
		$groups[] = 'realm-' . $realmpart;
	} else {
		$realmpart = 'NA';
	}

	
	/*
	 * Create group membership by the eduPersonAffiliation attribute.
	 */
	if (isset($attributes['eduPersonAffiliation']) && is_array($attributes['eduPersonAffiliation']) ) {
		foreach ($attributes['eduPersonAffiliation'] AS $affiliation) {
			$groups[] = 'affiliation-' . $realmpart . '-' . encodeIllegalChars($affiliation);
		}
	}
	
	/*
	 * Create group membership by the eduPersonOrgUnitDN attribute.
	 */
	if (isset($attributes['eduPersonOrgUnitDN']) && is_array($attributes['eduPersonOrgUnitDN']) ) {
		foreach ($attributes['eduPersonOrgUnitDN'] AS $orgunit) {
			$groups[] = 'orgunit-' . $realmpart . '-' . encodeIllegalChars($orgunit);
		}
	}
	
	if (isset($attributes['eduPersonEntitlement']) && is_array($attributes['eduPersonEntitlement']) ) {
		foreach ($attributes['eduPersonEntitlement'] AS $orgunit) {
			$groups[] = 'entitlement-' . $realmpart . '-' . encodeIllegalChars($orgunit);
		}
	}
	
	
	/*
	 * Read custom groups from the group file specified in the 

	if (file_exists('/etc/simplesamlphpgroups.txt')) {
		include($conf['groupfile']);
	}
	if (isset($customgroups[$user]) && is_array($customgroups[$user])) {
		foreach ($customgroups[$user] AS $ng) {
			$groups[] = $ng;
		}
	}
	 */
	$attributes['groups'] = $groups;

}



function attributealter_test(&$attributes, $spentityid = null, $idpentityid = null) {
	$attributes['injected'] = array('newvalue');
}

function attributealter_realm(&$attributes, $spentityid = null, $idpentityid = null) {

	$attributename = 'eduPersonPrincipalName';
#	$attributename = 'edupersonprincipalname';
	if (array_key_exists($attributename, $attributes)) {
		$eduppn = $attributes[$attributename][0];
		$splitted = explode('@', $eduppn);
		if (count($splitted) > 1) {
			$attributes['realm'] = array($splitted[1]);
		} else {
			SimpleSAML_Logger::debug('attributealter_realm: Wrong format on ' . $attributename . ' (not including @)');
		}
	} else {
		SimpleSAML_Logger::debug('attributealter_realm: Could not find ' . $attributename);
	}

}

function attributealter_edupersontargetedid(&$attributes, $spEntityId = null, $idpEntityId = null) {
	assert('$spEntityId !== NULL');
	assert('$idpEntityId !== NULL');

	$userid = SimpleSAML_Utilities::generateUserIdentifier($idpEntityId, $spEntityId, $attributes);

	$attributes['eduPersonTargetedID'] = array($userid);
}
