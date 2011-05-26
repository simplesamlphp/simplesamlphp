<?php


$faventry = NULL;
foreach( $this->data['idplist'] AS $tab => $slist) {
	if (!empty($this->data['preferredidp']) && array_key_exists($this->data['preferredidp'], $slist))
		$faventry = $slist[$this->data['preferredidp']];
}






if(!array_key_exists('header', $this->data)) {
	$this->data['header'] = 'selectidp';
}
$this->data['header'] = $this->t($this->data['header']);
$this->data['jquery'] = array('version' => '1.6', 'core' => TRUE, 'ui' => TRUE, 'css' => TRUE);

$this->data['head'] = '<link rel="stylesheet" media="screen" type="text/css" href="' . SimpleSAML_Module::getModuleUrl('discopower/style.css')  . '" />';

$this->data['head'] .= '<script type="text/javascript" src="' . SimpleSAML_Module::getModuleUrl('discopower/js/jquery.livesearch.js')  . '"></script>';
$this->data['head'] .= '<script type="text/javascript" src="' . SimpleSAML_Module::getModuleUrl('discopower/js/' . $this->data['score'] . '.js')  . '"></script>';

$this->data['head'] .= '<script type="text/javascript">

$(document).ready(function() {
	$("#discotabs").tabs({ selected: ' . $this->data['defaulttab'] . ' }); ';
	
$i = 0;
foreach ($this->data['idplist'] AS $tab => $slist) {
	$this->data['head'] .= "\n" . '$("#query_' . $tab . '").liveUpdate("#list_' . $tab . '")' .
		(($i++ == 0) && (empty($faventry)) ? '.focus()' : '') .
		';';


}

$this->data['head'] .= '
});

</script>';





if (!empty($faventry)) $this->data['autofocus'] = 'favouritesubmit';

$this->includeAtTemplateBase('includes/header.php');

// foreach ($this->data['idplist'] AS $slist) {
// 	foreach ($slist AS $idpentry) {
// 		if (isset($idpentry['name']))
// 			$this->includeInlineTranslation('idpname_' . $idpentry['entityid'], $idpentry['name']);
// 		if (isset($idpentry['description']))
// 			$this->includeInlineTranslation('idpdesc_' . $idpentry['entityid'], $idpentry['description']);
// 	}
// }
// 


function showEntry($t, $metadata, $favourite = FALSE) {
	
	$basequerystring = '?' . 
		'entityID=' . urlencode($t->data['entityID']) . '&amp;' . 
		'return=' . urlencode($t->data['return']) . '&amp;' . 
		'returnIDParam=' . urlencode($t->data['returnIDParam']) . '&amp;idpentityid=';
	
	$extra = ($favourite ? ' favourite' : '');
	$html = '<a class="metaentry' . $extra . '" href="' . $basequerystring . urlencode($metadata['entityid']) . '">';
	
	$html .= '' . htmlspecialchars(getTranslatedName($t, $metadata)) . '';

	#print_r($metadata['scopes']); 

	// if (!empty($idpentry['description'])) {
	// 	$html .= '	<p>' . htmlspecialchars($t->t('idpdesc_' . $metadata['entityid'])) . '<br />';
	// }
	
	if(array_key_exists('icon', $metadata) && $metadata['icon'] !== NULL) {
		$iconUrl = SimpleSAML_Utilities::resolveURL($metadata['icon']);
		$html .= '<img alt="Icon for identity provider" class="entryicon" src="' . htmlspecialchars($iconUrl) . '" />';
	}
	
	// $html .= '<input id="preferredidp" type="submit" name="idp_' .
	// 	htmlspecialchars($metadata['entityid']) . '" value="' .
	// 	$t->t('select') . '" /></p>';
	
	$html .= '</a>';
	
	return $html;
}

?>




<?php

function getTranslatedName($t, $metadata) {
#	if (is_null($metadata)) throw new Exception();
	if (array_key_exists('name', $metadata)) {
		if (is_array($metadata['name'])) {
			return $t->getTranslation($metadata['name']);
		} else {
			return $metadata['name'];
		}
	}
	return $metadata['entityid'];
}




if (!empty($faventry)) {


	echo('<div class="favourite">');
	echo($this->t('previous_auth'));
	echo(' <strong>' . htmlspecialchars(getTranslatedName($this, $faventry)) . '</strong>');
	echo('
	<form id="idpselectform" method="get" action="' . $this->data['urlpattern'] . '">
		<input type="hidden" name="entityID" value="' . htmlspecialchars($this->data['entityID']) . '" />
		<input type="hidden" name="return" value="' . htmlspecialchars($this->data['return']) . '" />
		<input type="hidden" name="returnIDParam" value="' . htmlspecialchars($this->data['returnIDParam']) . '" />
		<input type="hidden" name="idpentityid" value="' . htmlspecialchars($faventry['entityid']) . '" />

		<input type="submit" name="formsubmit" id="favouritesubmit" value="' . $this->t('login_at') . ' ' . htmlspecialchars(getTranslatedName($this, $faventry)) . '" /> 
	</form>');

	echo('</div>');
}


?>






<div id="discotabs"> 

    <ul class="tabset_tabs">     
    	<?php
    	
    		$tabs = array_keys( $this->data['idplist']);
    		foreach ($tabs AS $tab) {
			if(!empty($this->data['idplist'][$tab])) {
				echo '<li><a href="#' . $tab . '"><span>' . $this->t('{discopower:tabs:' . $tab . '}') . '</span></a></li> ';
			}
    		}
    	
    	?>
    </ul> 
    

<?php




foreach( $this->data['idplist'] AS $tab => $slist) {

	echo '<div id="' . $tab . '">';

	if (!empty($slist)) {
		
		// echo 'Favourite :: ' . $this->data['preferredidp']; 
		// echo '<pre>';
		// print_r($slist); exit;

		echo('	<div class="inlinesearch">');
		echo('	<p>Incremental search...</p>');
		echo('	<form id="idpselectform" action="?" method="get"><input class="inlinesearchf" type="text" value="" name="query_' . $tab . '" id="query_' . $tab . '" /></form>');
		echo('	</div>');
	
		echo('	<div class="metalist" id="list_' . $tab  . '">');
		if (!empty($this->data['preferredidp']) && array_key_exists($this->data['preferredidp'], $slist)) {
			$idpentry = $slist[$this->data['preferredidp']];
			echo (showEntry($this, $idpentry, TRUE));
		}

		foreach ($slist AS $idpentry) {
			if ($idpentry['entityid'] != $this->data['preferredidp']) {
				echo (showEntry($this, $idpentry));
			}
		}
		echo('	</div>');
	}
	echo '</div>';

}
	
?>



</div>

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
