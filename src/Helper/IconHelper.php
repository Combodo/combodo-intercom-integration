<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */


namespace Combodo\iTop\Extension\IntercomIntegration\Helper;


use utils;

/**
 * Class IconHelper
 *
 * Helper to access module's icons
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Helper
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class IconHelper
{
	/** @var string Provider of icons used as menu items decoration in lists (gray scale) */
	const ENUM_ICONS_PROVIDER_MATERIAL_IO = 'material.io';
	/** @var string Provider of icons used for feedback messages after actions (colored) */
	const ENUM_ICONS_PROVIDER_ICONS8 = 'icons8';

	/**
	 * @param string|null $sProvider Specify which specific folder to return {@see static::ENUM_ICONS_PROVIDER_MATERIAL_IO}, {@see static::ENUM_ICONS_PROVIDER_ICONS8}
	 *
	 * @return string Absolute URL to the folder of icons used in the Canvas Kit
	 * @throws \Exception
	 */
	public static function GetIconsFolderAbsUrl($sProvider = null)
	{
		$sURL = utils::GetAbsoluteUrlModulesRoot().ConfigHelper::GetModuleCode().'/asset/img/';

		switch ($sProvider) {
			case static::ENUM_ICONS_PROVIDER_MATERIAL_IO:
				$sURL .= 'material-icons/';
				break;

			case static::ENUM_ICONS_PROVIDER_ICONS8:
				$sURL .= 'icons8/';
				break;
		}

		return $sURL;
	}
}