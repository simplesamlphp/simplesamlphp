<?php

if(!array_key_exists('header', $this->data)) {
	$this->data['header'] = 'selectidp';
}
$this->data['header'] = $this->t($this->data['header']);
$this->data['jquery'] = array('version' => '1.6', 'core' => TRUE, 'ui' => TRUE, 'css' => TRUE);

$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" href="' . SimpleSAML_Module::getModuleUrl('discopower/style.css')  . '" />';

$this->data['head'] .= '<script type="text/javascript" src="' . SimpleSAML_Module::getModuleUrl('discopower/js/jquery.livesearch.js')  . '"></script>';
$this->data['head'] .= '<script type="text/javascript" src="' . SimpleSAML_Module::getModuleUrl('discopower/js/quicksilver.js')  . '"></script>';

$this->data['head'] .= '<script type="text/javascript">

$(document).ready(function() {
	$("#discotabs").tabs({ selected: ' . $this->data['defaulttab'] . ' }); ';
$i = 0;
foreach ($this->data['idplist'] AS $tab => $slist) {
	$this->data['head'] .= '$("#query_' . $tab . '").liveUpdate("#list_' . $tab . '")' .
		($i++ == 0 ? '.focus()' : '') .
		';';
}

$this->data['head'] .= '
});

	function chooseidp(idp) {
		$("#chosenidp").attr(\'name\', \'idp_\' + idp);
		$("#idpselectform").submit();
	}


</script>';

# $this->data['autofocus'] = 'preferredidp';

$this->includeAtTemplateBase('includes/header.php');

foreach ($this->data['idplist'] AS $slist) {
	foreach ($slist AS $idpentry) {
		if (isset($idpentry['name']))
			$this->includeInlineTranslation('idpname_' . $idpentry['entityid'], $idpentry['name']);
		if (isset($idpentry['description']))
			$this->includeInlineTranslation('idpdesc_' . $idpentry['entityid'], $idpentry['description']);
	}
}



function showEntry($t, $metadata, $favourite = FALSE) {
	$extra = ($favourite ? ' favourite' : '');
	$html = '<li class="metaentry' . $extra . '" onclick="chooseidp(\'' . htmlspecialchars($metadata['entityid']) . '\')">';
	
	$html .= '' . htmlspecialchars($t->t('idpname_' . $metadata['entityid'])) . '';

	#print_r($metadata['scopes']); 

	// if (!empty($idpentry['description'])) {
	// 	$html .= '	<p>' . htmlspecialchars($t->t('idpdesc_' . $metadata['entityid'])) . '<br />';
	// }
	
	if(array_key_exists('icon', $metadata) && $metadata['icon'] !== NULL) {
		$iconUrl = SimpleSAML_Utilities::resolveURL($metadata['icon']);
		$html .= '<img style="clear: both; float: left; margin: 1em; padding: 3px; border: 1px solid #999" src="' . htmlspecialchars($iconUrl) . '" />';
	}
	
	// $html .= '<input id="preferredidp" type="submit" name="idp_' .
	// 	htmlspecialchars($metadata['entityid']) . '" value="' .
	// 	$t->t('select') . '" /></p>';
	
	$html .= '</li>';
	
	return $html;
}




?>








	<!-- <h2><?php echo $this->data['header']; ?></h2> -->

	<form id="idpselectform" method="get" action="<?php echo $this->data['urlpattern']; ?>">
	<input type="hidden" name="entityID" value="<?php echo htmlspecialchars($this->data['entityID']); ?>" />
	<input type="hidden" name="return" value="<?php echo htmlspecialchars($this->data['return']); ?>" />
	<input type="hidden" name="returnIDParam" value="<?php echo htmlspecialchars($this->data['returnIDParam']); ?>" />
	
	<input id="chosenidp" type="hidden" name="<?php echo htmlspecialchars($this->data['returnIDParam']); ?>" value="1" />
	<!-- <input type="submit" style="" name="formsubmit" id="formsubmit" value="Submit" /> -->
	</form>
	<p><?php
	
	$checked = '';
	if ($this->data['rememberchecked']) {
		$checked = ' checked="checked"';
	}
	echo $this->t('selectidp_full');
	if($this->data['rememberenabled']) {
		echo('<br /><input type="checkbox"' . $checked . ' name="remember" value="1" /> ' . $this->t('remember'));
	}
	?></p>


<div id="discotabs"> 

    <ul class="tabset_tabs">     
    	<?php
    	
    		$tabs = array_keys( $this->data['idplist']);
    		foreach ($tabs AS $tab) {
    			echo '<li><a href="#' . $tab . '"><span>' . $this->t('{discopower:tabs:' . $tab . '}') . '</span></a></li> ';
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
			echo('	<input class="inlinesearchf" type="text" value="" name="query_' . $tab . '" id="query_' . $tab . '" />');
			echo('	</div>');
		
			echo('	<ul class="metalist" id="list_' . $tab  . '">');
			if (!empty($this->data['preferredidp']) && array_key_exists($this->data['preferredidp'], $slist)) {
				$idpentry = $slist[$this->data['preferredidp']];
				echo (showEntry($this, $idpentry, TRUE));
			}

			foreach ($slist AS $idpentry) {
				if ($idpentry['entityid'] != $this->data['preferredidp']) {
					echo (showEntry($this, $idpentry));
				}
			}
			echo('	</ul>');
		}
		echo '</div>';
	
	}
	
		?>
		


</div>

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
