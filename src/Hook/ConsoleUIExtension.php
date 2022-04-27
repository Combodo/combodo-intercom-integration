<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Extension;

use AbstractPageUIExtension;
use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use iTopWebPage;

/**
 * Class ConsoleUIExtension
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Extension
 * @author Guillaume Lajarige <guillaume.lajarige@combodo.com>
 */
class ConsoleUIExtension extends AbstractPageUIExtension
{
	/**
	 * @inheritDoc
	 * @throws \CoreException
	 */
	public function GetNorthPaneHtml(iTopWebPage $oPage)
	{
		$sJS = '';

		// Check if chat should be loaded
		if (!ConfigHelper::IsAllowed('backoffice'))
		{
			return $sJS;
		}

		// Add JS widget
		$oPage->add_init_script(ConfigHelper::GetWidgetJSSnippet());

		return $sJS;
	}
}
