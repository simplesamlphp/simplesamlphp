<?php
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
?>