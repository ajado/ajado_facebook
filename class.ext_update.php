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

require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * Update Class for DB Updates of version <= 0.2.0
 *
 * Sets new randomized passwords to each user in fe_users registered via facebook
 *
 * @author Matteo Saivo <msavio@ajado.com>
 */
class ext_update {
	function main() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, tx_ajadofacebook_id', 'fe_users', 'tx_ajadofacebook_id <> \'\' AND tx_ajadofacebook_id IS NOT NULL'); 
        
        $content = '<p><strong>Start randomizing passwords of all existing Facebook Users</strong></p>';
        
        $i = 0;
        
		while ($user = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'fe_users',
					'uid = ' . $user['uid'],
					array(
                        'username' => 'facebook' . $user['tx_ajadofacebook_id'] . '.' . t3lib_div::getRandomHexString(12),
                        'password' => t3lib_div::getRandomHexString(32)
                    )
                );
            $i++;
        }
        
        $content .= '<p><strong>Finished update. ' . $i . ' user(s) updated.</strong> You can redo this update any time.</p>';
        
        return $content;
    }
	/**
	 * Checks how many rows are found and returns true if there are any
	 * (this function is called from the extension manager)
	 *
	 * @param	string		$what: what should be updated
	 * @return	boolean
	 */
	function access() {
        return true;
    }
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ajado_facebook/pi1/class.tx_ajadofacebook_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ajado_facebook/pi1/class.tx_ajadofacebook_pi1.php']);
}

?>