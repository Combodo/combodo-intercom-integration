<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Helper;

use DBObjectSearch;
use DBObjectSet;
use MetaModel;
use UserRights;

/**
 * Class ConfigHelper
 *
 * @package Combodo\iTop\Extension\TawkIntegration\Helper
 * @author Guillaume Lajarige <guillaume.lajarige@combodo.com>
 */
class ConfigHelper
{
	const MODULE_CODE = 'combodo-intercom-integration';

	/**
	 * Return the module code so it can be used widely (module setting, URLs, ...)
	 *
	 * @return string
	 */
	public static function GetModuleCode()
	{
		return static::MODULE_CODE;
	}

	/**
	 * @param string $sProperty
	 *
	 * @return mixed
	 */
	public static function GetModuleSetting($sProperty)
	{
		return MetaModel::GetModuleSetting(static::GetModuleCode(), $sProperty);
	}

	/**
	 * Return if the module should be allowed based on:
	 * - The defined GUI
	 * - The current user profiles
	 *
	 * @param string $sGUI
	 *
	 * @return bool
	 */
	public static function IsAllowed($sGUI)
	{
		// Check if enabled in $sGUI
		$aEnabledGUIs = MetaModel::GetModuleSetting(static::GetModuleCode(), 'enabled_portals');
		if (is_array($aEnabledGUIs) && !in_array($sGUI, $aEnabledGUIs))
		{
			return false;
		}

		// Check if user has profile to access chat
		$aUserProfiles = UserRights::ListProfiles();
		$aAllowedProfiles = MetaModel::GetModuleSetting(static::GetModuleCode(), 'allowed_profiles');
		// No allowed profile defined = Allowed for everyone
		if (!empty($aAllowedProfiles))
		{
			$bAllowed = false;
			foreach ($aAllowedProfiles as $sAllowedProfile)
			{
				if (in_array($sAllowedProfile, $aUserProfiles))
				{
					$bAllowed = true;
					break;
				}
			}

			if (!$bAllowed)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Return the JS snippet for the widget
	 *
	 * @return string
	 * @throws \CoreException
	 */
	public static function GetWidgetJSSnippet()
	{
		$sJS = '';

		// Retrieve current person
		$sPersonNameAsJson = 'Unidentified visitor';
		$sPersonEmailAsJson = '';
		/** @var \DBObject $oPerson */
		$oPerson = UserRights::GetContactObject();
		if($oPerson !== null)
		{
			$sPersonNameAsJson = json_encode($oPerson->GetName());
			$sPersonEmailAsJson = json_encode($oPerson->Get('email'));
			// Class/ID are mandatory for reconciliation instead of just email/friendlyname, especially when a contact becomes obsolete and is re-created on another object later
			$sPersonClassAsJson = json_encode(get_class($oPerson));
			$sPersonIDAsJson = json_encode($oPerson->GetKey());
		}

		// Found first matching workspace
		$aWorkspaces = static::GetModuleSetting('workspaces');
		foreach ($aWorkspaces as $sWorkspaceID => $aWorkspaceConf) {
			$oSearch = DBObjectSearch::FromOQL($aWorkspaceConf['scope']);
			$oSet = new DBObjectSet($oSearch);

			$iCount = (int) $oSet->CountWithLimit(1);
			if ($iCount > 0) {
				$sJS .= <<<JS
window.intercomSettings = {
    api_base: "https://api-iam.intercom.io",
    app_id: "{$sWorkspaceID}",
    name: {$sPersonNameAsJson}, // Full name
    email: {$sPersonEmailAsJson}, // Email address
    // Note: We don't preset the user_id attribute as we recon that in Intercom contacts might be referenced in something else than iTop (eg. a CRM)
    //       For more information about "custom attributes" see https://developers.intercom.com/installing-intercom/docs/adding-custom-information
    itop_contact_class: {$sPersonClassAsJson},
    itop_contact_id: {$sPersonIDAsJson},
};

(function() {
    var w = window;
    var ic = w.Intercom;
    if (typeof ic === "function") {
        ic('reattach_activator');
        ic('update', w.intercomSettings);
    } else {
        var d = document;
        var i = function() {
            i.c(arguments);
        };
        i.q = [];
        i.c = function(args) {
            i.q.push(args);
        };
        w.Intercom = i;
        var l = function() {
            var s = d.createElement('script');
            s.type = 'text/javascript';
            s.async = true;
            s.src = 'https://widget.intercom.io/widget/{$sWorkspaceID}';
            var x = d.getElementsByTagName('script')[0];
            x.parentNode.insertBefore(s, x);
        };
        if (w.attachEvent) {
            w.attachEvent('onload', l);
        } else {
            w.addEventListener('load', l, false);
        }
    }
})();
JS
		;
				break;
			}
		}

		return $sJS;
	}
}