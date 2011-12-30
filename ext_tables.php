<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
$tempColumns = array (
	'tx_ajadofacebook_id' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:ajado_facebook/locallang_db.xml:fe_users.tx_ajadofacebook_id',		
		'config' => array (
			'type' => 'input',	
			'size' => '30',
		)
	),
	'tx_ajadofacebook_link' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:ajado_facebook/locallang_db.xml:fe_users.tx_ajadofacebook_link',		
		'config' => array (
			'type'     => 'input',
			'size'     => '15',
			'max'      => '255',
			'checkbox' => '',
			'eval'     => 'trim',
			'wizards'  => array(
				'_PADDING' => 2,
				'link'     => array(
					'type'         => 'popup',
					'title'        => 'Link',
					'icon'         => 'link_popup.gif',
					'script'       => 'browse_links.php?mode=wizard',
					'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
				)
			)
		)
	),
	'tx_ajadofacebook_gender' => array (		
		'exclude' => 0,
		'label' => 'LLL:EXT:ajado_facebook/locallang_db.xml:fe_users.tx_ajadofacebook_gender',		
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_ajadofacebook_email' => array (		
		'exclude' => 0,
		'label' => 'LLL:EXT:ajado_facebook/locallang_db.xml:fe_users.tx_ajadofacebook_email',		
		'config' => array (
			'type' => 'input',	
			'size' => '30',
		)
	),
    'tx_ajadofacebook_locale' => array (        
        'exclude' => 0,
        'label' => 'LLL:EXT:ajado_facebook/locallang_db.xml:fe_users.tx_ajadofacebook_locale',        
        'config' => array (
            'type' => 'input',    
            'size' => '5',    
            'max' => '5',    
            'eval' => 'trim',
        )
    ),
    'tx_ajadofacebook_locale' => array (        
        'exclude' => 0,
        'label' => 'LLL:EXT:ajado_facebook/locallang_db.xml:fe_users.tx_ajadofacebook_locale',        
        'config' => array (
            'type' => 'input',    
            'size' => '25',    
            'max' => '25',    
            'eval' => 'trim',
        )
    ),
);


t3lib_div::loadTCA('fe_users');
t3lib_extMgm::addTCAcolumns('fe_users',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('fe_users','tx_ajadofacebook_id;;;;1-1-1, tx_ajadofacebook_link, tx_ajadofacebook_gender, tx_ajadofacebook_email, tx_ajadofacebook_locale');

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';


t3lib_extMgm::addPlugin(array(
	'LLL:EXT:ajado_facebook/locallang_db.xml:tt_content.list_type_pi1',
	$_EXTKEY . '_pi1',
	t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'
),'list_type');

t3lib_extMgm::addStaticFile($_EXTKEY,'static/facebook_connect/', 'Facebook Connect');
?>