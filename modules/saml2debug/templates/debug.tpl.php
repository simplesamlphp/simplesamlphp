<?php

$this->data['head']  = '<script type="text/javascript" src="/' . $this->data['baseurlpath'] . 'resources/jquery.js"></script>';
$this->data['head'] .= '<script type="text/javascript" src="/' . $this->data['baseurlpath'] . 'resources/jquery-ui.js"></script>';
$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" href="/' . $this->data['baseurlpath'] . 'resources/uitheme/jquery-ui-themeroller.css" />';

$this->data['head'] .= '<script type="text/javascript">

$(document).ready(function() {
	$("#tabdiv > ul").tabs({ selected: ' . $this->data['activeTab'] . ' });
});
</script>';

$this->data['header'] = 'SAML 2.0 Debugger';
$this->includeAtTemplateBase('includes/header.php');
?>



<div id="tabdiv">
<ul>
	<li><a href="#decode">Decode</a></li>
	<li><a href="#encode">Encode</a></li>
</ul>


<div id="decode">

	<p>Paste in a SAML message encoded with the HTTP-POST or HTTP-REDIRECT encoding. You can both use the full URL that you copied from LiveHTTPHeaders, or you can paste in only the SAMLRequest or SAMLResponse parameter. It will be automatically detected whether you post an URL or the value it self and whether you post a HTTP-REDIRECT or HTTP-POST encoded value. enjoy!</p>
	
	<form method="post" action="debug.php">
		<textarea style="width: 95%; border: 1px solid #999; font-family: monospace" cols="50" rows="10" name="encoded"><?php echo $this->data['encoded']; ?></textarea>
		<p><input type="submit" name="decode" value="Decode SAML message »" /></p>
	</form>

</div> <!-- #redirect -->

<div id="encode">

	<p>Type in the SAML Message below, and select which binding to use.</p>
	
	<form method="post" action="debug.php">
		<textarea style="width: 95%; border: 1px solid #999" cols="50" rows="20" name="decoded"><?php echo $this->data['decoded']; ?></textarea>

		<div style="margin: 1em">
			Use this binding: 
			<select name="binding">
				<option value="redirect">HTTP-REDIRECT</option>
				<option value="post">HTTP-POST</option>
			</select>
		</div>
		
		<p><input type="submit" name="decode" value="« Encode SAML message" /></p>
	</form>

</div> <!-- #redirect -->




</div> <!-- #tabdiv -->





<?php $this->includeAtTemplateBase('includes/footer.php'); ?>