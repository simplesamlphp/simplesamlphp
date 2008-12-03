<?php

if(!array_key_exists('header', $this->data)) {
	$this->data['header'] = 'selectidp';
}
$this->data['header'] = $this->t($this->data['header']);

$this->data['head']  = '<script type="text/javascript" src="/' . $this->data['baseurlpath'] . 'resources/jquery.js"></script>';
$this->data['head'] .= '<script type="text/javascript" src="/' . $this->data['baseurlpath'] . 'resources/jquery-ui.js"></script>';
$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" href="/' . $this->data['baseurlpath'] . 'resources/uitheme/jquery-ui-themeroller.css" />';

$this->data['head'] .= '<script type="text/javascript">

$(document).ready(function() {
	$("#discotabs > ul").tabs({ selected: ' . $this->data['defaulttab'] . ' });
	/*
		$("#foodledescr").resizable({ 
			handles: "all" 
		});
	*/
});
</script>';

$this->data['autofocus'] = 'preferredidp';

$this->includeAtTemplateBase('includes/header.php');

foreach ($this->data['idplist'] AS $slist) {
	foreach ($slist AS $idpentry) {
		if (isset($idpentry['name']))
			$this->includeInlineTranslation('idpname_' . $idpentry['entityid'], $idpentry['name']);
		if (isset($idpentry['description']))
			$this->includeInlineTranslation('idpdesc_' . $idpentry['entityid'], $idpentry['description']);
	}
}


?>


	<h2><?php echo $this->data['header']; ?></h2>

	<form method="get" action="<?php echo $this->data['urlpattern']; ?>">
	<input type="hidden" name="entityID" value="<?php echo htmlspecialchars($this->data['entityID']); ?>" />
	<input type="hidden" name="return" value="<?php echo htmlspecialchars($this->data['return']); ?>" />
	<input type="hidden" name="returnIDParam" value="<?php echo htmlspecialchars($this->data['returnIDParam']); ?>" />
	
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

    <ul>     
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

		
		if (!empty($this->data['preferredidp']) && array_key_exists($this->data['preferredidp'], $slist)) {
			$idpentry = $slist[$this->data['preferredidp']];
			echo '<div class="preferredidp">';
			echo '	<img src="/' . $this->data['baseurlpath'] .'resources/icons/star.png" style="float: right" />';

			if(array_key_exists('icon', $idpentry) && $idpentry['icon'] !== NULL) {
				$iconUrl = SimpleSAML_Utilities::resolveURL($idpentry['icon']);
				echo '<img style="float: left; margin: 1em; padding: 3px; border: 1px solid #999" src="' . htmlspecialchars($iconUrl) . '" />';
			}
			echo '<h3 style="margin-top: 8px">' . htmlspecialchars($this->t('idpname_' . $idpentry['entityid'])) . '</h3>';

			if (!empty($idpentry['description'])) {
				echo '	<p>' . htmlspecialchars($this->t('idpdesc_' . $idpentry['entityid'])) . '<br />';
			}
			echo('<input id="preferredidp" type="submit" name="idp_' .
				htmlspecialchars($idpentry['entityid']) . '" value="' .
				$this->t('select') . '" /></p>');
			echo '</div>';
		}
		
		
		foreach ($slist AS $idpentry) {
			if ($idpentry['entityid'] != $this->data['preferredidp']) {

				if(array_key_exists('icon', $idpentry) && $idpentry['icon'] !== NULL) {
					$iconUrl = SimpleSAML_Utilities::resolveURL($idpentry['icon']);
					echo '<img style="clear: both; float: left; margin: 1em; padding: 3px; border: 1px solid #999" src="' . htmlspecialchars($iconUrl) . '" />';
				}
				echo '	<h3 style="margin-top: 8px">' . htmlspecialchars($this->t('idpname_' . $idpentry['entityid'])) . '</h3>';

				if (!empty($idpentry['description'])) {

					echo '	<p>' . htmlspecialchars($this->t('idpdesc_' . $idpentry['entityid'])) . '<br />';
				}
				echo('<input id="preferredidp" type="submit" name="idp_' .
					htmlspecialchars($idpentry['entityid']) . '" value="' .
					$this->t('select') . '" /></p>');
			}
		}
		echo '</div>';
	
	}
	
		?>
		


</div>
		
		</form>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
