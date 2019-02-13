<?php

$config = \SimpleSAML\Configuration::getInstance();
$statconfig = \SimpleSAML\Configuration::getConfig('module_statistics.php');
$session = \SimpleSAML\Session::getSessionFromRequest();
$t = new \SimpleSAML\XHTML\Template($config, 'statistics:statistics.tpl.php');

\SimpleSAML\Module\statistics\AccessCheck::checkAccess($statconfig);

/*
 * Check input parameters
 */
$preferRule = null;
$preferRule2 = null;
$preferTime = null;
$preferTimeRes = null;
$delimiter = null;

if (array_key_exists('rule', $_REQUEST)) {
    $preferRule = $_REQUEST['rule'];
}
if (array_key_exists('rule2', $_REQUEST)) {
    $preferRule2 = $_REQUEST['rule2'];
}
if (array_key_exists('time', $_REQUEST)) {
    $preferTime = $_REQUEST['time'];
}
if (array_key_exists('res', $_REQUEST)) {
    $preferTimeRes = $_REQUEST['res'];
}
if (array_key_exists('d', $_REQUEST)) {
    $delimiter = $_REQUEST['d'];
    $t->data['request_d'] = $delimiter;
}

if ($preferRule2 === '_') {
    $preferRule2 = null;
}

/*
 * Create statistics data.
 */
$ruleset = new \SimpleSAML\Module\statistics\Ruleset($statconfig);
$statrule = $ruleset->getRule($preferRule);
$rule = $statrule->getRuleID();

$t->data['pageid'] = 'statistics';
$t->data['header'] = 'stat';
$t->data['available_rules'] = $ruleset->availableRulesNames();
$t->data['selected_rule'] = $rule;
$t->data['selected_rule2'] = $preferRule2;

try {
    $dataset = $statrule->getDataset($preferTimeRes, $preferTime);
    $dataset->setDelimiter($delimiter);
    $dataset->aggregateSummary();
    $dataset->calculateMax();

    if (array_key_exists('output', $_REQUEST) && $_REQUEST['output'] === 'csv') {
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="simplesamlphp-data.csv"');
        $data = $dataset->getDebugData();
        foreach ($data as $de) {
            if (isset($de[1])) {
                echo '"'.$de[0].'",'.$de[1]."\n";
            }
        }
        exit;
    }
} catch (\Exception $e) {
    $t->data['error'] = "No data available";
    $t->show();
    exit;
}

$delimiter = $dataset->getDelimiter();

$timeres = $dataset->getTimeRes();
$fileslot = $dataset->getFileslot();

$timeNavigation = $statrule->getTimeNavigation($timeres, $preferTime);

$piedata = $dataset->getPieData();

$datasets = [];
$datasets[] = $dataset->getPercentValues();

$axis = $dataset->getAxis();

$maxes = [];

$maxes[] = $dataset->getMax();

$t->data['selected_time'] = $fileslot;
$t->data['selected_timeres'] = $timeres;
$t->data['post_d'] = getBaseURL($t, 'post', 'd');

if (isset($preferRule2)) {
    $statrule = $ruleset->getRule($preferRule2);
    try {
        $dataset2 = $statrule->getDataset($preferTimeRes, $preferTime);
        $dataset2->aggregateSummary();
        $dataset2->calculateMax();

        $datasets[] = $dataset2->getPercentValues();
        $maxes[] = $dataset2->getMax();
    } catch (\Exception $e) {
        $t->data['error'] = "No data available to compare";
        $t->show();
        exit;
    }
}

$dimx = $statconfig->getValue('dimension.x', 800);
$dimy = $statconfig->getValue('dimension.y', 350);
$grapher = new \SimpleSAML\Module\statistics\Graph\GoogleCharts($dimx, $dimy);

$t->data['imgurl'] = $grapher->show($axis['axis'], $axis['axispos'], $datasets, $maxes);
if (isset($piedata)) {
    $t->data['pieimgurl'] = $grapher->showPie($dataset->getDelimiterPresentationPie(), $piedata);
}

$t->data['available_rules'] = $ruleset->availableRulesNames();
$t->data['available_times'] = $statrule->availableFileSlots($timeres);
$t->data['available_timeres'] = $statrule->availableTimeRes();
$t->data['available_times_prev'] = $timeNavigation['prev'];
$t->data['available_times_next'] = $timeNavigation['next'];

$t->data['current_rule'] = $t->data['available_rules'][$rule];

$t->data['selected_rule'] = $rule;
$t->data['selected_rule2'] = $preferRule2;
$t->data['selected_delimiter'] = $delimiter;

$t->data['debugdata'] = $dataset->getDebugData();
$t->data['results'] = $dataset->getResults();
$t->data['summaryDataset'] = $dataset->getSummary();
$t->data['topdelimiters'] = $dataset->getTopDelimiters();

$t->data['post_rule'] = getBaseURL($t, 'post', 'rule');
$t->data['post_rule2'] = getBaseURL($t, 'post', 'rule2');
$t->data['post_res'] = getBaseURL($t, 'post', 'res');
$t->data['post_time'] = getBaseURL($t, 'post', 'time');
$t->data['get_times_prev'] = getBaseURL($t, 'get', 'time', $t->data['available_times_prev']);
$t->data['get_times_next'] = getBaseURL($t, 'get', 'time', $t->data['available_times_next']);

$t->data['availdelimiters'] = $dataset->availDelimiters();
$t->data['delimiterPresentation'] = $dataset->getDelimiterPresentation();

$t->data['jquery'] = ['core' => false, 'ui' => true, 'css' => true];

$t->show();

function getBaseURL($t, $type = 'get', $key = null, $value = null)
{
    $vars = [
        'rule' => $t->data['selected_rule'],
        'time' => $t->data['selected_time'],
        'res' => $t->data['selected_timeres'],
    ];
    if (isset($t->data['selected_delimiter'])) {
        $vars['d'] = $t->data['selected_delimiter'];
    }
    if (!empty($t->data['selected_rule2']) && $t->data['selected_rule2'] !== '_') {
        $vars['rule2'] = $t->data['selected_rule2'];
    }

    if (isset($key)) {
        if (isset($vars[$key])) {
            unset($vars[$key]);
        }
        if (isset($value)) {
            $vars[$key] = $value;
        }
    }

    if ($type === 'get') {
        return \SimpleSAML\Module::getModuleURL("statistics/showstats.php").'?'.http_build_query($vars, '', '&amp;');
    }

    return $vars;
}
