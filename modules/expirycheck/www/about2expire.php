<?php

/**
 * about2expire.php
 *
 * @package SimpleSAMLphp
 */

SimpleSAML\Logger::info('expirycheck - User has been warned that NetID is near to expirational date.');

if (!array_key_exists('StateId', $_REQUEST)) {
    throw new SimpleSAML_Error_BadRequest('Missing required StateId query parameter.');
}
$id = $_REQUEST['StateId'];
$state = SimpleSAML_Auth_State::loadState($id, 'expirywarning:about2expire');

if (array_key_exists('yes', $_REQUEST)) {
    // The user has pressed the yes-button
    SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);
}

$globalConfig = SimpleSAML_Configuration::getInstance();

$daysleft = $state['daysleft'];

$t = new SimpleSAML_XHTML_Template($globalConfig, 'expirycheck:about2expire.php');
$t->data['autofocus'] = 'yesbutton';
$t->data['yesTarget'] = SimpleSAML\Module::getModuleURL('expirycheck/about2expire.php');
$t->data['yesData'] = array('StateId' => $id);
$t->data['warning'] = $warning;
$t->data['expireOnDate'] = $state['expireOnDate'];
$t->data['netId'] = $state['netId'];

if ($daysleft == 0) {
    # netid will expire today
    $this->data['header'] = $this->t('{expirycheck:expwarning:warning_header_today}', array(
                                '%NETID%' => htmlspecialchars($this->data['netId'])
                        ));
    $this->data['warning'] = $this->t('{expirycheck:expwarning:warning_today}', array(
                                '%NETID%' => htmlspecialchars($this->data['netId'])
                        ));
} elseif ($daysleft == 1) {
    # netid will expire in one day

    $this->data['header'] = $this->t('{expirycheck:expwarning:warning_header}', array(
                                '%NETID%' => htmlspecialchars($this->data['netId']),
                                '%DAYS%' => $this->t('{expirycheck:expwarning:day}'),
                                '%DAYSLEFT%' => htmlspecialchars($daysleft),
                        ));
    $this->data['warning'] = $this->t('{expirycheck:expwarning:warning}', array(
                                '%NETID%' => htmlspecialchars($this->data['netId']),
                                '%DAYS%' => $this->t('{expirycheck:expwarning:day}'),
                                '%DAYSLEFT%' => htmlspecialchars($daysleft),
                        ));
} else {
    # netid will expire in next <daysleft> days
    $this->data['header'] = $this->t('{expirycheck:expwarning:warning_header}', array(
                                '%NETID%' => htmlspecialchars($this->data['netId']),
                                '%DAYS%' => $this->t('{expirycheck:expwarning:days}'),
                                '%DAYSLEFT%' => htmlspecialchars($daysleft),
                        ));
    $this->data['warning'] = $this->t('{expirycheck:expwarning:warning}', array(
                                '%NETID%' => htmlspecialchars($this->data['netId']),
                                '%DAYS%' => $this->t('{expirycheck:expwarning:days}'),
                                '%DAYSLEFT%' => htmlspecialchars($daysleft),
                        ));
}

$t->show();
