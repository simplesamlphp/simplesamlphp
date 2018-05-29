<?php
/*
$this->data['header'] = $this->t('{login:user_pass_header}');

if (strlen($this->data['username']) > 0) {
	$this->data['autofocus'] = 'password';
} else {
	$this->data['autofocus'] = 'username';
}
$this->includeAtTemplateBase('includes/header.php');
*/
?>

<?php
// - o - o - o - o - o - o - o - o - o - o - o - o -

/**
 * Do not allow to frame simpleSAMLphp pages from another location.
 * This prevents clickjacking attacks in modern browsers.
 *
 * If you don't want any framing at all you can even change this to
 * 'DENY', or comment it out if you actually want to allow foreign
 * sites to put simpleSAMLphp in a frame. The latter is however
 * probably not a good security practice.
 */
header('X-Frame-Options: SAMEORIGIN');

if (!function_exists('curl_init')) {
  die('This module needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  die('This module script needs the JSON PHP extension.');
}


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
<meta name="robots" content="noindex, nofollow" />
<script src="<?php echo(SimpleSAML_Module::getModuleURL('authjanrain/resources/scripts/janrain-utils.js')); ?>"></script>
    <script language="javascript">
    /*
    Initializations and settings for the Capture Widget.

    For more information about these settings, see the following documents:

        http://developers.janrain.com/documentation/widgets/social-sign-in-widget/social-sign-in-widget-api/settings/
        http://developers.janrain.com/documentation/widgets/user-registration-widget/capture-widget-api/settings/
    */

    (function() {
        // Check for settings. If there are none, create them
        if (typeof window.janrain !== 'object') window.janrain = {};
        if (typeof window.janrain.settings !== 'object') window.janrain.settings = {};
        if (typeof window.janrain.settings.capture !== 'object') window.janrain.settings.capture = {};

        // Load Engage and Capture. 'login' is Engage, 'capture' is Capture.
        // Changing these values without guidance can result in unexpected behavior.
        janrain.settings.packages = ['login', 'capture'];



        /*--- Application Settings -----------------------------------------------*\

            When transitioning from a development to production, these are the
            settings that need to be changed. Others may also need to be changed if
            you have purchased optional products and features, such as Federate.
            Those settings are located below.

            janrain.settings.appUrl:
                The URL of your Engage application.
                Example: https://your-company.rpxnow.com

            janrain.settings.capture.captureServer:
                The URL of your Capture application.
                Example: https://your-company.janraincapture.com

            janrain.settings.capture.appId:
                The the application ID of your Capture application.

            janrain.settings.capture.clientId:
                The client ID of the Capture application.

            Example Dev Configuration:
                janrain.settings.appUrl                = 'https://your-company-dev.rpxnow.com';
                janrain.settings.capture.captureServer = 'https://your-company-dev.janraincapture.com';
                janrain.settings.capture.appId         = <DEV CAPTURE APP ID>;
                janrain.settings.capture.clientId      = <DEV CAPTURE CLIENT ID>;
                var httpLoadUrl                        = "https://rpxnow.com/load/your-company-dev";
                var httpsLoadUrl                       = "http://widgets-cdn.rpxnow.com/load/your-company-dev";

            Example Prod Configuration:
                janrain.settings.appUrl                = 'https://login.yourcompany.com';
                janrain.settings.capture.captureServer = 'https://your-company.janraincapture.com';
                janrain.settings.capture.appId         = <PROD CAPTURE APP ID>;
                janrain.settings.capture.clientId      = <PROD CAPTURE CLIENT ID>;
                var httpLoadUrl                        = "https://rpxnow.com/load/login.yourcompany.com";
                var httpsLoadUrl                       = "http://widgets-cdn.rpxnow.com/load/login.yourcompany.com";
        \*------------------------------------------------------------------------*/

        janrain.settings.appUrl                = 'https://APPNAME.rpxnow.com';
        janrain.settings.capture.captureServer = 'https://APPNAME.janraincapture.com';
        janrain.settings.capture.appId         = 'CAPTURE_APP_ID';
        janrain.settings.capture.clientId      = 'CAPTURE_API_CLIENT_ID';

        // These are the URLs for your Engage app's load.js file, which is necessary
        // to load the Capture Widget.
        var httpLoadUrl  = "http://widget-cdn.rpxnow.com/load/APPNAME";
        var httpsLoadUrl = "https://rpxnow.com/load/APPNAME";


        // --- Engage Widget Settings ----------------------------------------------
        janrain.settings.language = 'en-US';
        janrain.settings.tokenUrl = 'http://localhost/';
        janrain.settings.tokenAction = 'event';
        janrain.settings.borderColor = '#ffffff';
        janrain.settings.fontFamily = 'Helvetica, Lucida Grande, Verdana, sans-serif';
        janrain.settings.width = 300;
        janrain.settings.actionText = ' ';



        // --- Capture Widget Settings ---------------------------------------------
        janrain.settings.capture.redirectUri = 'http://localhost/';
        janrain.settings.capture.flowName = 'saml';
        janrain.settings.capture.flowVersion = 'HEAD';
        janrain.settings.capture.registerFlow = 'socialRegistration';
        janrain.settings.capture.setProfileCookie = true;
        janrain.settings.capture.keepProfileCookieAfterLogout = true;
        janrain.settings.capture.modalCloseHtml = 'X';
        janrain.settings.capture.noModalBorderInlineCss = true;
        janrain.settings.capture.responseType = 'token';
        janrain.settings.capture.returnExperienceUserData = ['displayName'];
        //janrain.settings.capture.stylesheets = ['styles/janrain.css'];
        //janrain.settings.capture.mobileStylesheets = ['styles/janrain-mobile.css'];
        janrain.settings.capture.stylesheets = [];
        janrain.settings.capture.mobileStylesheets = [];


        // --- Mobile WebView ------------------------------------------------------
        //janrain.settings.capture.redirectFlow = true;
        //janrain.settings.popup = false;
        //janrain.settings.tokenAction = 'url';
        //janrain.settings.capture.registerFlow = 'socialMobileRegistration'



        // --- Federate ------------------------------------------------------------
        //janrain.settings.capture.federate = true;
        //janrain.settings.capture.federateServer = '';
        //janrain.settings.capture.federateXdReceiver = '';
        //janrain.settings.capture.federateLogoutUri = '';
        //janrain.settings.capture.federateLogoutCallback = function() {};
        //janrain.settings.capture.federateEnableSafari = false;



        // --- Backplane -----------------------------------------------------------
        //janrain.settings.capture.backplane = true;
        //janrain.settings.capture.backplaneBusName = '';
        //janrain.settings.capture.backplaneVersion = 2;
        //janrain.settings.capture.backplaneBlock = 20;




        // --- BEGIN WIDGET INJECTION CODE -----------------------------------------
        /********* WARNING: *******************************************************\
        |      DO NOT EDIT THIS SECTION                                            |
        | This code injects the Capture Widget. Modifying this code can cause the  |
        | Widget to load incorrectly or not at all.                                |
        \**************************************************************************/

        function isReady() {
            janrain.ready = true;
        }
        if (document.addEventListener) {
            document.addEventListener("DOMContentLoaded", isReady, false);
        } else {
            window.attachEvent('onload', isReady);
        }

        var injector = document.createElement('script');
        injector.type = 'text/javascript';
        injector.id = 'janrainAuthWidget';
        if (document.location.protocol === 'https:') {
            injector.src = httpsLoadUrl;
        } else {
            injector.src = httpLoadUrl;
        }
        var firstScript = document.getElementsByTagName('script')[0];
        firstScript.parentNode.insertBefore(injector, firstScript);

        // --- END WIDGET INJECTION CODE -------------------------------------------

    })();



    // This function is called by the Capture Widget when it has completred loading
    // itself and all other dependencies. This function is required, and must call
    // janrain.capture.ui.start() for the Widget to initialize correctly.
    function janrainCaptureWidgetOnLoad() {
       
        /*==== CUSTOM ONLOAD CODE START ==========================================*\
        ||  Any javascript that needs to be run before screens are rendered but   ||
        ||  after the Widget is loaded should go between this comment and "CUSTOM ||
        ||  ONLOAD CODE END" below.                                               ||
        \*                                                                        */

        /*--
            SCREEN TO RENDER:
            This setting defines which screen to render. We've set it to the result
            of implFuncs.getParameterByName() so that if you pass in a parameter
            in your URL called 'screenToRender' and provide a valid screen name,
            that screen will be shown when the Widget loads.
                                                                                --*/
        janrain.settings.capture.screenToRender = '';


        janrain.events.onCaptureSessionFound.addHandler(function(result) {
            janrain.capture.ui.endCaptureSession();
        });

        janrain.events.onCaptureLoginSuccess.addHandler(function(result) {
            janrain.capture.ui.endCaptureSession();
        });
        
        janrain.events.onCaptureRegistrationSuccess.addHandler(function(result) {
            janrain.capture.ui.endCaptureSession();
        });

        janrain.events.onCaptureSessionEnded.addHandler(function(result) {
            document.getElementById("samlForm").submit();
        });
        /*--
            SHOW EVENTS:
            Uncomment this line to show events in your browser's console. You must
            include janrain-utils.js to run this function.
                                                                                --*/
        janrainUtilityFunctions().showEvents();


        /*                                                                        *\
        || *** CUSTOM ONLOAD CODE END ***                                         ||
        \*========================================================================*/

        // This should be the last line in janrainCaptureWidgetOnLoad()
        janrain.capture.ui.start();
    }

</script>
<title><?php
if(array_key_exists('header', $this->data)) {
	echo $this->data['header'];
} else {
	echo 'Janrain Registration';
}
?></title>

	<link rel="stylesheet" type="text/css" href="<?php echo(SimpleSAML_Module::getModuleURL('authjanrain/resources/styles/janrain.css')); ?>" />
	<link rel="stylesheet" type="text/css" href="<?php echo(SimpleSAML_Module::getModuleURL('authjanrain/resources/styles/janrain-mobile.css')); ?>" />

<?php	
if(array_key_exists('head', $this->data)) {
	echo '<!-- head -->' . $this->data['head'] . '<!-- /head -->';
}
?>
</head>
<?php
$onLoad = '';
/*
if(array_key_exists('autofocus', $this->data)) {
	//$onLoad .= 'SimpleSAML_focus(\'' . $this->data['autofocus'] . '\');';
}
if (isset($this->data['onLoad'])) {
	$onLoad .= $this->data['onLoad']; 
}

if($onLoad !== '') {
	$onLoad = ' onload="' . $onLoad . '"';
}
*/
?>
<body<?php echo $onLoad; ?>>

<?php
if ($this->data['errorcode'] !== NULL) {
?>
	<div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/experience/gtk-dialog-error.48x48.png" class="float-l erroricon" style="margin: 15px " />
		<h2><?php echo $this->t('{login:error_header}'); ?></h2>
		<p><b><?php echo htmlspecialchars($this->t('{errors:title_' . $this->data['errorcode'] . '}', $this->data['errorparams'])); ?></b></p>
		<p><?php echo htmlspecialchars($this->t('{errors:descr_' . $this->data['errorcode'] . '}', $this->data['errorparams'])); ?></p>
	</div>
<?php
}
?>


    <!--
    ============================================================================
        SIGNIN SCREENS:
        The following screens are part of the sign in user workflow. For a
        complete out-of-the-box sign in experience, these screens must be
        included on the page where you are implementing sign in and registration.
    ============================================================================
    -->

    <!-- signIn:
    This is the starting point for sign in and registration. This screen is
    rendered by default. In order to change this behavior, the Flow must be
    edited.
    -->
    <div style="display:none;" id="signIn">
        <div class="capture_header">
            <h1>Sign Up / Sign In</h1>
        </div>
        <div class="capture_signin">
            <h2>With your existing account from...</h2>
            {* loginWidget *} <br />
        </div>
        <div class="capture_backgroundColor">
            <div class="capture_signin">
                <h2>With a traditional account...</h2>
                {* #signInForm *}
                    {* signInEmailAddress *}
                    {* currentPassword *}
                    <div class="capture_form_item">
                        <a href="#" data-capturescreen="forgotPassword">Forgot your password?</a>
                    </div>
                    <div class="capture_rightText">
                        <button class="capture_secondary capture_btn capture_primary" type="submit"><span class="janrain-icon-16 janrain-icon-key"></span> Sign In</button>
                        <a href="#" id="capture_signIn_createAccountButton" data-capturescreen="traditionalRegistration" class="capture_secondary capture_createAccountButton capture_btn capture_primary">Create Account</a>
                    </div>
                {* /signInForm *}
            </div>
        </div>
    </div>

    <!-- returnSocial:
    This is the screen the user sees in place of the signIn screen if they've
    already signed in with a social account on this site. Rendering of this
    screen is defined in the Flow only when the 'janrainLastAuthMethod' cookie
    is set to'socialSignin'.
    -->
    <div style="display:none;" id="returnSocial">
        <div class="capture_header">
            <h1>Sign In</h1>
        </div>
        <div class="capture_signin">
            <h2>Welcome back, {* welcomeName *}!</h2>
            {* loginWidget *}
            <div class="capture_centerText switchLink"><a href="#" data-cancelcapturereturnexperience="true">Use another account</a></div>
        </div>
    </div>

    <!-- returnTraditional:
    This is the screen the user sees in place of the signIn screen if they've
    already signed in with a traditional account on this site. Rendering of this
    screen is defined in the Flow only when the 'janrainLastAuthMethod' cookie
    is set to'traditionalSignin'.
    -->
    <div style="display:none;" id="returnTraditional">
        <div class="capture_header">
            <h1>Sign In</h1>
        </div>
        <h2 class="capture_centerText"><span id="traditionalWelcomeName">Welcome back!</span></h2>
        <div class="capture_backgroundColor">
            {* #signInForm *}
                {* signInEmailAddress *}
                {* currentPassword *}
                <div class="capture_form_item capture_rightText">
                    <button class="capture_secondary capture_btn capture_primary" type="submit"><span class="janrain-icon-16 janrain-icon-key"></span> Sign In</button>
                </div>
            {* /signInForm *}
            <div class="capture_centerText switchLink"><a href="#" data-cancelcapturereturnexperience="true">Use another account</a></div>
        </div>
    </div>

    <!-- accountDeactivated:
        This screen is rendered if the user's account is deactivated. Screen
        rendering is handled in janrain-init.js.
    -->
    <div style="display:none;" id="accountDeactivated">
        <div class="capture_header">
            <h1>Deactivated Account </h1>
        </div>
        <div class="content_wrapper">
            <p>Your account has been deactivated.</p>
        </div>
    </div>



    <!--
    ============================================================================
        REGISTRATION SCREENS:
        The following screens are part of the registration user workflow. For a
        complete out-of-the-box registration experience, these screens must be
        included on the page where you are implementing sign in and
        registration.
    ============================================================================
    -->

    <!-- socialRegistration:
        When a user clicks an IDP and does not already have an account in your
        capture application, this screen is rendered. This behavior is defined
        in the Flow.
    -->
    <div style="display:none;" id="socialRegistration">
        <div class="capture_header">
            <h1>Almost Done!</h1>
        </div>
        <h2>Please confirm the information below before signing in.</h2>
        {* #socialRegistrationForm *}
            {* firstName *}
            {* lastName *}
            {* emailAddress *}
            {* displayName *}
            By clicking "Sign in", you confirm that you accept our <a href="#">terms of service</a> and have read and understand <a href="#">privacy policy</a>.
            <div class="capture_footer">
                <div class="capture_left">
                    {* backButton *}
                </div>
                <div class="capture_right">
                    <input value="Create Account" type="submit" class="capture_btn capture_primary">
                </div>
            </div>
        {* /socialRegistrationForm *}
    </div>

    <!-- traditionalRegistration:
        When a user clicks the 'Create Account' button this screen is rendered.
    -->
    <div style="display:none;" id="traditionalRegistration">
        <div class="capture_header">
            <h1>Almost Done!</h1>
        </div>
        <p>Please confirm the information below before signing in. Already have an account? <a id="capture_traditionalRegistration_navSignIn" href="#" data-capturescreen="signIn">Sign In.</a></p>
        {* #registrationForm *}
            {* firstName *}
            {* lastName *}
            {* emailAddress *}
            {* displayName *}
            {* newPassword *}
            {* newPasswordConfirm *}
            By clicking "Create Account", you confirm that you accept our <a href="#">terms of service</a> and have read and understand <a href="#">privacy policy</a>.
            <div class="capture_footer">
                <div class="capture_left">
                    {* backButton *}
                </div>
                <div class="capture_right">
                    <input value="Create Account" type="submit" class="capture_btn capture_primary">
                </div>
            </div>
        {* /registrationForm *}
    </div>

    <!-- emailVerificationNotification:
        This screen is rendered after a user has registered. In the case of
        traditional registration, this screen is always rendered after the user
        completes registration on the traditionalRegistration screen. In the
        case of social registration, this screen is only rendered if the data
        returned from the IDP does not contain a verified email address.
        Twitter is an example of an IDP that does not return a verified email.
    -->
    <div style="display:none;" id="emailVerificationNotification">
        <div class="capture_header">
            <h1>Thank you for registering!</h1>
        </div>
        <p>We have sent a confirmation email to {* emailAddressData *}. Please check your email and click on the link to activate your account.</p>
        <div class="capture_footer">
            <a href="#" onclick="showScreen('signIn');" class="capture_btn capture_primary">Close</a>
        </div>
    </div>




    <!--
    ============================================================================
        FORGOT PASSWORD SCREENS:
        The following screens are part of the forgot password user workflow. For
        a complete out-of-the-box registration experience, these screens must be
        included on the page where you are implementing forgot password
        functionality.
    ============================================================================
    -->

    <!-- forgotPassword:
        Entry point into the forgot password user workflow. This screen is
        rendered when the user clicks on the 'Forgot your password?' link on the
        signIn screen.
    -->
    <div style="display:none;" id="forgotPassword">
        <div class="capture_header">
            <h1>Create a new password</h1>
        </div>
        <h2>We'll send you a link to create a new password.</h2>
        {* #forgotPasswordForm *}
            {* signInEmailAddress *}
            <div class="capture_footer">
                <div class="capture_left">
                    {* backButton *}
                </div>
                <div class="capture_right">
                    <input value="Send" type="submit" class="capture_btn capture_primary">
                </div>
            </div>
        {* /forgotPasswordForm *}
    </div>

    <!-- forgotPasswordSuccess:
        When the user submits an email address on the forgotPassword screen,
        this screen is rendered.
    -->
    <div style="display:none;" id="forgotPasswordSuccess">
        <div class="capture_header">
            <h1>Create a new password</h1>
        </div>
            <p>We've sent an email with instructions to create a new password. Your existing password has not been changed.</p>
        <div class="capture_footer">
            <a href="#" onclick="showScreen('signIn');" class="capture_btn capture_primary">Close</a>
        </div>
    </div>




    <!--
    ============================================================================
        MERGE ACCOUNT SCREENS:
        The following screens are part of the account merging user workflow. For
        a complete out-of-the-box account merging experience, these screens must
        be included on the page where you are implementing account merging
        functionality.
    ============================================================================
    -->

    <!-- mergeAccounts:
        This screen is rendered if the user created their account through
        traditional registration and then tries to sign in with an IDP that
        shares the same email address that exists in their user record.

        NOTE! You will notice special tags you see on this screen. These tags,
        such as '{| current_displayName |}' are rendered by the Janrain Capture
        Widget in a way similar to JTL tags, but are more limited. We currently
        only support modifying the text in this screen through the Flow. You
        can, however, add your own markup and text throughout this screen as you
        see fit.
    -->
    <div style="display:none;" id="mergeAccounts">
        {* mergeAccounts {"custom": true} *}
        <div id="capture_mergeAccounts_mergeAccounts_mergeOptionsContainer" class="capture_mergeAccounts_mergeOptionsContainer">
            <div class="capture_header">
                <div class="capture_icon_col">
                    {| rendered_current_photo |}
                </div>
                <div class="capture_displayName_col">
                    {| current_displayName |}<br />
                    {| current_emailAddress |}
                </div>
                <span class="capture_mergeProvider janrain-provider-icon-24 janrain-provider-icon-{| current_provider_lowerCase |}"></span>
            </div>
            <div class="capture_dashed">
                <div class="capture_mergeCol capture_centerText capture_left">
                    <p class="capture_bigText">{| foundExistingAccountText |} <b>{| current_emailAddress |}</b>.</p>
                    <div class="capture_hover">
                        <div class="capture_popup_container">
                            <span class="capture_popup-arrow"></span>{| moreInfoHoverText |}<br />
                            {| existing_displayName |} - {| existing_provider |} : {| existing_siteName |} {| existing_createdDate |}
                        </div>
                        {| moreInfoText |}
                    </div>
                </div>
                <div class="capture_mergeCol capture_mergeExisting_col capture_right">
                    <div class="capture_shadow capture_backgroundColor capture_border">
                        {| rendered_existing_provider_photo |}
                        <div class="capture_displayName_col">
                            {| existing_displayName |}<br />
                            {| existing_provider_emailAddress |}
                        </div>
                        <span class="capture_mergeProvider janrain-provider-icon-16 janrain-provider-icon-{| existing_provider_lowerCase |} "></span>
                        <div class="capture_centerText capture_smallText">Created {| existing_createdDate |} at {| existing_siteName |}</div>
                    </div>
                </div>
            </div>
            <div id="capture_mergeAccounts_form_collection_mergeAccounts_mergeRadio" class="capture_form_collection_merge_radioButtonCollection capture_form_collection capture_elementCollection capture_form_collection_mergeAccounts_mergeRadio" data-capturefield="undefined">
                <div id="capture_mergeAccounts_form_item_mergeAccounts_mergeRadio_1_0" class="capture_form_item capture_form_item_mergeAccounts_mergeRadio capture_form_item_mergeAccounts_mergeRadio_1_0 capture_toggled" data-capturefield="undefined">
                    <label for="capture_mergeAccounts_mergeAccounts_mergeRadio_1_0">
                        <input id="capture_mergeAccounts_mergeAccounts_mergeRadio_1_0" data-capturefield="undefined" data-capturecollection="true" value="1" type="radio" class="capture_mergeAccounts_mergeRadio_1_0 capture_input_radio" checked="checked" name="mergeAccounts_mergeRadio">
                            {| connectLegacyRadioText |}
                    </label>
                </div>
                <div id="capture_mergeAccounts_form_item_mergeAccounts_mergeRadio_2_1" class="capture_form_item capture_form_item_mergeAccounts_mergeRadio capture_form_item_mergeAccounts_mergeRadio_2_1" data-capturefield="undefined">
                    <label for="capture_mergeAccounts_mergeAccounts_mergeRadio_2_1">
                        <input id="capture_mergeAccounts_mergeAccounts_mergeRadio_2_1" data-capturefield="undefined" data-capturecollection="true" value="2" type="radio" class="capture_mergeAccounts_mergeRadio_2_1 capture_input_radio" name="mergeAccounts_mergeRadio">
                            {| createRadioText |} {| current_provider |}
                    </label>
                </div>
                <div class="capture_tip" style="display:none;">
            </div>
                <div class="capture_tip_validating" data-elementname="mergeAccounts_mergeRadio">Validating</div>
                <div class="capture_tip_error" data-elementname="mergeAccounts_mergeRadio"></div>
            </div>
            <div class="capture_footer">
                {| connect_button |}
                {| create_button |}
            </div>
        </div>
    </div>

    <!-- traditionalAuthenticateMerge:
        When the user elects to merge their traditional and social account, the
        user will see this screen. They will then enter their current sign in
        credentials and, upon successful authorization, the accounts will be
        merged.
    -->
    <div style="display:none;" id="traditionalAuthenticateMerge">
        <div class="capture_header">
            <h1>Sign in to complete account merge</h1>
        </div>
        <div class="capture_signin">
            {* #signInForm *}
                {* signInEmailAddress *}
                {* currentPassword *}
                <div class="capture_footer">
                    <div class="capture_left">
                        {* backButton *}
                    </div>
                    <div class="capture_right">
                        <button class="capture_secondary capture_btn capture_primary" type="submit"><span class="janrain-icon-16 janrain-icon-key"></span> Sign In</button>
                    </div>
                </div>
             {* /signInForm *}
        </div>
    </div>




    <!--
    ============================================================================
        EMAIL VERIFICATION SCREENS:
        The following screens are part of the email verification user workflow.
        For a complete out-of-the-box email verification experience, these
        screens must be included on page where you are implementing email
        verification.
    ============================================================================
    -->

    <!-- verifyEmail:
        This is the landing screen after a user clicks on the link in the
        verification email sent to the user when they've registered with a
        non-verified email address.

        HOW IT WORKS: The code that is generated by Capture and included in the
        link sent in the verification email is sent to the server and, if valid,
        the user's email will be marked as valid and the verifyEmailSuccess
        screen will be rendered. If the code is not accepted for any reason,
        the verifyEmail screen is shown and the user has another opportunity
        to have the verification email sent to them.

        NOTE: The links generated in the emails sent to users are based on
        Capture settings found in Janrain's Capture Dashboard. In addition to
        entering the URL of your email verification page, you will need to add
        'screenToRender' as a parameter in the URL with a value of 'verifyEmail'
        which is this screen.
    -->
    <div style="display:none;" id="verifyEmail">
        <div class="capture_header">
            <h1>Resend Email Verification</h1>
        </div>
        <p>Sorry we could not verify that email address. Enter your email below and we'll send you another email.</p>
        {* #resendVerificationForm *}
            {* signInEmailAddress *}
            <div class="capture_footer">
                <input value="Submit" type="submit" class="capture_btn capture_primary">
            </div>
         {* /resendVerificationForm *}
    </div>

    <!-- resendVerificationSuccess:
        This screen is rendered when a user enters an email address from the
        verifyEmail screen.
    -->
    <div style="display:none;" id="resendVerificationSuccess">
        <div class="capture_header">
            <h1>Your Verification Email Has Been Sent</h1>
        </div>
        <div class="hr"></div>
        <p>Check your email for a link to reset your password.</p>
        <div class="capture_footer">
            <a href="index.html" class="capture_btn capture_primary">Sign in</a>
        </div>
    </div>

    <!-- verifyEmailSuccess:
        This screen is rendered if the verification code provided in the link
        sent to the user in the verification email is accepted and the user's
        email address has been verified.
    -->
    <div style="display:none;" id="verifyEmailSuccess">
        <div class="capture_header">
            <h1>You did it!</h1>
        </div>
        <p>Thank you for verifiying your email address.
        <div class="capture_footer">
            <a href="index.html" class="capture_btn capture_primary">Sign in</a>
        </div>
    </div>




    <!--
    ============================================================================
        RESET PASSWORD SCREENS:
        The following screens are part of the password reset user workflow.
        For a complete out-of-the-box password reset experience, these screens
        must be included on the page where you are implementing password reset
        functionality.

        NOTE: The order in which these screens are rendered is as follows:
        resetPasswordRequestCode
        resetPasswordRequestCodeSuccess
        resetPassword
        resetPasswordSuccess
    ============================================================================
    -->

    <!-- resetPassword:
        This screen is rendered when the user clicks the link in provided in the
        password reset email and the code in the link is valid.
    -->
    <div style="display:none;" id="resetPassword">
        <div class="capture_header">
            <h1>Change password</h1>
        </div>
        {* #changePasswordFormNoAuth *}
            {* newPassword *}
            {* newPasswordConfirm *}
            <div class="capture_footer">
                <input value="Submit" type="submit" class="capture_btn capture_primary">
            </div>
        {* /changePasswordFormNoAuth *}
    </div>
    <!-- resetPasswordSuccess:
        This screen is rendered when the user successfully changes their
        password from the resetPassword screen.
    -->
    <div style="display:none;" id="resetPasswordSuccess">
        <div class="capture_header">
            <h1>Your password has been changed</h1>
        </div>
        <p>Password has been successfully updated.</p>
        <div class="capture_footer">
            <a href="index.html" class="capture_btn capture_primary">Sign in</a>
        </div>
    </div>
    <!-- resetPasswordRequestCode:
        This is the landing screen for the password reset workflow. When the
        user clicks the link provided in the reset password email, a code is
        supplied and is passed to Capture for verification. If the code is valid
        the resetPassword screen is rendered immediately and the content of
        this screen is not presented. If the code is not accepted for any reason
        this screen is then presented, allowing the user to re-enter their
        email address.
    -->
    <div style="display:none;" id="resetPasswordRequestCode">
        <div class="capture_header">
            <h1>Create a new password</h1>
        </div>
        <p>We didn't recognize that password reset code. Enter your email address to get a new one.</p>
        {* #resetPasswordForm *}
            {* signInEmailAddress *}
            <div class="capture_footer">
                <input value="Send" type="submit" class="capture_btn capture_primary">
            </div>
        {* /resetPasswordForm *}
    </div>

    <!-- resetPasswordRequestCodeSuccess:
        This screen is rendered if the user submitted an email address on the
        resetPasswordRequestCode screen.
    -->
    <div style="display:none;" id="resetPasswordRequestCodeSuccess">
        <div class="capture_header">
            <h1>Create a new password</h1>
        </div>
            <p>We've sent an email with instructions to create a new password. Your existing password has not been changed.</p>
        <div class="capture_footer">
            <a href="#" onclick="showScreen('signIn');" class="capture_btn capture_primary">Close</a>
        </div>
    </div>




    <!--
    ============================================================================
        EDIT PROFILE SCREENS:
        The following screens are part of the profile editing user workflow.
        For a complete out-of-the-box profile editing experience, these screens
        must be included on the page where you are implementing profile editing
        functionality.
    ============================================================================
    -->

    <!-- editProfile
        This screen is where the user can edit their profile data. It can be
        rendered in whatever way works best for your implementation, be it
        using the data-capturescreen attribute, janrain.capture.ui.renderScreen
        or passing in 'screenToRender' in the URL linking to the page where
        you have implemented edit profile.
    -->
    <div style="display:none;" id="editProfile">
        <h1>Edit Your Account</h1>
        <div class="capture_grid_block">
            <div class="capture_col_4">
                <h3>Profile Photo</h3>
                <div class="contentBoxWhiteShadow">
                    {* photoManager *}
                </div>
                <h3>Linked Accounts</h3>
                <div class="contentBoxWhiteShadow">
                    {* linkedAccounts *}
                    {* #linkAccountContainer *}
                        <div class="capture_header">
                            <h1>Link your accounts</h1>
                        </div>
                        <h2>Allows you to sign in to your account using that provider in the future.</h2>
                        <div class="capture_signin">
                            {* loginWidget *}
                        </div>
                    {* /linkAccountContainer *}
                </div>
                <!-- Only show this if it was from a traditional login !-->
                <h3 class="janrain_traditional_account_only">Password</h3>
                <div class="janrain_traditional_account_only contentBoxWhiteShadow">
                    <a href="#" data-capturescreen="changePassword">Change Password</a>
                </div>
                <h3 class="janrain_traditional_account_only">Deactivate Account</h3>
                <div class="capture_deactivate_section contentBoxWhiteShadow clearfix">
                    <a href="#" data-capturescreen="confirmAccountDeactivation">Deactivate Account</a>
                </div>
            </div>
            <div class="capture_col_8">
                <h3>Account Info</h3>
                <div class="contentBoxWhiteShadow">
                    <div class="capture_grid_block">
                        <div class="capture_center_col capture_col_8">
                            <div class="capture_editCol">
                                {* #editProfileForm *}
                                    {* firstName *}
                                    {* lastName *}
                                    {* gender *}
                                    {* birthdate *}
                                    {* displayName *}
                                    {* emailAddress *}
                                    {* resendLink *}
                                    {* phone *}
                                    {* addressStreetAddress1 *}
                                    {* addressStreetAddress2 *}
                                    {* addressCity *}
                                    {* addressPostalCode *}
                                    {* addressState *}
                                    {* addressCountry *}
                                    <div class="capture_form_item">
                                        <input value="Save" type="submit" class="capture_btn capture_primary">
                                        {* savedProfileMessage *}
                                    </div>
                                {* /editProfileForm *}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- changePassword:
        This screen is rendered when the user clicks the 'Change Password' link
        on the edit profile page. After the user enters their new password,
        the edit profile screen is refreshed and displayed.
    -->
    <div style="display:none;" id="changePassword">
        <div class="capture_header">
            <h1>Change password</h1>
        </div>
        {* #changePasswordForm *}
            {* currentPassword *}
            {* newPassword *}
            {* newPasswordConfirm *}
            <div class="capture_footer">
                <input value="Save" type="submit" class="capture_btn capture_primary">
            </div>
        {* /changePasswordForm *}
    </div>

    <!-- confirmAccountDeactivation:
        If the user clicks the 'Deactivate Account' link on the edit profile
        page, this screen is rendered. From here, the user can deactivate their
        account.
    -->
    <div style="display:none;" id="confirmAccountDeactivation">
        <div class="capture_header">
            <h1>Deactivate your Account</h1>
        </div>
        <div class="content_wrapper">
            <p>Are you sure you want to deactivate your account? You will no longer have access to your profile.</p>
            {* deactivateAccountForm *}
                    <div class="capture_footer">
                        <input value="Yes" type="submit" class="capture_btn capture_primary">
                        <a href="#" id="capture_confirmAccountDeactivation_noButton" onclick="showScreen('signIn');" class="capture_btn capture_primary">No</a>
                    </div>
                </div>
            {* /deactivateAccountForm *}
        </div>
    </div>


	<form id="samlForm" action="?" method="post" name="f">
        <input type="hidden" id="completeLogout" name="completeLogout" value="TRUE" />
<?php
foreach ($this->data['stateparams'] as $name => $value) {
	echo('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
}
?>
	</form>

<?php
//$this->includeAtTemplateBase('includes/footer.php');
?>
