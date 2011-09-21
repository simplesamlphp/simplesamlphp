<?php


$version = '0.1-4';
header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');



?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>Select Your Login Provider</title>
	

<?php

echo '<link rel="shortcut icon" href="' . SimpleSAML_Module::getModuleURL('discojuice/favicon.png') . '" />

';


echo '<!-- JQuery -->';
echo '<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/jquery-1.6.min.js') . '"></script>
<!-- script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/jquery-ui-1.8.5.custom.min.js') . '"></script -->
<!-- link rel="stylesheet" type="text/css" href="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/css/custom/jquery-ui-1.8.5.custom.css') . '" / -->

';


echo '<!-- DiscoJuice -->
<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/discojuice.misc.js?v=' . $version ) . '"></script>
<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/discojuice.ui.js?v=' . $version) . '"></script>
<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/discojuice.control.js?v=' . $version) . '"></script>

<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/idpdiscovery.js?v=' . $version) . '"></script>

<link rel="stylesheet" type="text/css" href="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/css/discojuice.css?v=' . $version) . '" />';

?>

	<style type="text/css">
		
		body {
			text-align: center;
		}
		div.discojuice {
			text-align: left;
			position: relative;
			width: 600px;
			margin-right: auto;
			margin-left: auto;
			
		}
		
	</style>

	<script type="text/javascript">
<?php

global $options;
global $returnidparam, $returnto;
$options = $this->data['discojuice.options'];

if (!empty($_REQUEST['entityID'])) {
	if (!array_key_exists('disco', $options)) {		
		$options['disco'] = array();
	}
	$options['disco']['spentityid'] = $_REQUEST['entityID'];
}

echo 'var options = ' . json_encode($options) . ';' . "\n\n";

echo 'options.countryAPI = "' . SimpleSAML_Module::getModuleURL('discojuice/country.php'). '"; ' . "\n";

if (empty($options['metadata'])) {
	echo 'options.metadata = "' . SimpleSAML_Module::getModuleURL('discojuice/feed.php'). '"; ' . "\n";
}

if (!empty($options['disco'])) {
	echo 'options.disco.url = "' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/discojuiceDiscoveryResponse.html?'). '"; ' . "\n";
}




if (empty($options['discoPath'])) {
	echo 'options.discoPath = "discojuice/"; ' . "\n";
	$options['discoPath'] = "discojuice/";
	
}

echo 'var acl = ' . json_encode($this->data['acl']) . ';' . "\n";
echo 'acl.push("' . SimpleSAML_Utilities::getSelfHost() . '");' . "\n\n";

SimpleSAML_Logger::info('Icon URL is: ' . $options['discoPath'] );

?>
		
		IdPDiscovery.setup(options, acl);
	</script>
	
	
	
</head>
<body style="background: #ccc">

<p style="text-align: right"><a class="signin" href="/"></a></p>
<div class="noscript">
<?php


$metadata = $this->data['metadata'];

function cmp($a, $b) {
	$xa = isset($a['weight']) ? $a['weight'] : 0;
	$xb = isset($b['weight']) ? $b['weight'] : 0;
	return ($xa-$xb);
}
usort($metadata, 'cmp');



$spentityid = !empty($_REQUEST['entityID']) ? $_REQUEST['entityID'] : null;
$returnidparam = !empty($_REQUEST['returnIDParam']) ? $_REQUEST['returnIDParam'] : 'entityID';
$returnto = !empty($_REQUEST['return']) ? $_REQUEST['return'] : null;



function show($item) {
	
	global $returnidparam, $returnto;
	global $options; 
	
	$iconPath = $options['discoPath'] . 'logos/';
	
	if (empty($item['entityID'])) {
		SimpleSAML_Logger::warning('Missing entityID on item to show in central discovery service...');
		return;
	}
	
	$href = $returnto . '&' . $returnidparam . '=' . urlencode($item['entityID']);
	if (!empty($item['icon'])) {
		echo '<a href="' . htmlspecialchars($href) . '" class="">' . 
			'<img src="' . htmlspecialchars($iconPath . $item['icon']) . '" />' .
			'<span class="title">' . htmlspecialchars($item['title']) . '</span>' . 
			'<span class="substring">' . (!empty($item['descr']) ? htmlspecialchars($item['descr']) : '') . '</span>' .
			'<hr style="clear: both; height: 0px; visibility:hidden" /></a>';

	} else {
		echo '<a href="' . htmlspecialchars($href) . '" class="">' . 
			'<span class="title">' . htmlspecialchars($item['title']) . '</span>' . 
			'<span class="substring">' . (!empty($item['descr']) ? htmlspecialchars($item['descr']) : '') . '</span></a>';
	}

}


echo '<div style="display: block" class="discojuice">
		<div class="top">
			<a href="#" class="discojuice_close">&nbsp;</a>
			<p class="discojuice_maintitle">Sign in</p>
			<p class="discojuice_subtitle">Select your login provider</p>
		</div>
		<div id="content" style="">
			<p class="moretext"></p>
			<div class="scroller">';

	foreach($metadata AS $item) {
		show($item);
	}

	
	echo '</div>
		</div>
		<div class="filters bottom">
			<p>You have disabled Javascript in your browser &mdash; therefore there user interface for selecting your provider is
			lacking some features. You may still use browser inline search to easier locate your provider on the list.</p>
		</div>
	</div>';



?>
</div>
</body>
</html>
















