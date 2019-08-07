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

use iPageUIExtension;
use iTopWebPage;
use MetaModel;
use UserRights;

class ConsoleUiExtension implements iPageUIExtension
{
	const MODULE_CODE = 'combodo-intercom-integration';

	/**
	 * @inheritDoc
	 */
	public function GetNorthPaneHtml(iTopWebPage $oPage)
	{
		// Retrieve current person
		$oPerson = UserRights::GetContactObject();
		$sPersonName = $oPerson->GetName();
		$sPersonEmail = $oPerson->Get('email');

		// Retrieve API key
		$sAPIKey = MetaModel::GetModuleSetting(static::MODULE_CODE, 'api_key');

		// Nothing
		$oPage->add_init_script(
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
		);
	}

	/**
	 * @inheritDoc
	 */
	public function GetSouthPaneHtml(iTopWebPage $oPage)
	{
		// Nothing
	}

	/**
	 * @inheritDoc
	 */
	public function GetBannerHtml(iTopWebPage $oPage)
	{
		// Nothing
	}
}
