<?php


function attributealter_test(&$attributes, $spentityid = null, $idpentityid = null) {
	$attributes['injected'] = array('newvalue');
}

function attributealter_realm(&$attributes, $spentityid = null, $idpentityid = null) {

	if (array_key_exists('eduPersonPrincipalName', $attributes)) {
		$eduppn = $attributes['eduPersonPrincipalName'][0];
		$splitted = explode('@', $eduppn);
		if (count($splitted) > 1) {
			$attributes['realm'] = array($splitted[1]);
		}
	}

}

