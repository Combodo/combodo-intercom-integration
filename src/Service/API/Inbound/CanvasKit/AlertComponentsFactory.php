<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound\CanvasKit;


use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use Combodo\iTop\Extension\IntercomIntegration\Helper\IconHelper;
use Dict;
use DBObjectSet;
use MetaModel;

/**
 * Class AlertComponentsFactory
 * Factory to make an array of Canvas Kit components {@link https://developers.intercom.com/canvas-kit-reference/reference/content} to represent feedback messages in an alert
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound\CanvasKit
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class AlertComponentsFactory
{
	/** @var string Type of alert for error messages */
	const ENUM_ALERT_TYPE_ERROR = 'error';
	/** @var string Type of alert for success messages */
	const ENUM_ALERT_TYPE_SUCCESS = 'success';
	/** @var string Type of alert for messages when something was linked */
	const ENUM_ALERT_TYPE_LINK = 'link';

	/** @var int Width in pixels of an icon displayed in a canvas header */
	const HEADER_ICON_WIDTH = 32;
	/** @var int Height in pixels of an icon displayed in a canvas header */
	const HEADER_ICON_HEIGHT = 32;

	/**
	 * @param string|null $sTitle Optional title
	 * @param string|null $sDescription Optional description
	 *
	 * @return array Array of Canvas Kit components for a Canvas {@link https://developers.intercom.com/canvas-kit-reference/reference/content} to represent an error message in an alert
	 */
	public static function MakeErrorAlertComponents($sTitle = null, $sDescription = null)
	{
		return static::PrepareBaseAlertComponents(static::ENUM_ALERT_TYPE_ERROR, $sTitle, $sDescription);
	}

	/**
	 * @param string|null $sTitle Optional title
	 * @param string|null $sDescription Optional description
	 *
	 * @return array Array of Canvas Kit components for a Canvas {@link https://developers.intercom.com/canvas-kit-reference/reference/content} to represent a success message in an alert
	 */
	public static function MakeSuccessAlertComponents($sTitle = null, $sDescription = null)
	{
		return static::PrepareBaseAlertComponents(static::ENUM_ALERT_TYPE_SUCCESS, $sTitle, $sDescription);
	}

	/**
	 * @param string|null $sTitle Optional title
	 * @param string|null $sDescription Optional description
	 *
	 * @return array Array of Canvas Kit components for a Canvas {@link https://developers.intercom.com/canvas-kit-reference/reference/content} to represent that something was linked in an alert
	 */
	public static function MakeLinkAlertComponents($sTitle = null, $sDescription = null)
	{
		return static::PrepareBaseAlertComponents(static::ENUM_ALERT_TYPE_LINK, $sTitle, $sDescription);
	}

	//------------------------
	// Internal helpers
	//------------------------

	/**
	 * @param string      $sType {@see static::ENUM_ALERT_TYPE_ERROR}, {@see static::ENUM_ALERT_TYPE_SUCCESS}, ...
	 * @param string|null $sTitle Optional title
	 * @param string|null $sDescription Optional description
	 *
	 * @return array Array of components
	 * @throws \Exception
	 */
	protected static function PrepareBaseAlertComponents($sType, $sTitle = null, $sDescription = null)
	{
		$aComponents = [];

		// Prepare image
		switch ($sType) {
			case static::ENUM_ALERT_TYPE_ERROR:
				$sImageUrl = IconHelper::GetIconsFolderAbsUrl(IconHelper::ENUM_ICONS_PROVIDER_ICONS8).'icons8-error-cloud.svg';
				break;

			case static::ENUM_ALERT_TYPE_SUCCESS:
				$sImageUrl = IconHelper::GetIconsFolderAbsUrl(IconHelper::ENUM_ICONS_PROVIDER_ICONS8).'icons8-check-mark.svg';
				break;

			case static::ENUM_ALERT_TYPE_LINK:
				$sImageUrl = IconHelper::GetIconsFolderAbsUrl(IconHelper::ENUM_ICONS_PROVIDER_ICONS8).'icons8-link.svg';
				break;

			default:
				$sImageUrl = null;
				break;
		}

		// Image component
		if (false === is_null($sImageUrl)) {
			$aComponents[] = [
				"type" => "image",
				"url" => $sImageUrl,
				"align" => "center",
				"width" => static::HEADER_ICON_WIDTH,
				"height" => static::HEADER_ICON_HEIGHT,
			];
		}

		// Title component
		if (strlen($sTitle) > 0) {
			$aComponents[] = [
				"type" => "text",
				"style" => "header",
				"align" => "center",
				"text" => $sTitle,
			];
		}

		// Description component
		if (strlen($sDescription) > 0) {
			$aComponents[] = [
				"type" => "text",
				"style" => "paragraph",
				"text" => $sDescription,
			];
		}

		return $aComponents;
	}
}