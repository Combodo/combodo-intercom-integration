<?php
/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

/** @noinspection PhpUnhandledExceptionInspection */
SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'combodo-intercom-integration/1.1.0',
	array(
		// Identification
		//
		'label' => 'Chat integration with Intercom',
		'category' => 'integration',

		// Setup
		//
		'dependencies' => array(
			// Dependency on request management must remain optional as we might want the chat widget only and not the ticket creation feature.
			// That's why we put a module that is always present (itop-config-mgmt for iTop 2.7, itop-structure for iTop 3.0+) in the expression, to keep the itop-request-mgmt|-itil] optional
			'itop-config-mgmt/2.7.0||itop-structure/3.0.0||itop-request-mgmt/2.7.0||itop-request-mgmt-itil/2.7.0',
		),
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
