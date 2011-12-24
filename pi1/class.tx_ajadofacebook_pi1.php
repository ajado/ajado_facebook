<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Matteo Savio <msavio@ajado.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extmgm::extPath('ajado_facebook', 'lib/facebook.php'));

/**
 * Plugin 'Facebook login' for the 'ajado_facebook' extension.
 *
 * @author	Matteo Savio <msavio@ajado.com>
 * @package	TYPO3
 * @subpackage	tx_ajadofacebook
 */
class tx_ajadofacebook_pi1 extends tslib_pibase {
	var $prefixId		= 'tx_ajadofacebook_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_ajadofacebook_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey		= 'ajado_facebook';	// The extension key.
    var $tableName = 'fe_users';
    var $loginParameter = 'fb_login';
	
  /**
   * List of query parameters that get automatically dropped when rebuilding
   * the current URL.
   */
  protected static $DROP_QUERY_PARAMS = array(
    'code',
    'state',
    'signed_request',
  );
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
	
		// Create application instance
		$facebook = new Facebook(array(
			'appId' => $this->conf['appId'],
			'secret' => $this->conf['secret']
		));
		
		$user = $facebook->getUser();
		if(($this->piVars['fbLogin'] == "1") && $user) {
			try {
				$facebookUserProfile = $facebook->api('/me');
				// todo was ist wenn ich es nicht zur�ck bekomme?
				$this->storeUser($facebookUserProfile);
				$this->loginUser($user);
			} catch (FacebookApiException $e) {
			  	error_log($e);
			  	$user = null;
			}
		}
		// ||�(!$user)
		if($this->piVars['fbLogout'] == "1") {
			$GLOBALS['TSFE']->fe_user->logoff();
            setcookie("fe_typo_user", "", time() - 3600, "/", $_SERVER["HTTP_HOST"]);
            
			if(isset($this->conf['redirectAfterLogoutPid'])){
				$redirectAfterLogoutUrl = $this->pi_getPageLink($this->conf['redirectAfterLogoutPid']);
				header('Location: '.$redirectAfterLogoutUrl);
				exit;
			}
        }
        
		if($this->conf['askForEmail']) {
			$scope = 'email';
		}
		
		/* Parameters for JavaScript */
        $appId = $facebook->getAppId();
        $facebookLanguage = $this->conf['facebookLanguage'] ? $this->conf['facebookLanguage'] : 'en_US';
		$channelUrl = $GLOBALS['TSFE']->baseUrl . t3lib_extMgm::siteRelPath($this->extKey).'lib/channel.html';
		
		$reloadUrlLogin = $this->pi_getPageLink($GLOBALS['TSFE']->id,'',array($this->prefixId . '[fbLogin]' => "1"));
		$reloadUrlLogout = $this->pi_getPageLink($GLOBALS['TSFE']->id,'',array($this->prefixId . '[fbLogout]' => "1"));
		
		$loginButton = $this->cObj->cObjGetSingle($this->conf['loginButton'], $this->conf['loginButton.']);
		$logoutButton = $this->cObj->cObjGetSingle($this->conf['logoutButton'], $this->conf['logoutButton.']);
		
		// If user is logged in and JavaScript detects that user should be logged out (because he/she is logged out of facebook) -> redirect to logout Url.
		$immedeateLogout = '';
		
		
		$additionalInitializationCode = '';
		if ($GLOBALS['TSFE']->fe_user->user) {
			// if a typo3 session exists the user can logout (if logged into facebook) or will be logged out (if not logged into facebook)
			$additionalInitializationCode = "
			FB.getLoginStatus(function(response) {
				   if (response.authResponse) {
				     document.getElementById('facebookauth').innerHTML = '$logoutButton';
				   } else {
				    document.location.href='$reloadUrlLogout';
				   }
				 }, true);";
		}
		else {
			// if no typo3 session exists the user has to log in anyway.
			$additionalInitializationCode =  "
			FB.getLoginStatus(function(response) {
				   	document.getElementById('facebookauth').innerHTML = '$loginButton';
				 }, true);";
		}
		
		$content .= <<<FACEBOOKJSSDK
			<div id="fb-root"></div>
			<div id="facebookauth"></div>
			<script>
			  window.fbAsyncInit = function() {
			    FB.init({
			      appId      : '$appId', // App ID
			      channelURL : '$channelUrl', // Channel File
			      status     : true, // check login status
			      cookie     : true, // enable cookies to allow the server to access the session
			      oauth      : true, // enable OAuth 2.0
			      xfbml      : true  // parse XFBML
			    });
			
			    $additionalInitializationCode
			  };
			
			  // Load the SDK Asynchronously
			  (function(d){
			     var js, id = 'facebook-jssdk'; if (d.getElementById(id)) {return;}
			     js = d.createElement('script'); js.id = id; js.async = true;
			     js.src = "//connect.facebook.net/$facebookLanguage/all.js";
			     d.getElementsByTagName('head')[0].appendChild(js);
			   }(document));
			   
			   function fbLogin() {
			   	FB.login(function(response) {
				   if (response.authResponse) {
				     document.location.href="$reloadUrlLogin";
				   } else {
				   }
				 }, {scope: '$scope'});
			   }
			   
			   function fbLogout() {
			   	 FB.logout(function(response) {
				     document.location.href="$reloadUrlLogout";
				 });
			   }
			</script>
FACEBOOKJSSDK;
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	function loginUser($facebookId) {
		global $TYPO3_DB;
		
        $where = 'tx_ajadofacebook_id=' . $TYPO3_DB->fullQuoteStr($facebookId, $this->tableName) . 
        		 ' AND deleted=0';
		
        $result = $TYPO3_DB->exec_SELECTquery('*', $this->tableName, $where, '', '', '');
        if ($userToLogin = $TYPO3_DB->sql_fetch_assoc($result))
        {
        	/* @var $fe_user tslib_feUserAuth */
            $feUser = $GLOBALS['TSFE']->fe_user;
            unset($feUser->user);
            if($this->conf['makeSessionPermanent']) {
            	$feUser->is_permanent = 1;
            }
            $feUser->createUserSession($userToLogin);
            $feUser->loginSessionStarted = TRUE;
            $feUser->user = $feUser->fetchUserSession();
            $GLOBALS["TSFE"]->loginUser = 1;
            $GLOBALS["TSFE"]->initUserGroups(); // this is needed in case the redirection is to a restricted page.
            
            
			if(isset($this->conf['redirectAfterLoginPid'])){
				$redirectAfterLoginUrl = $this->pi_getPageLink($this->conf['redirectAfterLoginPid']);
				header('Location: '.$redirectAfterLoginUrl);
				exit;
			}
        }
	}
	
    /**
     * inserts or updates the facebook values to table fe_users
     * @global <type> $TYPO3_DB
     */
    function storeUser($facebookUserProfile)
    {
        global $TYPO3_DB;
        
        $this->fe_usersValues['pid'] = $this->conf['usersPid'];
        $username = uniqid($this->extKey . $facebookUserProfile['id'] . '.');
        // username should be a unique, random string that should never be used with any registration
        //if($)
        
        // ??? check if usergroup is the one we're using
        
        $where = "(tx_ajadofacebook_id=" .
                 $TYPO3_DB->fullQuoteStr($facebookUserProfile['id'], $this->tableName) .
                 ") AND deleted=0";
        
        $result = $TYPO3_DB->exec_SELECTquery('*', $this->tableName, $where, '', '', '');
        
        $fe_usersValues['tstamp'] = time();
        $fe_usersValues['first_name'] = $facebookUserProfile['first_name'];
        $fe_usersValues['last_name'] = $facebookUserProfile['last_name'];
        $fe_usersValues['username'] = $username;
        $fe_usersValues['lastlogin'] = time();
        $fe_usersValues['tx_ajadofacebook_link'] = $facebookUserProfile['link'];
        $fe_usersValues['name'] = $facebookUserProfile['name'];
        
        /* from http://developers.facebook.com/docs/internationalization/
         * The basic format is ''ll_CC'', where ''ll'' is a two-letter language code,
         * and ''CC'' is a two-letter country code. For instance, 'en_US' represents US English.
         * There are two exceptions that do not follow the ISO standard: ar_AR and es_LA.
         * We use these to denote umbrella locales for Arabic and Spanish, despite
         * in the latter case having a few more specialized localizations of Spanish.
         */
        if(isset($facebookUserProfile['locale'])) {
       		$languageAndCountry = explode('_', $facebookUserProfile['locale']);
       		if(isset($languageAndCountry[0])) {
        		$fe_usersValues['language'] = $languageAndCountry[0];
       		}
        }
        if(isset($facebookUserProfile['gender'])) {
        	$fe_usersValues['tx_ajadofacebook_gender'] = $facebookUserProfile['gender'];
        }
        
		if($this->conf['askForEmail'] && isset($facebookUserProfile['email'])) {
        	$fe_usersValues['tx_ajadofacebook_email'] = $facebookUserProfile['email'];
		}
        // $fe_usersValues['tx_ajadofacebook_email']
        $fe_usersValues['pid'] = $this->conf['usersPid'];
        
        if ($TYPO3_DB->sql_num_rows($result) > 0)
        {
			$user = $TYPO3_DB->sql_fetch_assoc($result);
				// if updating usergroup: don't overwrite it since another extension could set it???
				//if(!$user['usergroup']){ // only override if user is not already in some group, could be set from another extension
				//$this->fe_usersValues['usergroup'] = $this->conf['userGroup'];
			
			/*
			 * TODO: Zeitintervall, wann upgedatet wird (bzw. nur dann updaten wenn der Facebook-User upgedatet wurde)
			 */
        	if($this->conf['copyFacebookImageToSrfeuserFolder']==1) {
				$fe_usersValues['image'] = $this->copyImageFromFacebook($facebookUserProfile['id']);
        	}
        	
			$TYPO3_DB->exec_UPDATEquery($this->tableName, $where, $fe_usersValues);
        }
        else {
        	$fe_usersValues['tx_ajadofacebook_id'] = $facebookUserProfile['id'];
        	if($this->conf['copyFacebookImageToSrfeuserFolder']==1) {
				$fe_usersValues['image'] = $this->copyImageFromFacebook($facebookUserProfile['id']);
        	}
			$fe_usersValues['usergroup'] = $this->conf['userGroup'];
            $fe_usersValues['password'] = md5($facebookUserProfile['name'] . time());
            $fe_usersValues['crdate'] = time();
            $TYPO3_DB->exec_INSERTquery($this->tableName, $fe_usersValues);
        }
    }
    
	private function copyImageFromFacebook($facebookUserId){
		// e.g. http://graph.facebook.com/1666900615/picture&type=large
		$this->conf['imageDir'] = 'uploads/tx_srfeuserregister';
		$imageUrl = "http://graph.facebook.com/$facebookUserId/picture&type=large";
		$fileName = $facebookUserId.'.jpg';
		$ch = curl_init($imageUrl);
		$fp = fopen(PATH_site.$this->conf['imageDir'].'/'.$fileName, 'wb');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		return $fileName;
	}
    
    /**
     * Check prerequisites and exit with statement if not met
     */
    function checkPrerequisites()
    {
        /*if (!function_exists('curl_init'))
        {
            throw new Exception('Ext facebook2t3: libcurl package for PHP is needed.');
        }
        if (!function_exists('json_decode'))
        {
            throw new Exception('Ext facebook2t3: JSON PHP extension is needed.');
        }

        $countFf4P = count($this->facebookFields4Perms);
        $countFf4F = count($this->facebookFields4Fetch);
        $countF_uF = count($this->fe_usersFields);
        if ($countFf4P != $countFf4F || $countFf4F != $countF_uF)
        {
            throw new Exception('Ext facebook2t3: constants "facebookFields4Perms", "facebookFields4Fetch" & "fe_usersFields" need to have the same number of elements!');
        }

        if (!isset($this->conf['appId']) || $this->conf['appId'] == '')
        {
            throw new Exception('Ext facebook2t3: Facebook app id is not set in constants');
        }
        if (!isset($this->conf['secret']) || $this->conf['secret'] == '')
        {
            throw new Exception('Ext facebook2t3: Facebook secret is not set in constants');
        }*/
    }
    
  /**
   * Returns the Current URL, stripping it of known FB parameters that should
   * not persist.
   *
   * @return string The current URL
   */
  protected function getCurrentUrl($additionalParams = array()) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'
      ? 'https://'
      : 'http://';
    $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $parts = parse_url($currentUrl);

    $query = '';
    if (!empty($parts['query'])) {
      // drop known fb params
      $params = explode('&', $parts['query']);
      $retained_params = array();
      foreach ($params as $param) {
        if ($this->shouldRetainParam($param)) {
          $retained_params[] = $param;
        }
      }
	  $retained_params = array_merge($retained_params, $additionalParams);
      if (!empty($retained_params)) {
        $query = '?'.implode($retained_params, '&');
      }
    }

    // use port if non default
    $port =
      isset($parts['port']) &&
      (($protocol === 'http://' && $parts['port'] !== 80) ||
       ($protocol === 'https://' && $parts['port'] !== 443))
      ? ':' . $parts['port'] : '';

    // rebuild
    return $protocol . $parts['host'] . $port . $parts['path'] . $query;
  }

  /**
   * Returns true if and only if the key or key/value pair should
   * be retained as part of the query string.  This amounts to
   * a brute-force search of the very small list of Facebook-specific
   * params that should be stripped out.
   *
   * @param string $param A key or key/value pair within a URL's query (e.g.
   *                     'foo=a', 'foo=', or 'foo'.
   *
   * @return boolean
   */
  protected function shouldRetainParam($param) {
    foreach (self::$DROP_QUERY_PARAMS as $drop_query_param) {
      if (strpos($param, $drop_query_param.'=') === 0) {
        return false;
      }
    }

    return true;
  }
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ajado_facebook/pi1/class.tx_ajadofacebook_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ajado_facebook/pi1/class.tx_ajadofacebook_pi1.php']);
}

?>