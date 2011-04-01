<?php

/**
 * ...
 */
class sspmod_discojuice_EmbedHelper {
	
	public static function head($includeJQuery = TRUE) {
		
		$version = '0.1';
		
		$config = SimpleSAML_Configuration::getInstance();
		$djconfig = SimpleSAML_Configuration::getOptionalConfig('discojuiceembed.php');
		
			
		if ($includeJQuery) {	
			echo '<!-- JQuery -->';
			echo '<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/jquery-1.4.3.min.js') . '"></script>
			<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/jquery-ui-1.8.5.custom.min.js') . '"></script>
			
			<link rel="stylesheet" type="text/css" href="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/css/custom/jquery-ui-1.8.5.custom.css') . '" />';
		}
		
		
		echo '<!-- DiscoJuice -->
		<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/discojuice.misc.js?v=' . $version) . '"></script>
		<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/discojuice.ui.js?v=' . $version) . '"></script>
		<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/discojuice.control.js?v=' . $version) . '"></script>
		
		<link rel="stylesheet" type="text/css" href="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/css/discojuice.css?v=' . $version) . '" />';
	
		
		$options = $djconfig->getValue('discojuice.options');
		$target = $djconfig->getValue('target');
		

		echo '<script type="text/javascript">';
		echo 'var options = ' . json_encode($options) . ';' . "\n";
		echo 'var target = "' . $target . '";' . "\n\n";
		
		echo 'options.countryAPI = "' . SimpleSAML_Module::getModuleURL('discojuice/country.php'). '"; ' . "\n";
		echo 'options.metadata = "' . SimpleSAML_Module::getModuleURL('discojuice/feed.php'). '"; ' . "\n";
		
		echo 'options.disco = { url: "' . SimpleSAML_Module::getModuleURL('discojuice/discojuiceDiscoveryResponse.html?'). '" }; ' . "\n";
		echo 'options.discoPath = "' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/') . '"; ' . "\n";
		
		
		echo 'options.callback = ' . $djconfig->getValue('callback', 'null') . ';' . "\n\n";
			

		echo '
			$(document).ready(function() {
				$(target).DiscoJuice(options);
			});
		</script>
		
		';
		
		
	}
	
	
	

	

}

