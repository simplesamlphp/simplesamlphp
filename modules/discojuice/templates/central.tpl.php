<?php

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
echo '<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/jquery-1.4.3.min.js') . '"></script>
<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/jquery-ui-1.8.5.custom.min.js') . '"></script>

<link rel="stylesheet" type="text/css" href="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/css/custom/jquery-ui-1.8.5.custom.css') . '" />


';



echo '<!-- DiscoJuice -->
<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/discojuice.misc.js') . '"></script>
<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/discojuice.ui.js') . '"></script>
<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/discojuice.control.js') . '"></script>

<script type="text/javascript" language="javascript" src="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/idpdiscovery.js') . '"></script>

<link rel="stylesheet" type="text/css" href="' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/css/discojuice.css') . '" />';

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


$options = $this->data['discojuice.options'];

echo 'var options = ' . json_encode($options) . ';' . "\n\n";

echo 'options.countryAPI = "' . SimpleSAML_Module::getModuleURL('discojuice/country.php'). '"; ' . "\n";
echo 'options.metadata = "' . SimpleSAML_Module::getModuleURL('discojuice/feed.php'). '"; ' . "\n";

echo 'options.disco = { url: "' . SimpleSAML_Module::getModuleURL('discojuice/discojuiceDiscoveryResponse.html?'). '" }; ' . "\n";
echo 'options.discoPath = "discojuice/"; ' . "\n";

echo 'var acl = ' . json_encode($this->data['acl']) . ';' . "\n";
echo 'acl.push("' . SimpleSAML_Utilities::getSelfHost() . '");' . "\n\n";

?>
		
		IdPDiscovery.receive();
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
	
	$iconPath = 'discojuice/logos/';
	
	$href = $returnto . '&' . $returnidparam . '=' . urlencode($item['entityid']);
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
	
// 	echo '<pre>';
// 	print_r($metadata);
// 	echo '</pre>';
	
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
















