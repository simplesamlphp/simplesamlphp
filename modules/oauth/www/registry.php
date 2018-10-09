<?php
// Load SimpleSAMLphp, configuration and metadata
$config = \SimpleSAML\Configuration::getInstance();
$session = \SimpleSAML\Session::getSessionFromRequest();
$oauthconfig = \SimpleSAML\Configuration::getOptionalConfig('module_oauth.php');

$store = new \SimpleSAML\Module\core\Storage\SQLPermanentStorage('oauth');

$authsource = "admin"; // force admin to authenticate as registry maintainer
$useridattr = $oauthconfig->getValue('useridattr', 'user');

if ($session->isValid($authsource)) {
    $attributes = $session->getAuthData($authsource, 'Attributes');
    // Check if userid exists
    if (!isset($attributes[$useridattr])) {
        throw new \Exception('User ID is missing');
    }
    $userid = $attributes[$useridattr][0];
} else {
    $as = \SimpleSAML\Auth\Source::getById($authsource);
    $as->initLogin(\SimpleSAML\Utils\HTTP::getSelfURL());
}

function requireOwnership($entry, $userid)
{
    if (!isset($entry['owner'])) {
        throw new \Exception('OAuth Consumer has no owner. Which means no one is granted access, not even you.');
    }
    if ($entry['owner'] !== $userid) {
        throw new \Exception(
            'OAuth Consumer has an owner that is not equal to your userid, hence you are not granted access.'
        );
    }
}

if (isset($_REQUEST['delete'])) {
    $entryc = $store->get('consumers', $_REQUEST['delete'], '');
    $entry = $entryc['value'];

    requireOwnership($entry, $userid);
    $store->remove('consumers', $entry['key'], '');
}

$list = $store->getList('consumers');

$slist = ['mine' => [], 'others' => []];
if (is_array($list)) {
    foreach ($list as $listitem) {
        if (array_key_exists('owner', $listitem['value'])) {
            if ($listitem['value']['owner'] === $userid) {
                $slist['mine'][] = $listitem;
                continue;
            }
        }
    }
    $slist['others'][] = $listitem;
}

$template = new \SimpleSAML\XHTML\Template($config, 'oauth:registry.list.php');
$template->data['entries'] = $slist;
$template->data['userid'] = $userid;
$template->show();
