<?php
$this->data['header'] = 'SimpleSAMLphp Statistics';

$this->data['jquery'] = array('core' => true, 'ui' => true, 'css' => true);

$this->data['head'] = '<link rel="stylesheet" type="text/css" href="' . SimpleSAML\Module::getModuleURL("statistics/style.css") . '" />' . "\n";
$this->data['head'] .= '<script type="text/javascript" src="' . SimpleSAML\Module::getModuleURL("statistics/javascript.js") . '"></script>' . "\n";

$this->includeAtTemplateBase('includes/header.php');

echo '<h1>'. $this->data['available.rules'][$this->data['selected.rule']]['name'] . '</h1>';
echo '<p>' . $this->data['available.rules'][$this->data['selected.rule']]['descr'] . '</p>';

// Report settings
echo '<table class="selecttime">';
echo '<tr><td class="selecttime_icon"><img src="' . SimpleSAML\Utils\HTTP::getBaseUrl() . 'resources/icons/crystal_project/kchart.32x32.png" alt="Report settings" /></td>';

// Select report
echo '<td>';
echo '<form action="#">';
echo $this->data['post_rule'];
echo '<select onchange="submit();" name="rule">';
foreach ($this->data['available.rules'] as $key => $rule) {
    if ($key === $this->data['selected.rule']) {
        echo '<option selected="selected" value="' . $key . '">' . $rule['name'] . '</option>';
    } else {
        echo '<option value="' . $key . '">' . $rule['name'] . '</option>';
    }
}
echo '</select></form>';
echo '</td>';

// Select delimiter
echo '<td class="td_right">';
echo '<form action="#">';
echo $this->data['post_d'];
echo '<select onchange="submit();" name="d">';
foreach ($this->data['availdelimiters'] as $key => $delim) {
    $delimName = $delim;
    if (array_key_exists($delim, $this->data['delimiterPresentation'])) {
        $delimName = $this->data['delimiterPresentation'][$delim];
    }

    if ($key == '_') {
        echo '<option value="_">Total</option>';
    } elseif (isset($_REQUEST['d']) && $delim == $_REQUEST['d']) {
        echo '<option selected="selected" value="' . htmlspecialchars($delim) . '">' . htmlspecialchars($delimName) . '</option>';
    } else {
        echo '<option  value="' . htmlspecialchars($delim) . '">' . htmlspecialchars($delimName) . '</option>';
    }
}
echo '</select></form>';
echo '</td></tr>';

echo '</table>';

// End report settings


// Select time and date
echo '<table class="selecttime">';
echo '<tr><td class="selecttime_icon"><img src="' . SimpleSAML\Utils\HTTP::getBaseUrl() . 'resources/icons/crystal_project/date.32x32.png" alt="Select date and time" /></td>';

if (isset($this->data['available.times.prev'])) {
    echo '<td><a href="' . $this->data['get_times_prev'] . '">« Previous</a></td>';
} else {
    echo '<td class="selecttime_link_grey">« Previous</td>';
}

echo '<td class="td_right">';
echo '<form action="#">';
echo $this->data['post_res'];
echo '<select onchange="submit();" name="res">';
foreach ($this->data['available.timeres'] as $key => $timeresname) {
    if ($key == $this->data['selected.timeres']) {
        echo '<option selected="selected" value="' . $key . '">' . $timeresname . '</option>';
    } else {
        echo '<option  value="' . $key . '">' . $timeresname . '</option>';
    }
}
echo '</select></form>';
echo '</td>';

echo '<td class="td_left">';
echo '<form action="#">';
echo $this->data['post_time'];
echo '<select onchange="submit();" name="time">';
foreach ($this->data['available.times'] as $key => $timedescr) {
    if ($key == $this->data['selected.time']) {
        echo '<option selected="selected" value="' . $key . '">' . $timedescr . '</option>';
    } else {
        echo '<option  value="' . $key . '">' . $timedescr . '</option>';
    }
}
echo '</select></form>';
echo '</td>';

if (isset($this->data['available.times.next'])) {
    echo '<td class="td_right td_next_right"><a href="' . $this->data['get_times_next'] . '">Next »</a></td>';
} else {
    echo '<td class="td_right selecttime_link_grey td_next_right">Next »</td>';
}

echo '</tr></table>';


echo '<div id="tabdiv"><ul class="tabset_tabs">
   <li><a href="#graph">Graph</a></li>
   <li><a href="#table">Summary table</a></li>
   <li><a href="#debug">Time serie</a></li>
</ul>';
echo '

<div id="graph" class="tabset_content">';

echo '<img src="' . htmlspecialchars($this->data['imgurl']) . '" alt="Graph" />';

echo '<form action="#">';
echo '<p class="p_right">Compare with total from this dataset ';
echo $this->data['post_rule2'];
echo '<select onchange="submit();" name="rule2">';
echo '	<option value="_">None</option>';
foreach ($this->data['available.rules'] as $key => $rule) {
    if ($key === $this->data['selected.rule2']) {
        echo '<option selected="selected" value="' . $key . '">' . $rule['name'] . '</option>';
    } else {
        echo '<option value="' . $key . '">' . $rule['name'] . '</option>';
    }
}
echo '</select></p></form>';

echo '</div>'; // end graph content.


/**
 * Handle table view - - - - - - 
 */
$classint = array('odd', 'even'); $i = 0;
echo '<div id="table" class="tabset_content">';

if (isset($this->data['pieimgurl'])) {
    echo '<img src="' . $this->data['pieimgurl'] . '" alt="Pie chart" />';
}
echo '<table class="tableview"><tr><th class="value">Value</th><th class="category">Data range</th></tr>';

foreach ($this->data['summaryDataset'] as $key => $value) {
    $clint = $classint[$i++ % 2];

    $keyName = $key;
    if (array_key_exists($key, $this->data['delimiterPresentation'])) {
        $keyName = $this->data['delimiterPresentation'][$key];
    }

    if ($key === '_') {
        echo '<tr class="total '  . $clint . '"><td  class="value">' . $value . '</td><td class="category">' . $keyName . '</td></tr>';
    } else {
        echo '<tr class="' . $clint . '"><td  class="value">' . $value . '</td><td class="category">' . $keyName . '</td></tr>';
    }
}

echo '</table></div>';
//  - - - - - - - End table view - - - - - - - 

echo '<div id="debug" >';
echo '<table class="timeseries">';
echo '<tr><th>Time</th><th>Total</th>';
foreach ($this->data['topdelimiters'] as $key) {
    $keyName = $key;
    if (array_key_exists($key, $this->data['delimiterPresentation'])) {
        $keyName = $this->data['delimiterPresentation'][$key];
    }
    echo'<th>' . $keyName . '</th>';
}
echo '</tr>';


$i = 0;
foreach ($this->data['debugdata'] as $slot => $dd) {
    echo '<tr class="' . ((++$i % 2) == 0 ? 'odd' : 'even') . '">';
    echo '<td>' . $dd[0] . '</td>';
    echo '<td class="datacontent">' . $dd[1] . '</td>';

    foreach ($this->data['topdelimiters'] as $key) {
        echo '<td class="datacontent">' . (array_key_exists($key, $this->data['results'][$slot]) ?
            $this->data['results'][$slot][$key] : '&nbsp;') . '</td>';
    }
    echo '</tr>';
}
echo '</table>';


echo '</div>'; // End debug tab content
echo '</div>'; // End tab div

$this->includeAtTemplateBase('includes/footer.php');
