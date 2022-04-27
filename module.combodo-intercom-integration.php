<?php
/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

/** @noinspection PhpUnhandledExceptionInspection */
SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'combodo-intercom-integration/1.0.0',
	array(
		// Identification
		//
		'label' => 'Chat integration with Intercom',
		'category' => 'integration',

		// Setup
		//
		'dependencies' => array(),
		'mandatory' => false,
		'visible' => true,

		// Components
		//
		'datamodel' => array(
			// Module's autoloader
			'vendor/autoload.php',
			// Explicitly load APIs classes
			'src/Hook/ConsoleUIExtension.php',
			'src/Hook/PortalUIExtension.php',
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
