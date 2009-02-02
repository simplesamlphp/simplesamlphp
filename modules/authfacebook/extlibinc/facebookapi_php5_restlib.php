<?php
// Copyright 2004-2008 Facebook. All Rights Reserved.
//
// +---------------------------------------------------------------------------+
// | Facebook Platform PHP5 client                                             |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2007-2008 Facebook, Inc.                                    |
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

class FacebookRestClient {
  public $secret;
  public $session_key;
  public $api_key;
  public $friends_list; // to save making the friends.get api call, this will get prepopulated on canvas pages
  public $added;        // to save making the users.isAppAdded api call, this will get prepopulated on canvas pages
  public $batch_mode;
  private $batch_queue;
  private $call_as_apikey;

  const BATCH_MODE_DEFAULT = 0;
  const BATCH_MODE_SERVER_PARALLEL = 0;
  const BATCH_MODE_SERIAL_ONLY = 2;

  /**
   * Create the client.
   * @param string $session_key if you haven't gotten a session key yet, leave
   *                            this as null and then set it later by just
   *                            directly accessing the $session_key member
   *                            variable.
   */
  public function __construct($api_key, $secret, $session_key=null) {
    $this->secret       = $secret;
    $this->session_key  = $session_key;
    $this->api_key      = $api_key;
    $this->batch_mode = FacebookRestClient::BATCH_MODE_DEFAULT;
    $this->last_call_id = 0;
    $this->call_as_apikey = '';
    $this->server_addr  = Facebook::get_facebook_url('api') . '/restserver.php';
    if (!empty($GLOBALS['facebook_config']['debug'])) {
      $this->cur_id = 0;
      ?>
<script type="text/javascript">
var types = ['params', 'xml', 'php', 'sxml'];
function getStyle(elem, style) {
  if (elem.getStyle) {
    return elem.getStyle(style);
  } else {
    return elem.style[style];
  }
}
function setStyle(elem, style, value) {
  if (elem.setStyle) {
    elem.setStyle(style, value);
  } else {
    elem.style[style] = value;
  }
}
function toggleDisplay(id, type) {
  for (var i = 0; i < types.length; i++) {
    var t = types[i];
    var pre = document.getElementById(t + id);
    if (pre) {
      if (t != type || getStyle(pre, 'display') == 'block') {
        setStyle(pre, 'display', 'none');
      } else {
        setStyle(pre, 'display', 'block');
      }
    }
  }
  return false;
}
</script>
<?php
    }
  }


  /**
   * Start a batch operation.
   */
  public function begin_batch() {
    if($this->batch_queue !== null)
    {
      throw new FacebookRestClientException(FacebookAPIErrorCodes::API_EC_BATCH_ALREADY_STARTED,
      FacebookAPIErrorCodes::$api_error_descriptions[FacebookAPIErrorCodes::API_EC_BATCH_ALREADY_STARTED]);
    }

    $this->batch_queue = array();
  }

  /*
   * End current batch operation
   */
  public function end_batch() {
    if($this->batch_queue === null) {
      throw new FacebookRestClientException(FacebookAPIErrorCodes::API_EC_BATCH_NOT_STARTED,
      FacebookAPIErrorCodes::$api_error_descriptions[FacebookAPIErrorCodes::API_EC_BATCH_NOT_STARTED]);
    }

    $this->execute_server_side_batch();

    $this->batch_queue = null;
  }


  private function execute_server_side_batch() {


    $item_count = count($this->batch_queue);
    $method_feed = array();
    foreach($this->batch_queue as $batch_item) {
      $method_feed[] = $this->create_post_string($batch_item['m'], $batch_item['p']);
    }

    $method_feed_json = json_encode($method_feed);

    $serial_only = $this->batch_mode == FacebookRestClient::BATCH_MODE_SERIAL_ONLY ;
    $params = array('method_feed' => $method_feed_json, 'serial_only' => $serial_only);
    if ($this->call_as_apikey) {
      $params['call_as_apikey'] = $this->call_as_apikey;
    }

    $xml = $this->post_request('batch.run', $params);

    $result = $this->convert_xml_to_result($xml, 'batch.run', $params);


    if (is_array($result) && isset($result['error_code'])) {
      throw new FacebookRestClientException($result['error_msg'], $result['error_code']);
    }

    for($i = 0; $i < $item_count; $i++) {
      $batch_item = $this->batch_queue[$i];
      $batch_item_result_xml = $result[$i];
      $batch_item_result = $this->convert_xml_to_result($batch_item_result_xml, $batch_item['m'], $batch_item['p']);

      if (is_array($batch_item_result) && isset($batch_item_result['error_code'])) {
        throw new FacebookRestClientException($batch_item_result['error_msg'], $batch_item_result['error_code']);
      }
      $batch_item['r'] = $batch_item_result;
    }
  }

  public function begin_permissions_mode($permissions_apikey) {
    $this->call_as_apikey = $permissions_apikey;
  }

  public function end_permissions_mode() {
    $this->call_as_apikey = '';
  }

  /**
   * Returns the session information available after current user logs in.
   * @param string $auth_token the token returned by auth_createToken or
   *  passed back to your callback_url.
   * @param bool   $generate_session_secret  whether the session returned should include a session secret
   *
   * @return assoc array containing session_key, uid
   */
  public function auth_getSession($auth_token, $generate_session_secret=false) {
    //Check if we are in batch mode
    if($this->batch_queue === null) {
      $result = $this->call_method('facebook.auth.getSession',
          array('auth_token' => $auth_token, 'generate_session_secret' => $generate_session_secret));
      $this->session_key = $result['session_key'];

    if (!empty($result['secret']) && !$generate_session_secret) {
      // desktop apps have a special secret
      $this->secret = $result['secret'];
    }
      return $result;
    }
  }

  /**
   * Generates a session specific secret. This is for integration with client-side API calls, such as the
   * JS library.
   * @error API_EC_PARAM_SESSION_KEY
   *        API_EC_PARAM_UNKNOWN
   * @return session secret for the current promoted session
   */
  public function auth_promoteSession() {
      return $this->call_method('facebook.auth.promoteSession', array());
  }

  /**
   * Expires the session that is currently being used.  If this call is successful, no further calls to the
   * API (which require a session) can be made until a valid session is created.
   *
   * @return bool  true if session expiration was successful, false otherwise
   */
  public function auth_expireSession() {
      return $this->call_method('facebook.auth.expireSession', array());
  }

  /**
   * Returns events according to the filters specified.
   * @param int $uid Optional: User associated with events.
   *   A null parameter will default to the session user.
   * @param array $eids Optional: Filter by these event ids.
   *   A null parameter will get all events for the user.
   * @param int $start_time Optional: Filter with this UTC as lower bound.
   *   A null or zero parameter indicates no lower bound.
   * @param int $end_time Optional: Filter with this UTC as upper bound.
   *   A null or zero parameter indicates no upper bound.
   * @param string $rsvp_status Optional: Only show events where the given uid
   *   has this rsvp status.  This only works if you have specified a value for
   *   $uid.  Values are as in events.getMembers.  Null indicates to ignore
   *   rsvp status when filtering.
   * @return array of events
   */
  public function &events_get($uid, $eids, $start_time, $end_time, $rsvp_status) {
    return $this->call_method('facebook.events.get',
        array(
        'uid' => $uid,
        'eids' => $eids,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'rsvp_status' => $rsvp_status));
  }

  /**
   * Returns membership list data associated with an event
   * @param int $eid : event id
   * @return assoc array of four membership lists, with keys 'attending',
   *  'unsure', 'declined', and 'not_replied'
   */
  public function &events_getMembers($eid) {
    return $this->call_method('facebook.events.getMembers',
      array('eid' => $eid));
  }

  /**
   * Makes an FQL query.  This is a generalized way of accessing all the data
   * in the API, as an alternative to most of the other method calls.  More
   * info at http://developers.facebook.com/documentation.php?v=1.0&doc=fql
   * @param string $query the query to evaluate
   * @return generalized array representing the results
   */
  public function &fql_query($query) {
    return $this->call_method('facebook.fql.query',
      array('query' => $query));
  }

  public function &feed_publishStoryToUser($title, $body,
                                          $image_1=null, $image_1_link=null,
                                          $image_2=null, $image_2_link=null,
                                          $image_3=null, $image_3_link=null,
                                          $image_4=null, $image_4_link=null) {
    return $this->call_method('facebook.feed.publishStoryToUser',
      array('title' => $title,
            'body' => $body,
            'image_1' => $image_1,
            'image_1_link' => $image_1_link,
            'image_2' => $image_2,
            'image_2_link' => $image_2_link,
            'image_3' => $image_3,
            'image_3_link' => $image_3_link,
            'image_4' => $image_4,
            'image_4_link' => $image_4_link));
  }

  public function &feed_publishActionOfUser($title, $body,
                                           $image_1=null, $image_1_link=null,
                                           $image_2=null, $image_2_link=null,
                                           $image_3=null, $image_3_link=null,
                                           $image_4=null, $image_4_link=null) {
    return $this->call_method('facebook.feed.publishActionOfUser',
      array('title' => $title,
            'body' => $body,
            'image_1' => $image_1,
            'image_1_link' => $image_1_link,
            'image_2' => $image_2,
            'image_2_link' => $image_2_link,
            'image_3' => $image_3,
            'image_3_link' => $image_3_link,
            'image_4' => $image_4,
            'image_4_link' => $image_4_link));
  }

  public function &feed_publishTemplatizedAction($title_template, $title_data,
                                                $body_template, $body_data, $body_general,
                                                $image_1=null, $image_1_link=null,
                                                $image_2=null, $image_2_link=null,
                                                $image_3=null, $image_3_link=null,
                                                $image_4=null, $image_4_link=null,
                                                $target_ids='', $page_actor_id=null) {
    return $this->call_method('facebook.feed.publishTemplatizedAction',
      array('title_template' => $title_template,
            'title_data' => $title_data,
            'body_template' => $body_template,
            'body_data' => $body_data,
            'body_general' => $body_general,
            'image_1' => $image_1,
            'image_1_link' => $image_1_link,
            'image_2' => $image_2,
            'image_2_link' => $image_2_link,
            'image_3' => $image_3,
            'image_3_link' => $image_3_link,
            'image_4' => $image_4,
            'image_4_link' => $image_4_link,
            'target_ids' => $target_ids,
            'page_actor_id' => $page_actor_id));
  }

  /**
   * Returns whether or not pairs of users are friends.
   * Note that the Facebook friend relationship is symmetric.
   * @param array $uids1: array of ids (id_1, id_2,...) of some length X
   * @param array $uids2: array of ids (id_A, id_B,...) of SAME length X
   * @return array of uid pairs with bool, true if pair are friends, e.g.
   *   array( 0 => array('uid1' => id_1, 'uid2' => id_A, 'are_friends' => 1),
   *          1 => array('uid1' => id_2, 'uid2' => id_B, 'are_friends' => 0)
   *         ...)
   */
  public function &friends_areFriends($uids1, $uids2) {
    return $this->call_method('facebook.friends.areFriends',
        array('uids1'=>$uids1, 'uids2'=>$uids2));
  }

  /**
   * Returns the friends of the current session user.
   * @return array of friends
   */
  public function &friends_get() {
    if (isset($this->friends_list)) {
      return $this->friends_list;
    }
    return $this->call_method('facebook.friends.get', array());
  }

  /**
   * Returns the friends of the session user, who are also users
   * of the calling application.
   * @return array of friends
   */
  public function &friends_getAppUsers() {
    return $this->call_method('facebook.friends.getAppUsers', array());
  }

  /**
   * Returns groups according to the filters specified.
   * @param int $uid Optional: User associated with groups.
   *  A null parameter will default to the session user.
   * @param array $gids Optional: group ids to query.
   *   A null parameter will get all groups for the user.
   * @return array of groups
   */
  public function &groups_get($uid, $gids) {
    return $this->call_method('facebook.groups.get',
        array(
        'uid' => $uid,
        'gids' => $gids));
  }

  /**
   * Returns the membership list of a group
   * @param int $gid : Group id
   * @return assoc array of four membership lists, with keys
   *  'members', 'admins', 'officers', and 'not_replied'
   */
  public function &groups_getMembers($gid) {
    return $this->call_method('facebook.groups.getMembers',
      array('gid' => $gid));
  }

  /**
   * Returns cookies according to the filters specified.
   * @param int $uid Required: User for which the cookies are needed.
   * @param string $name Optional:
   *   A null parameter will get all cookies for the user.
   * @return array of cookies
   */
  public function data_getCookies($uid, $name) {
    return $this->call_method('facebook.data.getCookies',
        array(
        'uid' => $uid,
        'name' => $name));
  }

  /**
   * Sets cookies according to the params specified.
   * @param int $uid Required: User for which the cookies are needed.
   * @param string $name Required: name of the cookie
   * @param string $value Optional if expires specified and is in the past
   * @param int$expires Optional
   * @param string $path Optional
   *
   * @return bool
   */
  public function data_setCookie($uid, $name, $value, $expires, $path) {
    return $this->call_method('facebook.data.setCookie',
        array(
        'uid' => $uid,
        'name' => $name,
        'value' => $value,
        'expires' => $expires,
        'path' => $path));
  }

  /**
   * Permissions API
   */

  /**
   * Checks API-access granted by self to the specified application
   * @param string $permissions_apikey: Required
   *
   * @return array: API methods/namespaces which are allowed access
   */
  public function permissions_checkGrantedApiAccess($permissions_apikey) {
    return $this->call_method('facebook.permissions.checkGrantedApiAccess',
        array(
        'permissions_apikey' => $permissions_apikey));
  }

  /**
   * Checks API-access granted to self by the specified application
   * @param string $permissions_apikey: Required
   *
   * @return array: API methods/namespaces which are allowed access
   */
  public function permissions_checkAvailableApiAccess($permissions_apikey) {
    return $this->call_method('facebook.permissions.checkAvailableApiAccess',
        array(
        'permissions_apikey' => $permissions_apikey));
  }

  /**
   * Grant API-access to the specified methods/namespaces to the specified application
   * @param string $permissions_apikey: Required
   * @param array(string) : Optional: API methods/namespaces to be allowed
   *
   * @return array: API methods/namespaces which are allowed access
   */
  public function permissions_grantApiAccess($permissions_apikey, $method_arr) {
    return $this->call_method('facebook.permissions.grantApiAccess',
        array(
        'permissions_apikey' => $permissions_apikey,
        'method_arr' => $method_arr));
  }

  /**
   * Revoke API-access granted to the specified application
   * @param string $permissions_apikey: Required
   *
   * @return bool
   */
  public function permissions_revokeApiAccess($permissions_apikey) {
    return $this->call_method('facebook.permissions.revokeApiAccess',
        array(
        'permissions_apikey' => $permissions_apikey));
  }

  /**
   * Returns the outstanding notifications for the session user.
   * @return assoc array of
   *  notification count objects for 'messages', 'pokes' and 'shares',
   *  a uid list of 'friend_requests', a gid list of 'group_invites',
   *  and an eid list of 'event_invites'
   */
  public function &notifications_get() {
    return $this->call_method('facebook.notifications.get', array());
  }

  /**
   * Sends a notification to the specified users.
   * @return (nothing)
   */
  public function &notifications_send($to_ids, $notification) {
    return $this->call_method('facebook.notifications.send',
                              array('to_ids' => $to_ids, 'notification' => $notification));
  }

  /**
   * Sends an email to the specified user of the application.
   * @param array $recipients : id of the recipients
   * @param string $subject : subject of the email
   * @param string $text : (plain text) body of the email
   * @param string $fbml : fbml markup if you want an html version of the email
   * @return comma separated list of successful recipients
   */
  public function &notifications_sendEmail($recipients, $subject, $text, $fbml) {
    return $this->call_method('facebook.notifications.sendEmail',
                              array('recipients' => $recipients,
                                    'subject' => $subject,
                                    'text' => $text,
                                    'fbml' => $fbml));
  }

  /**
   * Returns the requested info fields for the requested set of pages
   * @param array $page_ids an array of page ids
   * @param array $fields an array of strings describing the info fields desired
   * @param int $uid   Optionally, limit results to pages of which this user is a fan.
   * @param string type  limits results to a particular type of page.
   * @return array of pages
   */
  public function &pages_getInfo($page_ids, $fields, $uid, $type) {
    return $this->call_method('facebook.pages.getInfo', array('page_ids' => $page_ids, 'fields' => $fields, 'uid' => $uid, 'type' => $type));
  }

  /**
   * Returns true if logged in user is an admin for the passed page
   * @param int $page_id target page id
   * @return boolean
   */
  public function &pages_isAdmin($page_id) {
    return $this->call_method('facebook.pages.isAdmin', array('page_id' => $page_id));
  }

  /**
   * Returns whether or not the page corresponding to the current session object has the app installed
   * @return boolean
   */
  public function &pages_isAppAdded() {
    if (isset($this->added)) {
      return $this->added;
    }
    return $this->call_method('facebook.pages.isAppAdded', array());
  }

  /**
   * Returns true if logged in user is a fan for the passed page
   * @param int $page_id target page id
   * @param int $uid user to compare.  If empty, the logged in user.
   * @return bool
   */
  public function &pages_isFan($page_id, $uid) {
    return $this->call_method('facebook.pages.isFan', array('page_id' => $page_id, 'uid' => $uid));
  }

  /**
   * Returns photos according to the filters specified.
   * @param int $subj_id Optional: Filter by uid of user tagged in the photos.
   * @param int $aid Optional: Filter by an album, as returned by
   *  photos_getAlbums.
   * @param array $pids Optional: Restrict to a list of pids
   * Note that at least one of these parameters needs to be specified, or an
   * error is returned.
   * @return array of photo objects.
   */
  public function &photos_get($subj_id, $aid, $pids) {
    return $this->call_method('facebook.photos.get',
      array('subj_id' => $subj_id, 'aid' => $aid, 'pids' => $pids));
  }

  /**
   * Returns the albums created by the given user.
   * @param int $uid Optional: the uid of the user whose albums you want.
   *   A null value will return the albums of the session user.
   * @param array $aids Optional: a list of aids to restrict the query.
   * Note that at least one of the (uid, aids) parameters must be specified.
   * @returns an array of album objects.
   */
  public function &photos_getAlbums($uid, $aids) {
    return $this->call_method('facebook.photos.getAlbums',
      array('uid' => $uid,
            'aids' => $aids));
  }

  /**
   * Returns the tags on all photos specified.
   * @param string $pids : a list of pids to query
   * @return array of photo tag objects, with include pid, subject uid,
   *  and two floating-point numbers (xcoord, ycoord) for tag pixel location
   */
  public function &photos_getTags($pids) {
    return $this->call_method('facebook.photos.getTags',
      array('pids' => $pids));
  }


  /**
   * Returns the requested info fields for the requested set of users
   * @param array $uids an array of user ids
   * @param array $fields an array of strings describing the info fields desired
   * @return array of users
   */
  public function &users_getInfo($uids, $fields) {
    return $this->call_method('facebook.users.getInfo', array('uids' => $uids, 'fields' => $fields));
  }

  /**
   * Returns the user corresponding to the current session object.
   * @return integer uid
   */
  public function &users_getLoggedInUser() {
    return $this->call_method('facebook.users.getLoggedInUser', array());
  }

  /**
   * Returns whether or not the user corresponding to the current session object has the app installed
   * @return boolean
   */
  public function &users_isAppAdded($uid=null) {
    if (isset($this->added)) {
      return $this->added;
    }
    return $this->call_method('facebook.users.isAppAdded', array('uid' => $uid));
  }

  /**
   * Sets the FBML for the profile of the user attached to this session
   * @param   string   $markup           The FBML that describes the profile presence of this app for the user
   * @param   int      $uid              The user
   * @param   string   $profile          Profile FBML
   * @param   string   $profile_action   Profile action FBML
   * @param   string   $mobile_profile   Mobile profile FBML
   * @return  array    A list of strings describing any compile errors for the submitted FBML
   */
  function profile_setFBML($markup, $uid = null, $profile='', $profile_action='', $mobile_profile='') {
    return $this->call_method('facebook.profile.setFBML', array('markup' => $markup,
                                                                'uid' => $uid,
                                                                'profile' => $profile,
                                                                'profile_action' => $profile_action,
                                                                'mobile_profile' => $mobile_profile));
  }

  public function &profile_getFBML($uid) {
    return $this->call_method('facebook.profile.getFBML', array('uid' => $uid));
  }

  public function &fbml_refreshImgSrc($url) {
    return $this->call_method('facebook.fbml.refreshImgSrc', array('url' => $url));
  }

  public function &fbml_refreshRefUrl($url) {
    return $this->call_method('facebook.fbml.refreshRefUrl', array('url' => $url));
  }

  public function &fbml_setRefHandle($handle, $fbml) {
    return $this->call_method('facebook.fbml.setRefHandle', array('handle' => $handle, 'fbml' => $fbml));
  }

  /**
   * Get all the marketplace categories
   *
   * @return array  A list of category names
   */
  function marketplace_getCategories() {
    return $this->call_method('facebook.marketplace.getCategories', array());
  }

  /**
   * Get all the marketplace subcategories for a particular category
   *
   * @param  category  The category for which we are pulling subcategories
   * @return array     A list of subcategory names
   */
  function marketplace_getSubCategories($category) {
    return $this->call_method('facebook.marketplace.getSubCategories', array('category' => $category));
  }

  /**
   * Get listings by either listing_id or user
   *
   * @param listing_ids   An array of listing_ids (optional)
   * @param uids          An array of user ids (optional)
   * @return array        The data for matched listings
   */
  function marketplace_getListings($listing_ids, $uids) {
    return $this->call_method('facebook.marketplace.getListings', array('listing_ids' => $listing_ids, 'uids' => $uids));
  }

  /**
   * Search for Marketplace listings.  All arguments are optional, though at least
   * one must be filled out to retrieve results.
   *
   * @param category     The category in which to search (optional)
   * @param subcategory  The subcategory in which to search (optional)
   * @param query        A query string (optional)
   * @return array       The data for matched listings
   */
  function marketplace_search($category, $subcategory, $query) {
    return $this->call_method('facebook.marketplace.search', array('category' => $category, 'subcategory' => $subcategory, 'query' => $query));
  }

  /**
   * Remove a listing from Marketplace
   *
   * @param listing_id  The id of the listing to be removed
   * @param status      'SUCCESS', 'NOT_SUCCESS', or 'DEFAULT'
   * @return bool       True on success
   */
  function marketplace_removeListing($listing_id, $status='DEFAULT', $uid=null) {
    return $this->call_method('facebook.marketplace.removeListing',
                              array('listing_id'=>$listing_id,
                                    'status'=>$status,
                                    'uid' => $uid));
  }

  /**
   * Create/modify a Marketplace listing for the loggedinuser
   *
   * @param int              listing_id   The id of a listing to be modified, 0 for a new listing.
   * @param show_on_profile  bool         Should we show this listing on the user's profile
   * @param listing_attrs    array        An array of the listing data
   * @return                 int          The listing_id (unchanged if modifying an existing listing)
   */
  function marketplace_createListing($listing_id, $show_on_profile, $attrs, $uid=null) {
    return $this->call_method('facebook.marketplace.createListing',
                              array('listing_id'=>$listing_id,
                                    'show_on_profile'=>$show_on_profile,
                                    'listing_attrs'=>json_encode($attrs),
                                    'uid' => $uid));
  }


  /////////////////////////////////////////////////////////////////////////////
  // Data Store API

  /**
   * Set a user preference.
   *
   * @param  pref_id    preference identifier (0-200)
   * @param  value      preferece's value
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_setUserPreference($pref_id, $value) {
    return $this->call_method
      ('facebook.data.setUserPreference',
       array('pref_id' => $pref_id,
             'value' => $value));
  }

  /**
   * Set a user's all preferences for this application.
   *
   * @param  values     preferece values in an associative arrays
   * @param  replace    whether to replace all existing preferences or
   *                    merge into them.
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_setUserPreferences($values, $replace = false) {
    return $this->call_method
      ('facebook.data.setUserPreferences',
       array('values' => json_encode($values),
             'replace' => $replace));
  }

  /**
   * Get a user preference.
   *
   * @param  pref_id    preference identifier (0-200)
   * @return            preference's value
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getUserPreference($pref_id) {
    return $this->call_method
      ('facebook.data.getUserPreference',
       array('pref_id' => $pref_id));
  }

  /**
   * Get a user preference.
   *
   * @return           preference values
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getUserPreferences() {
    return $this->call_method
      ('facebook.data.getUserPreferences',
       array());
  }

  /**
   * Create a new object type.
   *
   * @param  name       object type's name
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_ALREADY_EXISTS
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_createObjectType($name) {
    return $this->call_method
      ('facebook.data.createObjectType',
       array('name' => $name));
  }

  /**
   * Delete an object type.
   *
   * @param  obj_type       object type's name
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_dropObjectType($obj_type) {
    return $this->call_method
      ('facebook.data.dropObjectType',
       array('obj_type' => $obj_type));
  }

  /**
   * Rename an object type.
   *
   * @param  obj_type       object type's name
   * @param  new_name       new object type's name
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_DATA_OBJECT_ALREADY_EXISTS
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_renameObjectType($obj_type, $new_name) {
    return $this->call_method
      ('facebook.data.renameObjectType',
       array('obj_type' => $obj_type,
             'new_name' => $new_name));
  }

  /**
   * Add a new property to an object type.
   *
   * @param  obj_type       object type's name
   * @param  prop_name      name of the property to add
   * @param  prop_type      1: integer; 2: string; 3: text blob
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_ALREADY_EXISTS
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_defineObjectProperty($obj_type, $prop_name, $prop_type) {
    return $this->call_method
      ('facebook.data.defineObjectProperty',
       array('obj_type' => $obj_type,
             'prop_name' => $prop_name,
             'prop_type' => $prop_type));
  }

  /**
   * Remove a previously defined property from an object type.
   *
   * @param  obj_type      object type's name
   * @param  prop_name     name of the property to remove
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_undefineObjectProperty($obj_type, $prop_name) {
    return $this->call_method
      ('facebook.data.undefineObjectProperty',
       array('obj_type' => $obj_type,
             'prop_name' => $prop_name));
  }

  /**
   * Rename a previously defined property of an object type.
   *
   * @param  obj_type      object type's name
   * @param  prop_name     name of the property to rename
   * @param  new_name      new name to use
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_DATA_OBJECT_ALREADY_EXISTS
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_renameObjectProperty($obj_type, $prop_name,
                                            $new_name) {
    return $this->call_method
      ('facebook.data.renameObjectProperty',
       array('obj_type' => $obj_type,
             'prop_name' => $prop_name,
             'new_name' => $new_name));
  }

  /**
   * Retrieve a list of all object types that have defined for the application.
   *
   * @return               a list of object type names
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PERMISSION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getObjectTypes() {
    return $this->call_method
      ('facebook.data.getObjectTypes',
       array());
  }

  /**
   * Get definitions of all properties of an object type.
   *
   * @param obj_type       object type's name
   * @return               pairs of property name and property types
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getObjectType($obj_type) {
    return $this->call_method
      ('facebook.data.getObjectType',
       array('obj_type' => $obj_type));
  }

  /**
   * Create a new object.
   *
   * @param  obj_type      object type's name
   * @param  properties    (optional) properties to set initially
   * @return               newly created object's id
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_createObject($obj_type, $properties = null) {
    return $this->call_method
      ('facebook.data.createObject',
       array('obj_type' => $obj_type,
             'properties' => json_encode($properties)));
  }

  /**
   * Update an existing object.
   *
   * @param  obj_id        object's id
   * @param  properties    new properties
   * @param  replace       true for replacing existing properties; false for merging
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_updateObject($obj_id, $properties, $replace = false) {
    return $this->call_method
      ('facebook.data.updateObject',
       array('obj_id' => $obj_id,
             'properties' => json_encode($properties),
             'replace' => $replace));
  }

  /**
   * Delete an existing object.
   *
   * @param  obj_id        object's id
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_deleteObject($obj_id) {
    return $this->call_method
      ('facebook.data.deleteObject',
       array('obj_id' => $obj_id));
  }

  /**
   * Delete a list of objects.
   *
   * @param  obj_ids       objects to delete
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_deleteObjects($obj_ids) {
    return $this->call_method
      ('facebook.data.deleteObjects',
       array('obj_ids' => json_encode($obj_ids)));
  }

  /**
   * Get a single property value of an object.
   *
   * @param  obj_id        object's id
   * @param  prop_name     individual property's name
   * @return               individual property's value
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getObjectProperty($obj_id, $prop_name) {
    return $this->call_method
      ('facebook.data.getObjectProperty',
       array('obj_id' => $obj_id,
             'prop_name' => $prop_name));
  }

  /**
   * Get properties of an object.
   *
   * @param  obj_id      object's id
   * @param  prop_names  (optional) properties to return; null for all.
   * @return             specified properties of an object
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getObject($obj_id, $prop_names = null) {
    return $this->call_method
      ('facebook.data.getObject',
       array('obj_id' => $obj_id,
             'prop_names' => json_encode($prop_names)));
  }

  /**
   * Get properties of a list of objects.
   *
   * @param  obj_ids     object ids
   * @param  prop_names  (optional) properties to return; null for all.
   * @return             specified properties of an object
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getObjects($obj_ids, $prop_names = null) {
    return $this->call_method
      ('facebook.data.getObjects',
       array('obj_ids' => json_encode($obj_ids),
             'prop_names' => json_encode($prop_names)));
  }

  /**
   * Set a single property value of an object.
   *
   * @param  obj_id        object's id
   * @param  prop_name     individual property's name
   * @param  prop_value    new value to set
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_setObjectProperty($obj_id, $prop_name,
                                         $prop_value) {
    return $this->call_method
      ('facebook.data.setObjectProperty',
       array('obj_id' => $obj_id,
             'prop_name' => $prop_name,
             'prop_value' => $prop_value));
  }

  /**
   * Read hash value by key.
   *
   * @param  obj_type      object type's name
   * @param  key           hash key
   * @param  prop_name     (optional) individual property's name
   * @return               hash value
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getHashValue($obj_type, $key, $prop_name = null) {
    return $this->call_method
      ('facebook.data.getHashValue',
       array('obj_type' => $obj_type,
             'key' => $key,
             'prop_name' => $prop_name));
  }

  /**
   * Write hash value by key.
   *
   * @param  obj_type      object type's name
   * @param  key           hash key
   * @param  value         hash value
   * @param  prop_name     (optional) individual property's name
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_setHashValue($obj_type, $key, $value, $prop_name = null) {
    return $this->call_method
      ('facebook.data.setHashValue',
       array('obj_type' => $obj_type,
             'key' => $key,
             'value' => $value,
             'prop_name' => $prop_name));
  }

  /**
   * Increase a hash value by specified increment atomically.
   *
   * @param  obj_type      object type's name
   * @param  key           hash key
   * @param  prop_name     individual property's name
   * @param  increment     (optional) default is 1
   * @return               incremented hash value
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_incHashValue($obj_type, $key, $prop_name, $increment = 1) {
    return $this->call_method
      ('facebook.data.incHashValue',
       array('obj_type' => $obj_type,
             'key' => $key,
             'prop_name' => $prop_name,
             'increment' => $increment));
  }

  /**
   * Remove a hash key and its values.
   *
   * @param  obj_type    object type's name
   * @param  key         hash key
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_removeHashKey($obj_type, $key) {
    return $this->call_method
      ('facebook.data.removeHashKey',
       array('obj_type' => $obj_type,
             'key' => $key));
  }

  /**
   * Remove hash keys and their values.
   *
   * @param  obj_type    object type's name
   * @param  keys        hash keys
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_removeHashKeys($obj_type, $keys) {
    return $this->call_method
      ('facebook.data.removeHashKeys',
       array('obj_type' => $obj_type,
             'keys' => json_encode($keys)));
  }


  /**
   * Define an object association.
   *
   * @param  name        name of this association
   * @param  assoc_type  1: one-way 2: two-way symmetric 3: two-way asymmetric
   * @param  assoc_info1 needed info about first object type
   * @param  assoc_info2 needed info about second object type
   * @param  inverse     (optional) name of reverse association
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_ALREADY_EXISTS
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_defineAssociation($name, $assoc_type, $assoc_info1,
                                         $assoc_info2, $inverse = null) {
    return $this->call_method
      ('facebook.data.defineAssociation',
       array('name' => $name,
             'assoc_type' => $assoc_type,
             'assoc_info1' => json_encode($assoc_info1),
             'assoc_info2' => json_encode($assoc_info2),
             'inverse' => $inverse));
  }

  /**
   * Undefine an object association.
   *
   * @param  name        name of this association
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_undefineAssociation($name) {
    return $this->call_method
      ('facebook.data.undefineAssociation',
       array('name' => $name));
  }

  /**
   * Rename an object association or aliases.
   *
   * @param  name        name of this association
   * @param  new_name    (optional) new name of this association
   * @param  new_alias1  (optional) new alias for object type 1
   * @param  new_alias2  (optional) new alias for object type 2
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_ALREADY_EXISTS
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_renameAssociation($name, $new_name, $new_alias1 = null,
                                         $new_alias2 = null) {
    return $this->call_method
      ('facebook.data.renameAssociation',
       array('name' => $name,
             'new_name' => $new_name,
             'new_alias1' => $new_alias1,
             'new_alias2' => $new_alias2));
  }

  /**
   * Get definition of an object association.
   *
   * @param  name        name of this association
   * @return             specified association
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getAssociationDefinition($name) {
    return $this->call_method
      ('facebook.data.getAssociationDefinition',
       array('name' => $name));
  }

  /**
   * Get definition of all associations.
   *
   * @return             all defined associations
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PERMISSION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getAssociationDefinitions() {
    return $this->call_method
      ('facebook.data.getAssociationDefinitions',
       array());
  }

  /**
   * Create or modify an association between two objects.
   *
   * @param  name        name of association
   * @param  obj_id1     id of first object
   * @param  obj_id2     id of second object
   * @param  data        (optional) extra string data to store
   * @param  assoc_time  (optional) extra time data; default to creation time
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_setAssociation($name, $obj_id1, $obj_id2, $data = null,
                                      $assoc_time = null) {
    return $this->call_method
      ('facebook.data.setAssociation',
       array('name' => $name,
             'obj_id1' => $obj_id1,
             'obj_id2' => $obj_id2,
             'data' => $data,
             'assoc_time' => $assoc_time));
  }

  /**
   * Create or modify associations between objects.
   *
   * @param  assocs      associations to set
   * @param  name        (optional) name of association
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_setAssociations($assocs, $name = null) {
    return $this->call_method
      ('facebook.data.setAssociations',
       array('assocs' => json_encode($assocs),
             'name' => $name));
  }

  /**
   * Remove an association between two objects.
   *
   * @param  name        name of association
   * @param  obj_id1     id of first object
   * @param  obj_id2     id of second object
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_removeAssociation($name, $obj_id1, $obj_id2) {
    return $this->call_method
      ('facebook.data.removeAssociation',
       array('name' => $name,
             'obj_id1' => $obj_id1,
             'obj_id2' => $obj_id2));
  }

  /**
   * Remove associations between objects by specifying pairs of object ids.
   *
   * @param  assocs      associations to remove
   * @param  name        (optional) name of association
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_removeAssociations($assocs, $name = null) {
    return $this->call_method
      ('facebook.data.removeAssociations',
       array('assocs' => json_encode($assocs),
             'name' => $name));
  }

  /**
   * Remove associations between objects by specifying one object id.
   *
   * @param  name        name of association
   * @param  obj_id      who's association to remove
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_removeAssociatedObjects($name, $obj_id) {
    return $this->call_method
      ('facebook.data.removeAssociatedObjects',
       array('name' => $name,
             'obj_id' => $obj_id));
  }

  /**
   * Retrieve a list of associated objects.
   *
   * @param  name        name of association
   * @param  obj_id      who's association to retrieve
   * @param  no_data     only return object ids
   * @return             associated objects
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getAssociatedObjects($name, $obj_id, $no_data = true) {
    return $this->call_method
      ('facebook.data.getAssociatedObjects',
       array('name' => $name,
             'obj_id' => $obj_id,
             'no_data' => $no_data));
  }

  /**
   * Count associated objects.
   *
   * @param  name        name of association
   * @param  obj_id      who's association to retrieve
   * @return             associated object's count
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getAssociatedObjectCount($name, $obj_id) {
    return $this->call_method
      ('facebook.data.getAssociatedObjectCount',
       array('name' => $name,
             'obj_id' => $obj_id));
  }

  /**
   * Get a list of associated object counts.
   *
   * @param  name        name of association
   * @param  obj_ids     whose association to retrieve
   * @return             associated object counts
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_DATA_OBJECT_NOT_FOUND
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_INVALID_OPERATION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getAssociatedObjectCounts($name, $obj_ids) {
    return $this->call_method
      ('facebook.data.getAssociatedObjectCounts',
       array('name' => $name,
             'obj_ids' => json_encode($obj_ids)));
  }

  /**
   * Find all associations between two objects.
   *
   * @param  obj_id1     id of first object
   * @param  obj_id2     id of second object
   * @param  no_data     only return association names without data
   * @return             all associations between objects
   * @error
   *    API_EC_DATA_DATABASE_ERROR
   *    API_EC_PARAM
   *    API_EC_PERMISSION
   *    API_EC_DATA_QUOTA_EXCEEDED
   *    API_EC_DATA_UNKNOWN_ERROR
   */
  public function &data_getAssociations($obj_id1, $obj_id2, $no_data = true) {
    return $this->call_method
      ('facebook.data.getAssociations',
       array('obj_id1' => $obj_id1,
             'obj_id2' => $obj_id2,
             'no_data' => $no_data));
  }

  /**
   * Get the properties that you have set for an app.
   *
   * @param  properties  list of properties names to fetch
   * @return             a map from property name to value
   */
  public function admin_getAppProperties($properties) {
    return json_decode($this->call_method
                       ('facebook.admin.getAppProperties',
                        array('properties' => json_encode($properties))), true);
  }

  /**
   * Set properties for an app.
   *
   * @param  properties  a map from property names to  values
   * @return             true on success
   */
  public function admin_setAppProperties($properties) {
    return $this->call_method
      ('facebook.admin.setAppProperties',
       array('properties' => json_encode($properties)));
  }

  /**
   * Returns the allocation limit value for a specified integration point name
   * Integration point names are defined in lib/api/karma/constants.php in the limit_map
   * @param string $integration_point_name
   * @return integration point allocation value
   */
  public function &admin_getAllocation($integration_point_name) {
    return $this->call_method('facebook.admin.getAllocation', array('integration_point_name' => $integration_point_name));
  }

  /**
   * Returns values for the specified daily metrics for the current
   * application, in the given date range (inclusive).
   *
   * @param start_date  unix time for the start of the date range
   * @param end_date    unix time for the end of the date range
   * @param metrics     list of daily metrics to look up
   * @return            a list of the values for those metrics
   */
  public function &admin_getDailyMetrics($start_date, $end_date, $metrics) {
    return $this->call_method('admin.getDailyMetrics',
                              array('start_date' => $start_date,
                                    'end_date' => $end_date,
                                    'metrics' => json_encode($metrics)));
  }

  /**
   * Returns values for the specified metrics for the current
   * application, in the given time range.  The metrics are collected
   * for fixed-length periods, and the times represent midnight at
   * the end of each period.
   *
   * @param start_time  unix time for the start of the range
   * @param end_time    unix time for the end of the range
   * @param period      number of seconds in the desired period
   * @param metrics     list of metrics to look up
   * @return            a list of the values for those metrics
   */
  public function &admin_getMetrics($start_time, $end_time, $period, $metrics) {
    return $this->call_method('admin.getMetrics',
                              array('start_time' => $start_time,
                                    'end_time' => $end_time,
                                    'period' => $period,
                                    'metrics' => json_encode($metrics)));
  }

  /**
   * Sets application targeting info
   *
   * @param   targeting_info
   * @return  bool
   */
  public function admin_setTargetingInfo($targeting_info = null) {
    $targeting_str = null;
    if (!empty($targeting_info)) {
      $targeting_str = json_encode($targeting_info);
    }
    return $this->call_method('admin.setTargetingInfo',
                              array('targeting_str' => $targeting_str));
  }

  /**
   * Gets application targeting info
   *
   * @return  bool
   */
  public function admin_getTargetingInfo() {
    return json_decode($this->call_method(
                                'admin.getTargetingInfo',
                                array()),
                       true);
  }

  /* UTILITY FUNCTIONS */

  public function & call_method($method, $params) {

    //Check if we are in batch mode
    if($this->batch_queue === null) {
      if ($this->call_as_apikey) {
        $params['call_as_apikey'] = $this->call_as_apikey;
      }
      $xml = $this->post_request($method, $params);
      $result = $this->convert_xml_to_result($xml, $method, $params);
      if (is_array($result) && isset($result['error_code'])) {
        throw new FacebookRestClientException($result['error_msg'], $result['error_code']);
      }
    }
    else {
      $result = null;
      $batch_item = array('m' => $method, 'p' => $params, 'r' => & $result);
      $this->batch_queue[] = $batch_item;
    }

    return $result;
  }

  private function convert_xml_to_result($xml, $method, $params) {
    $sxml = simplexml_load_string($xml);
    $result = self::convert_simplexml_to_array($sxml);


    if (!empty($GLOBALS['facebook_config']['debug'])) {
      // output the raw xml and its corresponding php object, for debugging:
      print '<div style="margin: 10px 30px; padding: 5px; border: 2px solid black; background: gray; color: white; font-size: 12px; font-weight: bold;">';
      $this->cur_id++;
      print $this->cur_id . ': Called ' . $method . ', show ' .
            '<a href=# onclick="return toggleDisplay(' . $this->cur_id . ', \'params\');">Params</a> | '.
            '<a href=# onclick="return toggleDisplay(' . $this->cur_id . ', \'xml\');">XML</a> | '.
            '<a href=# onclick="return toggleDisplay(' . $this->cur_id . ', \'sxml\');">SXML</a> | '.
            '<a href=# onclick="return toggleDisplay(' . $this->cur_id . ', \'php\');">PHP</a>';
      print '<pre id="params'.$this->cur_id.'" style="display: none; overflow: auto;">'.print_r($params, true).'</pre>';
      print '<pre id="xml'.$this->cur_id.'" style="display: none; overflow: auto;">'.htmlspecialchars($xml).'</pre>';
      print '<pre id="php'.$this->cur_id.'" style="display: none; overflow: auto;">'.print_r($result, true).'</pre>';
      print '<pre id="sxml'.$this->cur_id.'" style="display: none; overflow: auto;">'.print_r($sxml, true).'</pre>';
      print '</div>';
    }
    return $result;
  }



  private function create_post_string($method, $params) {
    $params['method'] = $method;
    $params['session_key'] = $this->session_key;
    $params['api_key'] = $this->api_key;
    $params['call_id'] = microtime(true);
    if ($params['call_id'] <= $this->last_call_id) {
      $params['call_id'] = $this->last_call_id + 0.001;
    }
    $this->last_call_id = $params['call_id'];
    if (!isset($params['v'])) {
      $params['v'] = '1.0';
    }
    $post_params = array();
    foreach ($params as $key => &$val) {
      if (is_array($val)) $val = implode(',', $val);
      $post_params[] = $key.'='.urlencode($val);
    }
    $secret = $this->secret;
    $post_params[] = 'sig='.Facebook::generate_sig($params, $secret);
    return implode('&', $post_params);
  }

  public function post_request($method, $params) {

    $post_string = $this->create_post_string($method, $params);

    if (function_exists('curl_init')) {
      // Use CURL if installed...
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->server_addr);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Facebook API PHP5 Client 1.1 (curl) ' . phpversion());
      $result = curl_exec($ch);
      curl_close($ch);
    } else {
      // Non-CURL based version...
      $context =
        array('http' =>
              array('method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded'."\r\n".
                                'User-Agent: Facebook API PHP5 Client 1.1 (non-curl) '.phpversion()."\r\n".
                                'Content-length: ' . strlen($post_string),
                    'content' => $post_string));
      $contextid=stream_context_create($context);
      $sock=fopen($this->server_addr, 'r', false, $contextid);
      if ($sock) {
        $result='';
        while (!feof($sock))
          $result.=fgets($sock, 4096);

        fclose($sock);
      }
    }
    return $result;
  }

  public static function convert_simplexml_to_array($sxml) {
    $arr = array();
    if ($sxml) {
      foreach ($sxml as $k => $v) {
        if ($sxml['list']) {
          $arr[] = self::convert_simplexml_to_array($v);
        } else {
          $arr[$k] = self::convert_simplexml_to_array($v);
        }
      }
    }
    if (sizeof($arr) > 0) {
      return $arr;
    } else {
      return (string)$sxml;
    }
  }

}


class FacebookRestClientException extends Exception {
}

// Supporting methods and values------

/**
 * Error codes and descriptions for the Facebook API.
 */

class FacebookAPIErrorCodes {

  const API_EC_SUCCESS = 0;

  /*
   * GENERAL ERRORS
   */
  const API_EC_UNKNOWN = 1;
  const API_EC_SERVICE = 2;
  const API_EC_METHOD = 3;
  const API_EC_TOO_MANY_CALLS = 4;
  const API_EC_BAD_IP = 5;

  /*
   * PARAMETER ERRORS
   */
  const API_EC_PARAM = 100;
  const API_EC_PARAM_API_KEY = 101;
  const API_EC_PARAM_SESSION_KEY = 102;
  const API_EC_PARAM_CALL_ID = 103;
  const API_EC_PARAM_SIGNATURE = 104;
  const API_EC_PARAM_USER_ID = 110;
  const API_EC_PARAM_USER_FIELD = 111;
  const API_EC_PARAM_SOCIAL_FIELD = 112;
  const API_EC_PARAM_ALBUM_ID = 120;

  /*
   * USER PERMISSIONS ERRORS
   */
  const API_EC_PERMISSION = 200;
  const API_EC_PERMISSION_USER = 210;
  const API_EC_PERMISSION_ALBUM = 220;
  const API_EC_PERMISSION_PHOTO = 221;

  const FQL_EC_PARSER = 601;
  const FQL_EC_UNKNOWN_FIELD = 602;
  const FQL_EC_UNKNOWN_TABLE = 603;
  const FQL_EC_NOT_INDEXABLE = 604;

  /**
   * DATA STORE API ERRORS
   */
  const API_EC_DATA_UNKNOWN_ERROR = 800;
  const API_EC_DATA_INVALID_OPERATION = 801;
  const API_EC_DATA_QUOTA_EXCEEDED = 802;
  const API_EC_DATA_OBJECT_NOT_FOUND = 803;
  const API_EC_DATA_OBJECT_ALREADY_EXISTS = 804;
  const API_EC_DATA_DATABASE_ERROR = 805;


  /*
   * Batch ERROR
   */
  const API_EC_BATCH_ALREADY_STARTED = 900;
  const API_EC_BATCH_NOT_STARTED = 901;
  const API_EC_BATCH_METHOD_NOT_ALLOWED_IN_BATCH_MODE = 902;

  public static $api_error_descriptions = array(
      API_EC_SUCCESS           => 'Success',
      API_EC_UNKNOWN           => 'An unknown error occurred',
      API_EC_SERVICE           => 'Service temporarily unavailable',
      API_EC_METHOD            => 'Unknown method',
      API_EC_TOO_MANY_CALLS    => 'Application request limit reached',
      API_EC_BAD_IP            => 'Unauthorized source IP address',
      API_EC_PARAM             => 'Invalid parameter',
      API_EC_PARAM_API_KEY     => 'Invalid API key',
      API_EC_PARAM_SESSION_KEY => 'Session key invalid or no longer valid',
      API_EC_PARAM_CALL_ID     => 'Call_id must be greater than previous',
      API_EC_PARAM_SIGNATURE   => 'Incorrect signature',
      API_EC_PARAM_USER_ID     => 'Invalid user id',
      API_EC_PARAM_USER_FIELD  => 'Invalid user info field',
      API_EC_PARAM_SOCIAL_FIELD => 'Invalid user field',
      API_EC_PARAM_ALBUM_ID    => 'Invalid album id',
      API_EC_PERMISSION        => 'Permissions error',
      API_EC_PERMISSION_USER   => 'User not visible',
      API_EC_PERMISSION_ALBUM  => 'Album not visible',
      API_EC_PERMISSION_PHOTO  => 'Photo not visible',
      FQL_EC_PARSER            => 'FQL: Parser Error',
      FQL_EC_UNKNOWN_FIELD     => 'FQL: Unknown Field',
      FQL_EC_UNKNOWN_TABLE     => 'FQL: Unknown Table',
      FQL_EC_NOT_INDEXABLE     => 'FQL: Statement not indexable',
      FQL_EC_UNKNOWN_FUNCTION  => 'FQL: Attempted to call unknown function',
      FQL_EC_INVALID_PARAM     => 'FQL: Invalid parameter passed in',
      API_EC_DATA_UNKNOWN_ERROR => 'Unknown data store API error',
      API_EC_DATA_INVALID_OPERATION => 'Invalid operation',
      API_EC_DATA_QUOTA_EXCEEDED => 'Data store allowable quota was exceeded',
      API_EC_DATA_OBJECT_NOT_FOUND => 'Specified object cannot be found',
      API_EC_DATA_OBJECT_ALREADY_EXISTS => 'Specified object already exists',
      API_EC_DATA_DATABASE_ERROR => 'A database error occurred. Please try again',
      API_EC_BATCH_ALREADY_STARTED => 'begin_batch already called, please make sure to call end_batch first',
      API_EC_BATCH_NOT_STARTED => 'end_batch called before start_batch',
      API_EC_BATCH_METHOD_NOT_ALLOWED_IN_BATCH_MODE => 'this method is not allowed in batch mode',
  );
}
