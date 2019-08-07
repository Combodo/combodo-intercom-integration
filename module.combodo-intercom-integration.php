<?php

SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'combodo-intercom-integration/0.0.1',
	array(
		// Identification
		//
		'label' => 'Intercom messenger integration',
		'category' => 'integration',

		// Setup
		//
		'dependencies' => array(),
		'mandatory' => false,
		'visible' => true,

		// Components
		//
		'datamodel' => array(
			'src/Hook/consoleuiextension.class.inc.php',
			'src/Hook/portaluiextension.class.inc.php',
		),
		'webservice' => array(),
		'dictionary' => array(
		),
		'data.struct' => array(),
		'data.sample' => array(),
		
		// Documentation
		//
		'doc.manual_setup' => '',
		'doc.more_information' => '',

		// Default settings
		//
		'settings' => array(),
	)
);
