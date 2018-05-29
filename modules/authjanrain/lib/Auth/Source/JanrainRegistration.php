<?php
class sspmod_authjanrain_Auth_Source_JanrainRegistration extends SimpleSAML_Auth_Source {
//class sspmod_authYubiKey_Auth_Source_YubiKey extends SimpleSAML_Auth_Source {

    /**
     * The string used to identify our states.
     */
    const STAGEID = 'sspmod_authjanrain_Auth_Source_JanrainRegistration.state';

    
    /**
     * The key of the AuthId field in the state.
     */
    const AUTHID = 'sspmod_authjanrain_Auth_Source_JanrainRegistration.AuthId';

    /**
     * The Janrain Registration Capture Server URL with protocol(https://appname.janraincapture.com).
     */
    private $captureServer;
    private $captureEntityType;
    private $includeFullProfileJson;

    //See Authsources Sample for Documentation.
    private $salesforceMode;
    private $salesforceOnly;
    private $salesforceOrganizationId;
    private $salesforceAccountOwnerId;
    private $salesforceProfileId;
    private $salesforcePortalAccount;
    private $salesforcePortalRole;

    /**
     * Constructor for this authentication source.
     *
     * @param array $info  Information about this authentication source.
     * @param array $config  Configuration.
     */
    public function __construct($info, $config) {
        assert('is_array($info)');
        assert('is_array($config)');

        /* Call the parent constructor first, as required by the interface. */
        parent::__construct($info, $config);

        if (array_key_exists('captureServer', $config)) {
            $this->captureServer = $config['captureServer'];
        }

        if (array_key_exists('captureEntityType', $config)) {
            $this->captureEntityType = $config['captureEntityType'];
        }

        if (array_key_exists('includeFullProfileJson', $config)) {
            $this->includeFullProfileJson = $config['includeFullProfileJson'];
        }else{
            $this->includeFullProfileJson = true;
        }

        if (array_key_exists('salesforceMode', $config)) {
            $this->salesforceMode = $config['salesforceMode'];
        }else{
            $this->salesforceMode = '';
        }

        if (array_key_exists('salesforceOnly', $config)) {
            $this->salesforceOnly = $config['salesforceOnly'];
        }else{
            $this->salesforceOnly = false;
        }

        if (array_key_exists('salesforceOrganizationId', $config)) {
            $this->salesforceOrganizationId = $config['salesforceOrganizationId'];
        }else{
            $this->salesforceOrganizationId = '';
        }

        if (array_key_exists('salesforceAccountOwnerId', $config)) {
            $this->salesforceAccountOwnerId = $config['salesforceAccountOwnerId'];
        }else{
            $this->salesforceAccountOwnerId = '';
        }

        if (array_key_exists('salesforceProfileId', $config)) {
            $this->salesforceProfileId = $config['salesforceProfileId'];
        }else{
            $this->salesforceProfileId = '';
        }

        if (array_key_exists('salesforcePortalAccount', $config)) {
            $this->salesforcePortalAccount = $config['salesforcePortalAccount'];
        }else{
            $this->salesforcePortalAccount = '';
        }

        if (array_key_exists('salesforcePortalRole', $config)) {
            $this->salesforcePortalRole = $config['salesforcePortalRole'];
        }else{
            $this->salesforcePortalRole = '';
        }

    }


    /**
     * Initialize login.
     *
     * This function saves the information about the login, and redirects to a
     * login page.
     *
     * @param array &$state  Information about the current authentication.
     */
    public function authenticate(&$state) {
        assert('is_array($state)');

        /* We are going to need the authId in order to retrieve this authentication source later. */
        $state[self::AUTHID] = $this->authId;

        $id = SimpleSAML_Auth_State::saveState($state, self::STAGEID);

        $url = SimpleSAML_Module::getModuleURL('authjanrain/janrainWidget.php');
        SimpleSAML_Utilities::redirectTrustedURL($url, array('AuthState' => $id));
    }
    
    
    /**
     * Handle login request.
     *
     * This function is used by the login form (core/www/loginuserpass.php) when the user
     * enters a username and password. On success, it will not return. On wrong
     * username/password failure, it will return the error code. Other failures will throw an
     * exception.
     *
     * @param string $authStateId  The identifier of the authentication state.
     * @param string $otp  The one time password entered-
     * @return string  Error code in the case of an error.
     */
    public static function handleLogin($authStateId, $captureServerUrl, $captureToken) {
        assert('is_string($authStateId)');
        assert('is_string($captureServerUrl)');
        assert('is_string($captureToken)');
        // sanitize the input
        $sid = SimpleSAML_Utilities::parseStateID($authStateId);
        if (!is_null($sid['url'])) {
            SimpleSAML_Utilities::checkURLAllowed($sid['url']);
        }

        /* Retrieve the authentication state. */
        $state = SimpleSAML_Auth_State::loadState($authStateId, self::STAGEID);

        /* Find authentication source. */
        assert('array_key_exists(self::AUTHID, $state)');
        $source = SimpleSAML_Auth_Source::getById($state[self::AUTHID]);
        if ($source === NULL) {
            throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
        }


        try {
            /* Verify Token and Retrieve User Profile. */
            $attributes = $source->checkToken($captureServerUrl, $captureToken);
        } catch (SimpleSAML_Error_Error $e) {
            /* An error occurred during login. Check if it is because of the wrong
             * username/password - if it is, we pass that error up to the login form,
             * if not, we let the generic error handler deal with it.
             */
            if ($e->getErrorCode() === 'WRONGUSERPASS') {
                return 'WRONGUSERPASS';
            }

            /* Some other error occurred. Rethrow exception and let the generic error
             * handler deal with it.
             */
            throw $e;
        }

        $state['Attributes'] = $attributes;
        SimpleSAML_Auth_Source::completeAuth($state);
    }
    
   
    /**
     * Attempt to validate the token and retrieve the User Profile.
     *
     * On a successful login, this function should return the users attributes. On failure,
     * it should throw an exception. If the error was caused by the user entering the wrong
     * username or password, a SimpleSAML_Error_Error('WRONGUSERPASS') should be thrown.
     *
     *
     * @param string $captureServerUrl  The full url with protocol to the Janrain Registration Capture Server.
     * @param string $captureToken  The OAuth token retrieved from the Janrain Registration Widget upon successful authentication.
     * @return array  Associative array with the users attributes.
     */
    protected function checkToken($captureServerUrl, $captureToken) {
        assert('is_string($captureServerUrl)');
        assert('is_string($captureToken)');
        assert('is_string($this->captureEntityType)');

        //require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/libextinc/Yubico.php';

        $attributes = array();

        try {
            
            //WORK HERE
            $url = $captureServerUrl.'/entity';
  
            // Use the access token to get the user profile data
            $params = array('access_token'    => $captureToken,
                            'type_name'       => $this->captureEntityType
                            );
            
            $entityResponse = $this->postToCapture($url, $params);

            if(isset($entityResponse)){
                $decodedEntityResponse = json_decode($entityResponse);
                
                if(!$decodedEntityResponse || $decodedEntityResponse != "" || !is_null($decodedEntityResponse) )
                {
                    if($decodedEntityResponse->stat =="ok"){

                        if(isset($decodedEntityResponse->result->uuid) && $decodedEntityResponse->result->uuid !="")
                        {
                            $attributes['uid'] = array($decodedEntityResponse->result->uuid);
                            if($this->salesforceMode =="communities"){
                                $attributes['Account.AccountNumber'] = array($decodedEntityResponse->result->uuid);
                            }
                        }
                        if(isset($decodedEntityResponse->result->displayName) && $decodedEntityResponse->result->displayName !="")
                        {
                            if(!$this->salesforceOnly) $attributes['displayname'] =  array($decodedEntityResponse->result->displayName);
                            if($this->salesforceMode =="communities"){
                                $attributes['Account.Name'] =  array($decodedEntityResponse->result->displayName);
                            }
                        }
                        if(isset($decodedEntityResponse->result->email) && $decodedEntityResponse->result->email !="")
                        {
                            if(!$this->salesforceOnly) $attributes['mail'] = array($decodedEntityResponse->result->email);
                            
                            if($this->salesforceMode =="communities" || $this->salesforceMode =="portal"){
                                $attributes['Contact.Email'] = array($decodedEntityResponse->result->email);
                            }
                            if(    $this->salesforceMode =="communities" 
                                || $this->salesforceMode =="portal" 
                                || $this->salesforceMode =="standard")
                            {
                                $attributes['User.Email'] = array($decodedEntityResponse->result->email);
                                $attributes['User.username'] =  array($decodedEntityResponse->result->email);
                            }

                            
                        }
                        if(isset($decodedEntityResponse->result->givenName) && $decodedEntityResponse->result->givenName !="")
                        {
                            if(!$this->salesforceOnly) $attributes['givenname'] = array($decodedEntityResponse->result->givenName);
                            if(    $this->salesforceMode =="communities" 
                                || $this->salesforceMode =="portal" 
                                || $this->salesforceMode =="standard")
                            {
                                $attributes['User.FirstName'] = array($decodedEntityResponse->result->givenName);
                            }
                        }
                        if(isset($decodedEntityResponse->result->middleName) && $decodedEntityResponse->result->middleName !="")
                        {
                            if(!$this->salesforceOnly) $attributes['middlename'] = array($decodedEntityResponse->result->middleName);
                        }
                        if(isset($decodedEntityResponse->result->familyName) && $decodedEntityResponse->result->familyName !="")
                        {
                            if(!$this->salesforceOnly) $attributes['sn'] = array($decodedEntityResponse->result->familyName);
                            if(    $this->salesforceMode =="communities" 
                                || $this->salesforceMode =="portal" )
                            {
                                $attributes['Contact.LastName'] = array($decodedEntityResponse->result->familyName);
                            }
                            if(    $this->salesforceMode =="communities" 
                                || $this->salesforceMode =="portal" 
                                || $this->salesforceMode =="standard")
                            {
                                $attributes['User.LastName'] = array($decodedEntityResponse->result->familyName);
                            }
                        }
                        if(isset($decodedEntityResponse->result->birthday) && $decodedEntityResponse->result->birthday !="")
                        {
                            if(!$this->salesforceOnly) $attributes['noredupersonbirthdate'] = array($decodedEntityResponse->result->birthday);
                        }
                        if(isset($decodedEntityResponse->result->gender) && $decodedEntityResponse->result->gender !="")
                        {
                            if(!$this->salesforceOnly) $attributes['gender'] = array($decodedEntityResponse->result->gender);
                        }
                        if(isset($decodedEntityResponse->result->primaryAddress->mobile) && $decodedEntityResponse->result->primaryAddress->mobile !="")
                        {
                            if(!$this->salesforceOnly) $attributes['mobile'] = array($decodedEntityResponse->result->primaryAddress->mobile);
                            if(    $this->salesforceMode =="communities" 
                                || $this->salesforceMode =="portal" 
                                || $this->salesforceMode =="standard")
                            {
                                $attributes['User.MobilePhone'] = array($decodedEntityResponse->result->primaryAddress->mobile);
                            }
                        }
                        if(isset($decodedEntityResponse->result->primaryAddress->phone) && $decodedEntityResponse->result->primaryAddress->phone !="")
                        {
                            if(!$this->salesforceOnly) $attributes['telephonenumber'] = array($decodedEntityResponse->result->primaryAddress->phone);
                            if(    $this->salesforceMode =="communities" 
                                || $this->salesforceMode =="portal" 
                                || $this->salesforceMode =="standard")
                            {
                                $attributes['User.Phone'] = array($decodedEntityResponse->result->primaryAddress->phone);
                            }
                        }
                        if(isset($decodedEntityResponse->result->primaryAddress->address1) && $decodedEntityResponse->result->primaryAddress->address1 !="")
                        {
                            if(!$this->salesforceOnly) $attributes['postaladdress'] = array($decodedEntityResponse->result->primaryAddress->address1);
                            if(    $this->salesforceMode =="communities" 
                                || $this->salesforceMode =="portal" 
                                || $this->salesforceMode =="standard")
                            {
                                $attributes['User.Street'] = array($decodedEntityResponse->result->primaryAddress->address1);
                            }
                        }
                        if(isset($decodedEntityResponse->result->primaryAddress->city) && $decodedEntityResponse->result->primaryAddress->city !="")
                        {
                            if(!$this->salesforceOnly) $attributes['l'] = array($decodedEntityResponse->result->primaryAddress->city);
                            if(    $this->salesforceMode =="communities" 
                                || $this->salesforceMode =="portal" 
                                || $this->salesforceMode =="standard")
                            {
                                $attributes['User.City'] = array($decodedEntityResponse->result->primaryAddress->city);
                            }
                        }
                        if(isset($decodedEntityResponse->result->primaryAddress->zip) && $decodedEntityResponse->result->primaryAddress->zip !="")
                        {
                            if(!$this->salesforceOnly) $attributes['postalcode'] = array($decodedEntityResponse->result->primaryAddress->zip);
                            if(    $this->salesforceMode =="communities" 
                                || $this->salesforceMode =="portal" 
                                || $this->salesforceMode =="standard")
                            {
                                $attributes['User.Zip'] = array($decodedEntityResponse->result->primaryAddress->zip);
                            }
                        }
                        if(isset($decodedEntityResponse->result->primaryAddress->country) && $decodedEntityResponse->result->primaryAddress->country !="")
                        {
                            if(!$this->salesforceOnly) $attributes['country'] = array($decodedEntityResponse->result->primaryAddress->country);
                            if(    $this->salesforceMode =="communities" 
                                || $this->salesforceMode =="portal" 
                                || $this->salesforceMode =="standard")
                            {
                                $attributes['User.Country'] = array($decodedEntityResponse->result->primaryAddress->country);
                            }
                        }
                        if(isset($decodedEntityResponse->result->primaryAddress->stateAbbreviation) && $decodedEntityResponse->result->primaryAddress->stateAbbreviation !="")
                        {
                            if(!$this->salesforceOnly) $attributes['state'] = array($decodedEntityResponse->result->primaryAddress->stateAbbreviation);
                            if(    $this->salesforceMode =="communities" 
                                || $this->salesforceMode =="portal" 
                                || $this->salesforceMode =="standard")
                            {
                                $attributes['User.State'] = array($decodedEntityResponse->result->primaryAddress->stateAbbreviation);
                            }
                        }
                        //Salesforce Specific Attributes for SF Communities:
                        //https://help.salesforce.com/HTViewSolution?id=000198728&language=en_US
                        /*                        
                        Account.AccountNumber=98523554;
                        Account.Owner=005o0000000FYcpAAG;
                        Contact.Email=test123Comunity1fghgfhgfhghfg@test.com;
                        Contact.LastName=123Test1235gh4;
                        Account.Name=communityTfghgfest3;
                        User.Email=ada896532@ada.com;
                        User.LastName=CommunityUsgfhfgher;
                        User.ProfileId=00eo0000000oIIWAA2;
                        User.Username=testComunity5fghgfh5@test.com; 
                        */ 
                        if($this->salesforceMode =="communities") {
                            $attributes['organization_id'] = array($this->salesforceOrganizationId);
                            $attributes['Account.Owner'] = array($this->salesforceAccountOwnerId);
                        }
                        if(    $this->salesforceMode =="communities" 
                                || $this->salesforceMode =="portal" 
                                || $this->salesforceMode =="standard")
                        {
                                $attributes['User.ProfileId'] = array($this->salesforceProfileId);
                        }
                        if($this->salesforceMode =="portal"){
                            $attributes['User.PortalRole'] = array($this->salesforcePortalRole);
                            $attributes['Contact.Account'] = array($this->salesforcePortalAccount);
                        }
                        

                        if($this->includeFullProfileJson && $this->salesforceOnly != true){
                            $copyProfile = $decodedEntityResponse->result;
                            //Let's not send the password hash unless needed.
                            unset($copyProfile->password);
                            $attributes['JanrainProfile'] = array(json_encode($copyProfile));
                        }

                        
                    }else{
                        throw new SimpleSAML_Error_Exception("ERROR: ".$decodedEntityResponse->error."<br />ERROR DESCRIPTION: ".$decodedEntityResponse->error_description);
                    }
                }else{
                    throw new SimpleSAML_Error_Exception("ERROR: Invalid Entity Response Format");
                }

            }else{
                throw new SimpleSAML_Error_Exception("ERROR: No data returned from entity API");
            }

            //$attributes = array('uid' => array($uid));
        } catch (Exception $e) {
            SimpleSAML_Logger::info('JanrainRegistration:' . $this->authId . ': Validation error (captureServerUrl ' . $captureServerUrl . ', captureToken ' . $captureToken . ', captureEntityType ' . $this->captureEntityType . '), debug output: ' . $e->getMessage());

            throw new SimpleSAML_Error_Error('WRONGUSERPASS', $e);
        }

        SimpleSAML_Logger::info('JanrainRegistration:' . $this->authId . ': CaptureToken ' . $captureToken . ' validated successfully');

        return $attributes;
    }

    protected static function postToCapture($url,$postData){
        $result = "";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $result = curl_error($ch);
        } else {
            curl_close($ch);
            $result = $response;
        }
        
        return $result;
    }

    
    /**
     * Handle logout request.
     *
     * This function is used by the login form (core/www/loginuserpass.php) when the user
     * enters a username and password. On success, it will not return. On wrong
     * username/password failure, it will return the error code. Other failures will throw an
     * exception.
     *
     * @param string $authStateId  The identifier of the authentication state.
     * @param string $otp  The one time password entered-
     * @return string  Error code in the case of an error.
     */
    public static function handleLogout($authStateId) {
        assert('is_string($authStateId)');
        // sanitize the input
        $sid = SimpleSAML_Utilities::parseStateID($authStateId);
        if (!is_null($sid['url'])) {
            SimpleSAML_Utilities::checkURLAllowed($sid['url']);
        }

        /* Retrieve the authentication state. */
        $state = SimpleSAML_Auth_State::loadState($authStateId, self::STAGEID);

        /* Find authentication source. */
        assert('array_key_exists(self::AUTHID, $state)');
        $source = SimpleSAML_Auth_Source::getById($state[self::AUTHID]);
        if ($source === NULL) {
            throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
        }


        try {
            //$source->logout($state);
            SimpleSAML_Auth_Source::completeLogout($state);
        } catch (SimpleSAML_Error_Error $e) {
            
            /* Some other error occurred. Rethrow exception and let the generic error
             * handler deal with it.
             */
            throw $e;
        }

        //SimpleSAML_Auth_Source::completeAuth($state);
    }


    /**
     * Log out from this authentication source.
     *
     * This function should be overridden if the authentication source requires special
     * steps to complete a logout operation.
     *
     * If the logout process requires a redirect, the state should be saved. Once the
     * logout operation is completed, the state should be restored, and completeLogout
     * should be called with the state. If this operation can be completed without
     * showing the user a page, or redirecting, this function should return.
     *
     * @param array &$state  Information about the current logout operation.
     */
    public function logout(&$state) {
        assert('is_array($state)');
        
        /* Default logout handler which doesn't do anything. */

        /* We are going to need the authId in order to retrieve this authentication source later. */
        $state[self::AUTHID] = $this->authId;

        $id = SimpleSAML_Auth_State::saveState($state, self::STAGEID);
        //SimpleSAML_Auth_Source::completeLogout($state);
        $url = SimpleSAML_Module::getModuleURL('authjanrain/janrainLogout.php');
        SimpleSAML_Utilities::redirectTrustedURL($url, array('AuthState' => $id));

    }


    

}