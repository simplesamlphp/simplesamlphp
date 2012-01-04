<?php

require_once(dirname(dirname(__FILE__)) . '/extlibinc/base_facebook.php');

/**
 * Extends the BaseFacebook class with the intent of using
 * PHP sessions to store user ids and access tokens.
 */
class sspmod_authfacebook_Facebook extends BaseFacebook
{

  /* SimpleSAMLPhp state array */
  protected $ssp_state;

  /**
   * Identical to the parent constructor, except that
   * we start a PHP session to store the user ID and
   * access token if during the course of execution
   * we discover them.
   *
   * @param Array $config the application configuration.
   * @see BaseFacebook::__construct in base_facebook.php
   */
  public function __construct(array $config, &$ssp_state) {
    $this->ssp_state = &$ssp_state;

    parent::__construct($config);
  }

  protected static $kSupportedKeys =
    array('state', 'code', 'access_token', 'user_id');

  /**
   * Provides the implementations of the inherited abstract
   * methods.  The implementation uses PHP sessions to maintain
   * a store for authorization codes, user ids, CSRF states, and
   * access tokens.
   */
  protected function setPersistentData($key, $value) {
    if (!in_array($key, self::$kSupportedKeys)) {
      SimpleSAML_Logger::debug("Unsupported key passed to setPersistentData: " . var_export($key, TRUE));
      return;
    }

    $session_var_name = $this->constructSessionVariableName($key);
    $this->ssp_state[$session_var_name] = $value;
  }

  protected function getPersistentData($key, $default = false) {
    if (!in_array($key, self::$kSupportedKeys)) {
      SimpleSAML_Logger::debug("Unsupported key passed to getPersistentData: " . var_export($key, TRUE));
      return $default;
    }

    $session_var_name = $this->constructSessionVariableName($key);
    if (isset($this->ssp_state[$session_var_name])) {
      $value = $this->ssp_state[$session_var_name];
    }
    return isset($value) ? $value : $default;
  }

  protected function clearPersistentData($key) {
    if (!in_array($key, self::$kSupportedKeys)) {
      SimpleSAML_Logger::debug("Unsupported key passed to clearPersistentData: " . var_export($key, TRUE));
      return;
    }

    $session_var_name = $this->constructSessionVariableName($key);
    if (isset($this->ssp_state[$session_var_name])) {
      unset($this->ssp_state[$session_var_name]);
    }
  }

  protected function clearAllPersistentData() {
    foreach (self::$kSupportedKeys as $key) {
      $this->clearPersistentData($key);
    }
  }

  protected function constructSessionVariableName($key) {
    return 'authfacebook:authdata:' . implode('_', array('fb',
                              $this->getAppId(),
                              $key));
  }
}
