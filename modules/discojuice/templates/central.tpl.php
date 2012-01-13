<?php

header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>Select Your Login Provider</title>

	<!-- JQuery hosted by Google -->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js" type="text/javascript"></script>

	<!-- DiscoJuice hosted by UNINETT at discojuice.org -->
	<script type="text/javascript" src="https://engine.discojuice.org/discojuice-stable.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://static.discojuice.org/css/discojuice.css" />

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

	echo '
		$("document").ready(function() {
			var djc = DiscoJuice.Hosted.getConfig(' . 
				json_encode($this->data['hostedConfig'][0]) . "," .
				json_encode($this->data['hostedConfig'][1]) . "," . 
				json_encode($this->data['hostedConfig'][2]) .  "," .
				json_encode($this->data['hostedConfig'][3]) .  "," .
				json_encode($this->data['hostedConfig'][4]) .
			');';

	// echo "	djc.country = false;\n";
	// echo "	djc.showLocationInfo = false;\n";
	
	if (!$this->data['enableCentralStorage']) {
		echo "	delete djc.disco;\n";
	}
	if (!empty($this->data['additionalFeeds'])) {
		foreach($this->data['additionalFeeds'] AS $feed) {
			echo "	djc.metadata.push(" . json_encode($feed) . ");\n";
		}
	}
	
	echo "	djc.always = true;\n";
		
	echo '
			$("a.signin").DiscoJuice(djc);
		});
	';

?>



	</script>
	
	
	
</head>
<body style="background: #ccc">

	<p style="display: none; text-align: right"><a class="signin" href="/">signin</a></p>

</body>
</html>


