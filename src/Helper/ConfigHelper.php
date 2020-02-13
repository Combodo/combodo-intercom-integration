<?php

/**
 * Copyright (C) 2013-2020 Combodo SARL
 *
 * This file is part of iTop.
 *
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Helper;

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
		// Retrieve current person
		/** @var \DBObject $oPerson */
		$oPerson = UserRights::GetContactObject();
		$sPersonName = $oPerson->GetName();
		$sPersonEmail = $oPerson->Get('email');

		// Retrieve API key
		$sAPIKey = static::GetModuleSetting('api_key');

		// Nothing
		$sJS =
			<<<JS
window.intercomSettings = {
    app_id: "{$sAPIKey}",
    name: "{$sPersonName}", // Full name
    email: "{$sPersonEmail}", // Email address
    //created_at: "" // Signup date as a Unix timestamp
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
            s.src = 'https://widget.intercom.io/widget/{$sAPIKey}';
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

		return $sJS;
	}
}