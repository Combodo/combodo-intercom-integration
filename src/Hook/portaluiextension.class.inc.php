<?php

// Copyright (C) 2010-2019 Combodo SARL
//
//   This file is part of iTop.
//
//   iTop is free software; you can redistribute it and/or modify	
//   it under the terms of the GNU Affero General Public License as published by
//   the Free Software Foundation, either version 3 of the License, or
//   (at your option) any later version.
//
//   iTop is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU Affero General Public License for more details.
//
//   You should have received a copy of the GNU Affero General Public License
//   along with iTop. If not, see <http://www.gnu.org/licenses/>

namespace Combodo\iTop\Hook;

use iPortalUIExtension;
use MetaModel;
use Silex\Application;
use UserRights;

class PortalUiExtension implements iPortalUIExtension
{
	const MODULE_CODE = 'combodo-intercom-integration';

	/**
	 * Returns an array of CSS file urls
	 *
	 * @param \Silex\Application $oApp
	 *
	 * @return array
	 */
	public function GetCSSFiles(Application $oApp)
	{
		// Nothing
		return array();
	}

	/**
	 * Returns inline (raw) CSS
	 *
	 * @param \Silex\Application $oApp
	 *
	 * @return string
	 */
	public function GetCSSInline(Application $oApp)
	{
		// Nothing
		return '';
	}

	/**
	 * Returns an array of JS file urls
	 *
	 * @param \Silex\Application $oApp
	 *
	 * @return array
	 */
	public function GetJSFiles(Application $oApp)
	{
		// Nothing
		return array();
	}

	/**
	 * Returns raw JS code
	 *
	 * @param \Silex\Application $oApp
	 *
	 * @return string
	 */
	public function GetJSInline(Application $oApp)
	{
		// Retrieve current person
		$oPerson = UserRights::GetContactObject();
		$sPersonName = $oPerson->GetName();
		$sPersonEmail = $oPerson->Get('email');

		// Retrieve API key
		$sAPIKey = MetaModel::GetModuleSetting(static::MODULE_CODE, 'api_key');

		// Nothing
		$sJS =
<<<JS
	window.intercomSettings = {
	    app_id: "$sAPIKey",
	    name: "$sPersonName", // Full name
	    email: "$sPersonEmail", // Email address
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
	            s.src = 'https://widget.intercom.io/widget/$sAPIKey';
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

	/**
	 * Returns raw HTML code to put at the end of the <body> tag
	 *
	 * @param \Silex\Application $oApp
	 *
	 * @return string
	 */
	public function GetBodyHTML(Application $oApp)
	{
		return '';
	}

	/**
	 * Returns raw HTML code to put at the end of the #main-wrapper element
	 *
	 * @param \Silex\Application $oApp
	 *
	 * @return string
	 */
	public function GetMainContentHTML(Application $oApp)
	{
		return '';
	}

	/**
	 * Returns raw HTML code to put at the end of the #topbar and #sidebar elements
	 *
	 * @param \Silex\Application $oApp
	 *
	 * @return string
	 */
	public function GetNavigationMenuHTML(Application $oApp)
	{
		return '';
	}
}
