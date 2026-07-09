<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Hook;

use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use iBackofficeScriptExtension;

/**
 * Class IntercomBackofficeScriptExtension
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Hook
 * @author Guillaume Lajarige <guillaume.lajarige@combodo.com>
 */
class IntercomBackofficeScriptExtension implements iBackofficeScriptExtension
{
	/**
	 * @inheritDoc
	 */
	public function GetScript(): string
	{
		// Check if chat should be loaded
		if (!ConfigHelper::IsAllowed('backoffice')) {
			return '';
		}

		// Add JS widget
		return ConfigHelper::GetWidgetJSSnippet();
	}
}
