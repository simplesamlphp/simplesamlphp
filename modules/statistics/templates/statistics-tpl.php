<?php
$this->data['header'] = 'Statistics';
$this->includeAtTemplateBase('includes/header.php');

echo '<script type="text/javascript" src="/' . $this->data['baseurlpath'] . 'resources/js/tabs/addclasskillclass.js"></script>';
echo '<script type="text/javascript" src="/' . $this->data['baseurlpath'] . 'resources/js/tabs/attachevent.js"></script>';
echo '<script type="text/javascript" src="/' . $this->data['baseurlpath'] . 'resources/js/tabs/addcss.js"></script>';
echo '<script type="text/javascript" src="/' . $this->data['baseurlpath'] . 'resources/js/tabs/tabtastic.js"></script>';


?>

	<style type="text/css" media="all">
#content .tabset_tabs	{ 
	margin:0; padding:0; 
	list-style-type:none; position:relative; z-index:2; white-space:nowrap 
}
#content .tabset_tabs li	{ 
	margin:0; 
	padding: 0px; 
	display:inline;
	font-family: sans-serif; 
	font-size: medium;
	font-weight: normal;
}
#content .tabset_tabs a	{ 
	color:#bbb ! important; 
	background-color:#e8e8e8 ! important; 
	border:1px solid #aaa; text-decoration:none; 
	padding:0 2em;

/*	border-left-width:1px;  */
	border-bottom:none 
}
#content .tabset_tabs a:hover { 
	color:#666; 
	background-color:#eee; 
}
#content .tabset_tabs a.active { 
	color:black ! important; background-color:white ! important; border-color:black; border-left-width:1px; cursor:default; border-bottom:white; padding-top:1px; padding-bottom:1px;
}

#content .tabset_tabs li.firstchild a	{ border-left-width:1px }

#content .tabset_content	{ 
	border:1px solid black; background-color:white; position:relative; z-index:1; padding:0.5em 1em; display:none;
	top: -3px;
}
#content .tabset_label	{ display:none }

#content .tabset_content_active	{ display:block }


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
echo('<div id="content">');
echo('<h1>'. $this->data['available.rules'][$this->data['selected.rule']]['name'] . '</h1>');
echo('<p>' . $this->data['available.rules'][$this->data['selected.rule']]['descr'] . '</p>');

echo '<div class="selecttime" style="border: 1px solid #999; background: #ccc; margin: .5em; padding: .5em">';
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


echo '<br style="clear: both; height: 0px">';
echo '</div>';


echo '<ul class="tabset_tabs">
   <li><a href="#graph" class="active">Graph</a></li>
   <li><a href="#table">Table</a></li>
   <li><a href="#debug">Debug</a></li>
</ul>';
echo '

<div id="graph" class="tabset_content">
   <h2 class="tabset_label">Graph</h2>
';


echo '<img src="' . htmlspecialchars($this->data['imgurl']) . '" />';




echo '</div>'; # end graph content.



/**
 * Handle table view - - - - - - 
 */
$classint = array('odd', 'even'); $i = 0;
echo '<div id="table" class="tabset_content">
   <h2 class="tabset_label">Table</h2>
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



 
 


echo '<div id="debug" class="tabset_content" style="max-height: 400px; overflow-y: scroll">
   <h2 class="tabset_label">Debug</h2>
';



#echo $this->data['selected.time'];




echo '<input style="width: 80%" value="' . htmlspecialchars($this->data['imgurl']) . '" />';

echo '<table style="">';
foreach ($this->data['debugdata'] AS $dd) {
	echo '<tr><td style="padding-right: 2em; border: 1px solid #ccc">' . $dd[0] . '</td><td style="padding-right: 2em; border: 1px solid #ccc">' . $dd[1] . '</td></tr>';
}
echo '</table>';


echo '</div>'; # End debug tab content




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