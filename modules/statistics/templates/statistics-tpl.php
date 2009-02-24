<?php
$this->data['header'] = 'SimpleSAMLphp Statistics';

$this->data['head']  = '<script type="text/javascript" src="/' . $this->data['baseurlpath'] . 'resources/jquery.js"></script>';
$this->data['head'] .= '<script type="text/javascript" src="/' . $this->data['baseurlpath'] . 'resources/jquery-ui.js"></script>';
$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" href="/' . $this->data['baseurlpath'] . 'resources/uitheme/jquery-ui-themeroller.css" />';

$this->data['head'] .= '<script type="text/javascript">

$(document).ready(function() {
	$("#tabdiv > ul").tabs();
});
</script>';


$this->includeAtTemplateBase('includes/header.php');

?>

	<style type="text/css" media="all">
.ui-tabs-panel { padding: .5em }

.tableview {
	border-collapse: collapse;
	border: 1px solid #ccc;
	margin: 1em;
	width: 80%;
}
.tableview th, .tableview td{
	border: 1px solid: #ccc;
	padding: 0px 5px;
}
.tableview th {
	background: #e5e5e5;
}
.tableview tr.total td {
	color: #500; font-weight: bold;
}
.tableview tr.even td {
	background: #f5f5f5;
	border-top: 1px solid #e0e0e0;
	border-bottom: 1px solid #e0e0e0;
}
.tableview th.value, .tableview td.value {
	text-align: right;
}
	</style>

<?php
echo('<h1>'. $this->data['available.rules'][$this->data['selected.rule']]['name'] . '</h1>');
echo('<p>' . $this->data['available.rules'][$this->data['selected.rule']]['descr'] . '</p>');

echo '<div class="selecttime" style="border: 1px solid #ccc; background: #eee; margin: 5px 0px; padding: .5em">';
echo '<div style="display: inline">';
echo '<form style="display: inline"><select onChange="submit();" name="rule">';
foreach ($this->data['available.rules'] AS $key => $rule) {
	if ($key === $this->data['selected.rule']) {
		echo '<option selected="selected" value="' . $key . '">' . $rule['name'] . '</option>';
	} else {
		echo '<option value="' . $key . '">' . $rule['name'] . '</option>';
	}
}
echo '</select></form>';
echo '</div>';




echo '<div style="display: inline">';
echo '<form style="display: inline">';
echo '<input type="hidden" name="rule" value="' . $this->data['selected.rule'] . '" />';
echo '<select onChange="submit();" name="time">';
foreach ($this->data['available.times'] AS $key => $timedescr) {
	if ($key == $this->data['selected.time']) {
		echo '<option selected="selected" value="' . $key . '">' . $timedescr . '</option>';
	} else {
		echo '<option  value="' . $key . '">' . $timedescr . '</option>';
	}
}
echo '</select></form>';
echo '</div>';



echo '<div style="display: inline">';
echo '<form style="display: inline">';
echo '<input type="hidden" name="rule" value="' . $this->data['selected.rule'] . '" />';
echo '<input type="hidden" name="time" value="' . $this->data['selected.time'] . '" />';
echo '<select onChange="submit();" name="d">';
foreach ($this->data['availdelimiters'] AS $key => $delim) {
	if ($key == '_') {
		echo '<option value="_">Total</option>';
	} elseif ($delim == $_REQUEST['d']) {
		echo '<option selected="selected" value="' . $delim . '">' . $delim . '</option>';
	} else {
		echo '<option  value="' . $delim . '">' . $delim . '</option>';
	}
}
echo '</select></form>';
echo '</div>';


echo '<div style="clear: both; height: 0px"></div>';
echo '</div>';


echo '<div id="tabdiv"><ul class="tabset_tabs">
   <li><a href="#graph">Graph</a></li>
   <li><a href="#table">Table</a></li>
   <li><a href="#debug">Debug</a></li>
</ul>';
echo '

<div id="graph" class="tabset_content">';


echo '<img src="' . htmlspecialchars($this->data['imgurl']) . '" />';

echo '</div>'; # end graph content.



/**
 * Handle table view - - - - - - 
 */
$classint = array('odd', 'even'); $i = 0;
echo '<div id="table" class="tabset_content">

<table class="tableview"><tr><th class="value">Value</th><th class="category">Data range</th>';
foreach ( $this->data['summaryDataset'] as $key => $value ) {
	$clint = $classint[$i++ % 2];
	if ($key === '_') {
	    echo '<tr class="total ' . $clint . '"><td  class="value">' . $value . '</td><td class="category">Total</td></tr>';
    } else {
	    echo '<tr class="' . $clint . '"><td  class="value">' . $value . '</td><td class="category">' . $key . '</td></tr>';
    }
}
echo '</table></div>';
//  - - - - - - - End table view - - - - - - - 



 
 


echo '<div id="debug" >';



#echo $this->data['selected.time'];




echo '<input style="width: 80%" value="' . htmlspecialchars($this->data['imgurl']) . '" />';

echo '<table style="">';
foreach ($this->data['debugdata'] AS $dd) {
	echo '<tr><td style="padding-right: 2em; border: 1px solid #ccc">' . $dd[0] . '</td><td style="padding-right: 2em; border: 1px solid #ccc">' . $dd[1] . '</td></tr>';
}
echo '</table>';


echo '</div>'; # End debug tab content
echo('</div>'); # End tab div




// 
// if (count($this->data['sources']) === 0) {
// 	echo('<p>' . $this->t('{aggregator:dict:no_aggregators}') . '</p>');
// } else {
// 
// 	echo('<ul>');
// 
// 	foreach ($this->data['sources'] as $source) {
// 		$encId = urlencode($source);
// 		$encName = htmlspecialchars($source);
// 		echo('<li>');
// 		echo('<a href="?id=' . $encId . '">' . $encName . '</a>');
// 		echo(' <a href="?id=' . $encId . '&amp;mimetype=text/plain">[' . $this->t('{aggregator:dict:text}') . ']</a>');
// 		echo(' <a href="?id=' . $encId . '&amp;mimetype=application/xml">[xml]</a>');
// 		echo('</li>');
// 	}
// 
// 	echo('</ul>');
// }

$this->includeAtTemplateBase('includes/footer.php');
?>
