<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Extension;

use AbstractPortalUIExtension;
use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class PortalUIExtension
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Extension
 * @author Guillaume Lajarige <guillaume.lajarige@combodo.com>
 */
class PortalUIExtension extends AbstractPortalUIExtension
{
	/**
	 * @inheritDoc
	 * @throws \CoreException
	 */
	public function GetJSInline(Container $oContainer)
	{
		$sJS = '';

		// Check if chat should be loaded
		if (!ConfigHelper::IsAllowed($_ENV['PORTAL_ID']))
		{
			return $sJS;
		}

		// Add JS widget
		$sJS .= ConfigHelper::GetWidgetJSSnippet();

		return $sJS;
	}
}
