Negotiate-Ext module
====================

The Negotiate-Ext module implements external authentication mechanism.
It is intended to support Kerberos SPNEGO or other GSSAPI by
leveraging Apache modules. In fact it can work with any Apache
authentication source providing REMOTE_USER.

It is based on the Negotiate module but does not require php-krb5.

negotiateext:Negotiate
:     Authenticates users via HTTP authentication

negotiateext:Negotiate
----------------------

Negotiate implements the following mechanics:

 * Initiate HTTP_AUTHN with the client
 * Authorize user against a LDAP directory
 * Collect metadata from LDAP directory
 * Fall back to other SimpleSamlPhp module for any client/user that
   fails to authenticate in the Negotiate-Ext module
 * Check only clients from a certain subnet
 * Supports enabling/disabling a client

In effect this module aims to extend the Microsoft AD or FreeIPA
Kerberos SSO session to the SAML IdP. It doesn't work like this
of course but for the user the client is automatically authenticated
when an SP sends the client to the IdP. In reality Negotiate
authenticates the user via SPNEGO and issues a separate SAML session.
The Kerberos session against the Authentication Server is completely
separate from the SAML session with the IdP. The only time the
Kerberos session affects the SAML session is at authN at the IdP.

The module is meant to supplement existing auth modules and not
replace them. Users do not always log in on the IdP from a machine in
the Windows domain (or another Kerberos domain) and from their own
domain accounts. A fallback mechanism must be supplemented.

The Kerberos TGS can be issued for a wide variety of accounts so an
authoriation backend via LDAP is needed. If the search, with filters,
fails, the fallback in invoked. This to prevent kiosk accounts and the
likes to get faulty SAML sessions.

The subnet is required to prevent excess attempts to authenticate via
Kerberos for clients that always will fail. Worst case scenario the
browser will prompt the user for u/p in a popup box that will always
fail. Only when the user clicks cancel the proper login process will
continue. This is handled through the body of the 401 message the
client recieves with the Negotiate request. In the body a URL to the
fallback mechanism is supplied and meta-refresh is used to redirect the
client.

All configuration is handled in authsources.php:

     'weblogin' => array(
             'negotiateext:Negotiate',
             'fallback' => 'ldap',
             'hostname' => 'ldap.example.com',
             'enable_tls' => TRUE,
             'base' => 'cn=people,dc=example,dc=com',
             'adminUser' => 'cn=idp-fallback,cn=services,dc=example,dc=com',
             'adminPassword' => 'VerySecretPassphraseHush'
     ),
     'ldap' => array(
             'ldap:LDAP',
             'hostname' => 'ldap.example.com',
             'enable_tls' => TRUE,
             'timeout' => 10,
             'dnpattern' => 'uid=%username%,cn=people,dc=example,dc=com',
             'search.enable' => FALSE
     ),



Authentication handling
-----------------------

The processing involving the actual Authentication handling is done
by the web server. For Apache httpd, you can freely use `mod_auth_kerb` or
`mod_auth_gssapi`. 

You **must** configure protection on the `auth.php` script properly as
follows: (Example for `mod_auth_kerb`)

    <LocationMatch /negotiateext/auth.php>
        AuthType KerberosV5
        KrbMethodK5Passwd off
        KrbMethodK4Passwd off

        Krb5Keytab /etc/httpd.keytab
        AuthName "Kerberos SSO"
        Require valid-user
        ErrorDocument 401 /module.php/negotiateext/error.php
    </LocationMatch>

Note that you need to adjust the path to the ErrorDocument according
to the base of your SimpleSAMLphp installation.

LDAP
----

LDAP is used to verify the user due to the lack of metadata in
Kerberos. A domain can contain lots of kiosk users, non-personal
accounts and the likes. The LDAP lookup will authorize and fetch
attributes as defined by SimpleSAMLphp metadata.

`hostname`, `enable_tls`, `debugLDAP`, `timeout` and `base` are
self-explanatory. Read the documentation of the LDAP auth module for
more information. `attr` is the attribute that will be used to look up
user objects in the directory after extracting it from the Kerberos
session. Default is `uid`.

For LDAP directories with restricted access to objects or attributes
Negotiate implements `adminUser` and `adminPassword`. adminUser must
be a DN to an object with access to search for all relevant user
objects and to look up attributes needed by the SP.


Subnet filtering
----------------

Subnet is meant to filter which clients you subject to the
WWW-Authenticate request.

Syntax is:

     'subnet' => array('127.0.0.0/16','192.168.0.0/16'),

Browsers, especially IE, behave erratically when they encounter a
WWW-Authenticate from the webserver. Included in RFC4559 Negotiate is
NTLM authentication which IE seems prone to fall back to under various
conditions. This triggers a popup login box which defeats the whole
purpose of this module.

TBD: Replace or supplement with LDAP lookups in the domain. Machines
currently in the domain should be the only ones that are promted with
WWW-Authenticate: Negotiate.


Enabling/disabling Negotiate from a web browser
-----------------------------------------------

Included in Negotiate are semi-static web pages for enabling and
disabling Negotiate for any given client. The pages simple set/deletes
a cookie that Negotiate will look for when a client attempts AuthN.
The help text in the JSON files should be locally overwritten to fully
explain which clients are accepted by Negotiate.


Logout/Login loop and reauthenticating
--------------------------------------

Due to the automatic AuthN of certain clients and how SPs will
automatically redirect clients to the IdP when clients try to access
restricted content, a session variable has been put into Negotiate. This
variable makes sure Negotiate doesn't reautenticate a recently logged
out user. The consequence of this is that the user will be presented
with the login mechanism of the fallback module specified in Negotiate
config.

SimpleSamlPhp offers no decent way of adding hooks or piggyback this
information to the fallback module. In future releases one might add a
box of information to the user explaining what's happening.

One can add this bit of code to the template in the fallback AuthN
module:

    // This should be placed in your www script modules/core/www/loginuserpass.php

    $nego_perm = FALSE;
    $nego_retry = NULL;
    if (is_array($state) && array_key_exists('negotiate:authId', $state)) {
        $nego = SimpleSAML_Auth_Source::getById($state['negotiate:authId']);
        $mask = $nego->checkMask();
        $disabled = $nego->spDisabledInMetadata($spMetadata);
        $session = SimpleSAML_Session::getSessionFromRequest();
        $session_disabled = $session && $session->getData('negotiate:disable', 'session');
        if ($mask and !$disabled) {
            if(array_key_exists('NEGOTIATE_AUTOLOGIN_DISABLE_PERMANENT', $_COOKIE) &&
               $_COOKIE['NEGOTIATE_AUTOLOGIN_DISABLE_PERMANENT'] == 'True') {
                $nego_perm = TRUE;
            } else {
                $retryState = SimpleSAML_Auth_State::cloneState($state);
                unset($retryState[SimpleSAML_Auth_State::ID]);
                $nego_retry = SimpleSAML_Auth_State::saveState($retryState, 'sspmod_negotiateext_Auth_Source_Negotiate.StageId');
                $nego_session = TRUE;
            }
        }
    }
    $t->data['nego'] = array (
        'disable_perm' => $nego_perm,
        'retry_id'     => $nego_retry,
    );

-

    // This should reside in your template modules/core/templates/loginuserpass.php

    <?php
    if($this->data['nego']['disable_perm']) {
        echo '<span id="login-extra-info-uio.no" class="login-extra-info">'
              . '<span class="login-extra-info-divider"></span>'
              . $this->t('{negotiate:negotiate:disabled_info}')
              . '</span>';
    } elseif($this->data['nego']['retry_id']) {
         echo '<span id="login-extra-info-uio.no" class="login-extra-info">'
              . '<span class="login-extra-info-divider"></span>'
              . $this->t('{negotiate:negotiate:failed_info}')
              . ' <a class="btn" href="'.SimpleSAML_Module::getModuleURL('negotiateext/retry.php', array('AuthState' => $this->data['nego']['retry_id'])).'">'
              . $this->t('{negotiate:negotiate:retry_link}')
              . '</a>'
              . '</span>';
    }
    ?>


The above may or may not work right out of the box for you but it is
the gist of it. By looking at the state variable, cookie and checking
for filters and the likes, only clients that are subjected to
Negotiate should get the help text.

Note that with Negotiate there is also a small script to allow the
user to re-authenticate with Negotiate after being sent to the
fallback mechanism due to the session cookie. In the example above you
can see the construction of the URL. The cloning of the current state
is necessary for retry.php to load a state without triggering a
security check in SSP's state handling library. If you omit this and
pass on the original state you will see a warning in the log like
this:

    Sep 27 13:47:36 simplesamlphp WARNING [b99e6131ee] Wrong stage in state. Was 'foo', should be 'sspmod_negotiateext_Auth_Source_Negotiate.StageId'.

It will work as loadState will take controll and call
Negotiate->authenticate() but remaining code in retry.php will be
discarded. Other side-effects may occur.


Clients
-------

* Internet Explorer

YMMV but generally you need to have your IdP defined in "Internet
Options" -> "Security" -> "Local intranet" -> "Sites" -> "Advanced".
You also need "Internet Options" -> "Advanced" -> "Security" -> Enable
Integrated Windows Authentication" enabled.

* Firefox

Open "about:config". Locate "network.auth.use-sspi" and verify that
this is true (on a Windows machine). Next locate
"network.negotiate-auth.trusted-uris" and insert your IdP.

* Safari

TODO

* Chrome

Chrome on Linux: Create a file /etc/opt/chrome/policies/managed/company_policy.json
with the following content:

    {"AuthServerWhitelist":"your.idp.host"}

Chrome on OSX: Configure AuthServerWhitelist using Apple Workgroup Manager as detailed on https://www.chromium.org/administrators/mac-quick-start 
