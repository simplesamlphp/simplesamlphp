<?php

if(!array_key_exists('header', $this->data)) {
	$this->data['header'] = 'selectidp';
}
$this->data['header'] = $this->t($this->data['header']);

$this->data['head']  = '<script type="text/javascript" src="' . $this->data['baseurlpath'] . 'resources/jquery.js"></script>';
$this->data['head'] .= '<script type="text/javascript" src="' . $this->data['baseurlpath'] . 'resources/jquery-ui.js"></script>';

$this->data['head'] .= '<script type="text/javascript">

$(document).ready(function() {
	$("#discotabs > ul").tabs();
	/*
		$("#foodledescr").resizable({ 
			handles: "all" 
		});
	*/
});
</script>';





$this->data['autofocus'] = 'preferredidp';

$this->includeAtTemplateBase('includes/header.php');

foreach ($this->data['idplist'] AS $idpentry) {
	if (isset($idpentry['name']))
		$this->includeInlineTranslation('idpname_' . $idpentry['entityid'], $idpentry['name']);
	if (isset($idpentry['description']))
		$this->includeInlineTranslation('idpdesc_' . $idpentry['entityid'], $idpentry['description']);
}


?>


		<h2><?php echo $this->data['header']; ?></h2>
		
		<h2>h2</h2>

<h2>FOO</h2>
		<form method="get" action="<?php echo $this->data['urlpattern']; ?>">
		<input type="hidden" name="entityID" value="<?php echo htmlspecialchars($this->data['entityID']); ?>" />
		<input type="hidden" name="return" value="<?php echo htmlspecialchars($this->data['return']); ?>" />
		<input type="hidden" name="returnIDParam" value="<?php echo htmlspecialchars($this->data['returnIDParam']); ?>" />
		
		<p><?php
		echo $this->t('selectidp_full');
		if($this->data['rememberenabled']) {
			echo('<br /><input type="checkbox" name="remember" value="1" />' . $this->t('remember'));
		}
		?></p>


<div id="foodletabs"> 
     <!--
        <input type="button" onclick="$('#tabsEx1 > ul').tabs('add', '#appended-tab', 'New Tab');" value="Add new tab"> 
        <input type="button" onclick="$('#tabsEx1 > ul').tabs('add', '#inserted-tab', 'New Tab', 1);" value="Insert tab"> 
        <input type="button" onclick="$('#tabsEx1 > ul').tabs('disable', 1);" value="Disable tab 2"> 
        <input type="button" onclick="$('#tabsEx1 > ul').tabs('enable', 1);" value="Enable tab 2"> 
        <input type="button" onclick="$('#tabsEx1 > ul').tabs('select', 2);" value="Select tab 3"> 
         -->
    <ul style="height: 30px;"> 
        <li><a href="#fdescr"><span>Foodle description</span></a></li> 
        <li><a href="#fcols"><span>Setup columns</span></a></li> 
        <li><a href="#preview"><span>Preview</span></a></li> 
        <li><a href="#advanced"><span>Advanced options</span></a></li>
    </ul> 
    <div id="fdescr"> 

		<?php

		
		if (!empty($this->data['preferredidp']) && array_key_exists($this->data['preferredidp'], $this->data['idplist'])) {
			$idpentry = $this->data['idplist'][$this->data['preferredidp']];
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
		
		
		foreach ($this->data['idplist'] AS $idpentry) {
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
		
		?>
		
		
</div>
<div>
</div>
		
		</form>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
