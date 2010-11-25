<?php
// Copyright 2004-2008 Facebook. All Rights Reserved.
//
// +---------------------------------------------------------------------------+
// | Facebook Platform PHP5 client                                             |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2007 Facebook, Inc.                                         |
// | All rights reserved.                                                      |
// |                                                                           |
// | Redistribution and use in source and binary forms, with or without        |
// | modification, are permitted provided that the following conditions        |
// | are met:                                                                  |
// |                                                                           |
// | 1. Redistributions of source code must retain the above copyright         |
// |    notice, this list of conditions and the following disclaimer.          |
// | 2. Redistributions in binary form must reproduce the above copyright      |
// |    notice, this list of conditions and the following disclaimer in the    |
// |    documentation and/or other materials provided with the distribution.   |
// |                                                                           |
// | THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR      |
// | IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES |
// | OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.   |
// | IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT  |
// | NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY     |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT       |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF  |
// | THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.         |
// +---------------------------------------------------------------------------+
// | For help with this library, contact developers-help@facebook.com          |
// +---------------------------------------------------------------------------+
//

include_once 'facebookapi_php5_restlib.php';

define('FACEBOOK_API_VALIDATION_ERROR', 1);
class Facebook {
  public $api_client;

  public $api_key;
  public $secret;
  public $generate_session_secret;
  public $session_expires;

  public $fb_params;
  public $user;

  public function __construct($api_key, $secret, $generate_session_secret=false) {
    $this->api_key                 = $api_key;
    $this->secret                  = $secret;
    $this->generate_session_secret = $generate_session_secret;
    $this->api_client = new FacebookRestClient($api_key, $secret);

    $this->validate_fb_params();
    if (isset($this->fb_params['friends'])) {
      $this->api_client->friends_list = explode(',', $this->fb_params['friends']);
    }
    if (isset($this->fb_params['added'])) {
      $this->api_client->added = $this->fb_params['added'];
    }
  }

  public function validate_fb_params($resolve_auth_token=true) {
    $this->fb_params = $this->get_valid_fb_params($_POST, 48*3600, 'fb_sig');
    if (!$this->fb_params) {
      $this->fb_params = $this->get_valid_fb_params($_GET, 48*3600, 'fb_sig');
    }
    if ($this->fb_params) {
      // If we got any fb_params passed in at all, then either:
      //  - they included an fb_user / fb_session_key, which we should assume to be correct
      //  - they didn't include an fb_user / fb_session_key, which means the user doesn't have a
      //    valid session and if we want to get one we'll need to use require_login().  (Calling
      //    set_user with null values for user/session_key will work properly.)
      // Note that we should *not* use our cookies in this scenario, since they may be referring to
      // the wrong user.
      $user        = isset($this->fb_params['user'])        ? $this->fb_params['user'] : null;
      $session_key = isset($this->fb_params['session_key']) ? $this->fb_params['session_key'] : null;
      $expires     = isset($this->fb_params['expires'])     ? $this->fb_params['expires'] : null;
      $this->set_user($user, $session_key, $expires);
    } else if (!empty($_COOKIE) && $cookies = $this->get_valid_fb_params($_COOKIE, null, $this->api_key)) {
      // use $api_key . '_' as a prefix for the cookies in case there are
      // multiple facebook clients on the same domain.
      $expires = isset($cookies['expires']) ? $cookies['expires'] : null;
      $this->set_user($cookies['user'], $cookies['session_key'], $expires);
    } else if (isset($_GET['auth_token']) && $resolve_auth_token &&
               $session = $this->do_get_session($_GET['auth_token'])) {
      $session_secret = ($this->generate_session_secret && !empty($session['secret'])) ? $session['secret'] : null;
      $this->set_user($session['uid'], $session['session_key'], $session['expires'], $session_secret);
    }

    return !empty($this->fb_params);
  }

  // Store a temporary session secret for the current session
  // for use with the JS client library
  public function promote_session() {
    try {
      $session_secret = $this->api_client->auth_promoteSession();
      if (!$this->in_fb_canvas()) {
        $this->set_cookies($this->user, $this->api_client->session_key, $this->session_expires, $session_secret);
      }
      return $session_secret;
    } catch (FacebookRestClientException $e) {
      // API_EC_PARAM means we don't have a logged in user, otherwise who
      // knows what it means, so just throw it.
      if ($e->getCode() != FacebookAPIErrorCodes::API_EC_PARAM) {
        throw $e;
      }
    }
  }

  public function do_get_session($auth_token) {
    try {
      return $this->api_client->auth_getSession($auth_token, $this->generate_session_secret);
    } catch (FacebookRestClientException $e) {
      // API_EC_PARAM means we don't have a logged in user, otherwise who
      // knows what it means, so just throw it.
      if ($e->getCode() != FacebookAPIErrorCodes::API_EC_PARAM) {
        throw $e;
      }
    }
  }

  // Invalidate the session currently being used, and clear any state associated with it
  public function expire_session() {
    if ($this->api_client->auth_expireSession()) {
      if (!$this->in_fb_canvas() && isset($_COOKIE[$this->api_key . '_user'])) {
        $cookies = array('user', 'session_key', 'expires', 'ss');
        foreach ($cookies as $name) {
          setcookie($this->api_key . '_' . $name, false, time() - 3600);
          unset($_COOKIE[$this->api_key . '_' . $name]);
        }
        setcookie($this->api_key, false, time() - 3600);
        unset($_COOKIE[$this->api_key]);
      }

      // now, clear the rest of the stored state
      $this->user = 0;
      $this->api_client->session_key = 0;
      return true;
    } else {
      return false;
    }
  }

  public function redirect($url) {
    if ($this->in_fb_canvas()) {
      echo '<fb:redirect url="' . $url . '"/>';
    } else if (preg_match('/^https?:\/\/([^\/]*\.)?facebook\.com(:\d+)?/i', $url)) {
      // make sure facebook.com url's load in the full frame so that we don't
      // get a frame within a frame.
      echo "<script type=\"text/javascript\">\ntop.location.href = \"$url\";\n</script>";
    } else {
      header('Location: ' . $url);
    }
    exit;
  }

  public function in_frame() {
    return isset($this->fb_params['in_canvas']) || isset($this->fb_params['in_iframe']);
  }
  public function in_fb_canvas() {
    return isset($this->fb_params['in_canvas']);
  }

  public function get_loggedin_user() {
    return $this->user;
  }

  public static function current_url() {
    return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '?poot=bar';
  }

  public function require_login($next = NULL) {
  	if ($next === NULL) $next = self::current_url();
    if ($user = $this->get_loggedin_user()) {
      return $user;
    }
    $this->redirect($this->get_login_url($next, $this->in_frame()));
  }

  public function require_install() {
    // this was renamed, keeping for compatibility's sake
    return $this->require_add();
  }

  public function require_add() {
    if ($user = $this->get_loggedin_user()) {
      if ($this->fb_params['added']) {
        return $user;
      }
    }
    $this->redirect($this->get_add_url(self::current_url()));
  }

  public function require_frame() {
    if (!$this->in_frame()) {
      $this->redirect($this->get_login_url(self::current_url(), true));
    }
  }

  public static function get_facebook_url($subdomain='www') {
    return 'http://' . $subdomain . '.facebook.com';
  }

  public function get_install_url($next=null) {
    // this was renamed, keeping for compatibility's sake
    return $this->get_add_url($next);
  }

  public function get_add_url($next=null) {
    return self::get_facebook_url().'/add.php?api_key='.$this->api_key .
      ($next ? '&next=' . urlencode($next) : '');
  }

  public function get_login_url($next, $canvas) {
    return self::get_facebook_url().'/login.php?v=1.0&api_key=' . $this->api_key .
      ($next ? '&next=' . urlencode($next)  : '') .
      '&req_perms=email' .
      ($canvas ? '&canvas' : '');
  }

  public static function generate_sig($params_array, $secret) {
    $str = '';

    ksort($params_array);
    // Note: make sure that the signature parameter is not already included in
    //       $params_array.
    foreach ($params_array as $k=>$v) {
      $str .= "$k=$v";
    }
    $str .= $secret;

    return md5($str);
  }

  public function set_user($user, $session_key, $expires=null, $session_secret=null) {
    if (!$this->in_fb_canvas() && (!isset($_COOKIE[$this->api_key . '_user'])
                                   || $_COOKIE[$this->api_key . '_user'] != $user)) {
      $this->set_cookies($user, $session_key, $expires, $session_secret);
    }
    $this->user = $user;
    $this->api_client->session_key = $session_key;
    $this->session_expires = $expires;
  }

  public function set_cookies($user, $session_key, $expires=null, $session_secret=null) {
    $cookies = array();
    $cookies['user'] = $user;
    $cookies['session_key'] = $session_key;
    if ($expires != null) {
      $cookies['expires'] = $expires;
    }
    if ($session_secret != null) {
      $cookies['ss'] = $session_secret;
    }
    foreach ($cookies as $name => $val) {
      setcookie($this->api_key . '_' . $name, $val, (int)$expires);
      $_COOKIE[$this->api_key . '_' . $name] = $val;
    }
    $sig = self::generate_sig($cookies, $this->secret);
    setcookie($this->api_key, $sig, (int)$expires);
    $_COOKIE[$this->api_key] = $sig;
  }

  /**
   * Tries to undo the badness of magic quotes as best we can
   * @param     string   $val   Should come directly from $_GET, $_POST, etc.
   * @return    string   val without added slashes
   */
  public static function no_magic_quotes($val) {
    if (get_magic_quotes_gpc()) {
      return stripslashes($val);
    } else {
      return $val;
    }
  }

  public function get_valid_fb_params($params, $timeout=null, $namespace='fb_sig') {
    $prefix = $namespace . '_';
    $prefix_len = strlen($prefix);
    $fb_params = array();
    foreach ($params as $name => $val) {
      if (strpos($name, $prefix) === 0) {
        $fb_params[substr($name, $prefix_len)] = self::no_magic_quotes($val);
      }
    }
    if ($timeout && (!isset($fb_params['time']) || time() - $fb_params['time'] > $timeout)) {
      return array();
    }
    if (!isset($params[$namespace]) || (!$this->verify_signature($fb_params, $params[$namespace]))) {
      return array();
    }
    return $fb_params;
  }

  public function verify_signature($fb_params, $expected_sig) {
    return self::generate_sig($fb_params, $this->secret) == $expected_sig;
  }

  public function encode_validationError($summary, $message) {
    return json_encode(
               array('errorCode'    => FACEBOOK_API_VALIDATION_ERROR,
                     'errorTitle'   => $summary,
                     'errorMessage' => $message));
  }

  public function encode_multiFeedStory($feed, $next) {
    return json_encode(
               array('method'   => 'multiFeedStory',
                     'content'  =>
                     array('next' => $next,
                           'feed' => $feed)));
  }

  public function encode_feedStory($feed, $next) {
    return json_encode(
               array('method'   => 'feedStory',
                     'content'  =>
                     array('next' => $next,
                           'feed' => $feed)));
  }

  public function create_templatizedFeedStory($title_template, $title_data=array(),
                                    $body_template='', $body_data = array(), $body_general=null,
                                    $image_1=null, $image_1_link=null,
                                    $image_2=null, $image_2_link=null,
                                    $image_3=null, $image_3_link=null,
                                    $image_4=null, $image_4_link=null) {
    return array('title_template'=> $title_template,
                 'title_data'   => $title_data,
                 'body_template'=> $body_template,
                 'body_data'    => $body_data,
                 'body_general' => $body_general,
                 'image_1'      => $image_1,
                 'image_1_link' => $image_1_link,
                 'image_2'      => $image_2,
                 'image_2_link' => $image_2_link,
                 'image_3'      => $image_3,
                 'image_3_link' => $image_3_link,
                 'image_4'      => $image_4,
                 'image_4_link' => $image_4_link);
  }


}

