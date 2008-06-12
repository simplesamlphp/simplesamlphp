<?php
function attributealter_edupersontargetedid(&$attributes, $spEntityId = null, $idpEntityId = null) {
	assert('$spEntityId !== NULL');
	assert('$idpEntityId !== NULL');

	$userid = SimpleSAML_Utilities::generateUserIdentifier($idpEntityId, $spEntityId, $attributes);

	$attributes['eduPersonTargetedID'] = array($userid);
}
?>