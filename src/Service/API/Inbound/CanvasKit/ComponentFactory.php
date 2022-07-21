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
use IssueLog;
use MetaModel;

/**
 * Class CanvasKitComponentFactory
 * Factory to make Canvas Kit components {@link https://developers.intercom.com/canvas-kit-reference/reference}
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound\CanvasKit
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class ComponentFactory
{
	/**
	 * @return array Canvas Kit Button component for a divider {@link https://developers.intercom.com/canvas-kit-reference/reference/divider}
	 */
	public static function MakeDivider()
	{
		return [
			"type" => "divider",
			"margin_bottom" => "none",
		];
	}

	/**
	 * @return array Canvas Kit Spacer component for an extra small-size spacer {@link https://developers.intercom.com/canvas-kit-reference/reference/spacer}
	 */
	public static function MakeExtraSmallSpacer()
	{
		return [
			"type" => "spacer",
			"size" => "xs",
		];
	}

	/**
	 * @return array Canvas Kit Spacer component for a small-size spacer {@link https://developers.intercom.com/canvas-kit-reference/reference/spacer}
	 */
	public static function MakeSmallSpacer()
	{
		return [
			"type" => "spacer",
			"size" => "s",
		];
	}

	/**
	 * @return array Canvas Kit Spacer component for a medium-size spacer {@link https://developers.intercom.com/canvas-kit-reference/reference/spacer}
	 */
	public static function MakeMediumSpacer()
	{
		return [
			"type" => "spacer",
			"size" => "m",
		];
	}

	/**
	 * @return array Canvas Kit Spacer component for a large-size spacer {@link https://developers.intercom.com/canvas-kit-reference/reference/spacer}
	 */
	public static function MakeLargeSpacer()
	{
		return [
			"type" => "spacer",
			"size" => "l",
		];
	}

	/**
	 * @return array Canvas Kit Spacer component for a extra large-size spacer {@link https://developers.intercom.com/canvas-kit-reference/reference/spacer}
	 */
	public static function MakeExtraLargeSpacer()
	{
		return [
			"type" => "spacer",
			"size" => "xl",
		];
	}

	/**
	 * @param string        $sID
	 * @param string|null   $sLabel If null, default label will be set
	 * @param bool          $bDisabled Whether the button is disabled or not, meaning that the user cannot click on it
	 *
	 * @return array Canvas Kit Button component for go back to the $sComponentIDToGoBackTo canvas {@link https://developers.intercom.com/canvas-kit-reference/reference/button}
	 */
	public static function MakeSubmitButton($sID, $sLabel = null, $bDisabled = false)
	{
		if (is_null($sLabel)) {
			$sLabel = Dict::S('combodo-intercom-integration:SyncApp:BackButton:Title');
		}

		$aComponent = static::PrepareBaseButton($sID, $sLabel, "primary", $bDisabled);
		$aComponent["action"] = [
			"type" => "submit",
		];

		return $aComponent;
	}

	/**
	 * @param string        $sComponentIDToGoBackTo
	 * @param string|null   $sLabel If null, default label will be set
	 *
	 * @return array Canvas Kit Button component for go back to the $sComponentIDToGoBackTo canvas {@link https://developers.intercom.com/canvas-kit-reference/reference/button}
	 */
	public static function MakeBackButton($sComponentIDToGoBackTo, $sLabel = null)
	{
		if (is_null($sLabel)) {
			$sLabel = Dict::S('combodo-intercom-integration:SyncApp:BackButton:Title');
		}

		$aComponent = static::PrepareBaseButton($sComponentIDToGoBackTo, $sLabel, "link");
		$aComponent["action"] = [
			"type" => "submit",
		];

		return $aComponent;
	}

	/**
	 * @param string $sID
	 * @param string $sLabel Label of the button
	 * @param string $sURL URL to be opened in a new tab
	 *
	 * @return array Canvas Kit Button component for opening an $sURL in a new tab {@link https://developers.intercom.com/canvas-kit-reference/reference/button}
	 */
	public static function MakeUrlButton($sID, $sLabel, $sURL)
	{
		$aComponent = static::PrepareBaseButton($sID, $sLabel, "secondary");
		$aComponent["action"] = [
			"type" => "url",
			"url" => $sURL,
		];

		return $aComponent;
	}

	/**
	 * @param string|       $sID ID of the input, on submit value will be retrieved using this ID
	 * @param string|null   $sLabel Label of the field
	 * @param string|null   $sValue Prefilled value of the field input
	 * @param string|null   $sPlaceholder Hint displayed within the field input when there is no value yet
	 * @param string        $sSaveState Visual hint of the current state of the field
	 *                        * {@see \Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound\CanvasKit\InteractiveComponentSaveStates}
	 *                        * {@link https://developers.intercom.com/canvas-kit-reference/reference/input}
	 * @param false         $bIsDisabled Set to true to disable the field and avoid any interaction from the user
	 *
	 * @return array Canvas Kit Input component for a text input (1-line string) field {@link https://developers.intercom.com/canvas-kit-reference/reference/input}
	 */
	public static function MakeStringField($sID, $sLabel = null, $sValue = null, $sPlaceholder = null, $sSaveState = InteractiveComponentSaveStates::UNSAVED, $bIsDisabled = false)
	{
		$aComponent = [
			"type" => "input",
			"id" => $sID,
			"label" => $sLabel,
			"save_state" => $sSaveState,
			"disabled" => $bIsDisabled,
		];

		if (strlen($sValue) > 0) {
			$aComponent['value'] = $sValue;
		}
		if (strlen($sPlaceholder) > 0) {
			$aComponent['placeholder'] = $sPlaceholder;
		}

		return $aComponent;
	}

	/**
	 * @param string        $sID ID of the textarea, on submit value will be retrieved using this ID
	 * @param string|null   $sLabel Label of the field
	 * @param string|null   $sValue Prefilled value of the field input
	 * @param string|null   $sPlaceholder Hint displayed within the field input when there is no value yet
	 * @param false         $bInError Set to true to display the field as in error
	 * @param false         $bIsDisabled Set to true to disable the field and avoid any interaction from the user
	 *
	 * @return array Canvas Kit TextArea component for a textarea input field {@link https://developers.intercom.com/canvas-kit-reference/reference/text-area}
	 */
	public static function MakeTextareaField($sID, $sLabel = null, $sValue = null, $sPlaceholder = null, $bInError = false, $bIsDisabled = false)
	{
		$aComponent = [
			"type" => "textarea",
			"id" => $sID,
			"label" => $sLabel,
			"error" => $bInError,
			"disabled" => $bIsDisabled,
		];

		if (strlen($sValue) > 0) {
			$aComponent['value'] = $sValue;
		}
		if (strlen($sPlaceholder) > 0) {
			$aComponent['placeholder'] = $sPlaceholder;
		}

		return $aComponent;
	}

	/**
	 * @param \DBObjectSet $oSet Set of tickets to display
	 * @param string       $sListItemPrefix Prefix used for the list items IDs
	 *
	 * @return array Canvas Kit List component for the tickets from $oSet {@link https://developers.intercom.com/canvas-kit-reference/reference/list}
	 */
	public static function MakeObjectsList(DBObjectSet $oSet, $sListItemPrefix)
	{
		$aItems = [];

		$sClass = $oSet->GetClass();
		$sClassAlias = $oSet->GetClassAlias();
		$aAttCodesToLoad = [];

		// Check if optional attributes are required
		$sStateAttCode = MetaModel::GetStateAttributeCode($sClass);
		$bHasStateAttCode = strlen($sStateAttCode) > 0;
		if ($bHasStateAttCode) {
			$aAttCodesToLoad[] = $sStateAttCode;
		}

		$sSubtitleAttCode = ConfigHelper::GetModuleSetting('sync_app.search_ticket.subtitle_attribute');
		$bHasSubtitleAttCode = strlen($sSubtitleAttCode) > 0;
		if ($bHasSubtitleAttCode) {
			$aAttCodesToLoad[] = $sSubtitleAttCode;
			$oSubtitleAttDef = MetaModel::GetAttributeDef($sClass, $sSubtitleAttCode);
		}

		// Prepare items for the list component
		$oSet->OptimizeColumnLoad([$sClassAlias => $aAttCodesToLoad]);
		while ($oTicket = $oSet->Fetch()) {
			$aItem = [
				"type" => "item",
				"id" => $sListItemPrefix."::{$sClass}::{$oTicket->GetKey()}",
				"title" => $oTicket->GetRawName(),
				"action" => [
					"type" => "submit",
				],
			];

			// Add optional attributes
			if ($bHasStateAttCode) {
				$aItem["subtitle"] = $oTicket->GetStateLabel();
			}
			// Note: $sSubtitleAttCode is not in the "subtitle" entry on purpose as we want the state to always be displayed first
			if ($bHasSubtitleAttCode) {
				$aItem["tertiary_text"] = $oSubtitleAttDef->GetValueLabel($oTicket->Get($sSubtitleAttCode));
			}

			$aItems[] = $aItem;
		}

		// Assemble final component
		$aComponent = [
			"type" => "list",
			"items" => $aItems,
		];

		return $aComponent;
	}

	public static function MakeEnumValuesDropdown($sID, $aAllowedValues, $sLabel = null, $sValue = null, $bSubmitAction = false, $sSaveState = InteractiveComponentSaveStates::UNSAVED, $bIsDisabled = false)
	{
		$aComponent = [
			"id" => $sID,
		];

		if (strlen($sLabel) > 0) {
			$aComponent["label"] = $sLabel;
		}

		// In case there is no value to display in the dropdown, we can't use a Dropdown Component as its specs specify that it needs at least 1 option.
		// As a fallback, we display a r/o text filed.
		if (count($aAllowedValues) === 0) {
			$aComponent["type"] = "input";
			$aComponent["disabled"] = true;

			return $aComponent;
		}

		// Prepare dropdown
		$aComponent["type"] = "dropdown";
		$aComponent["save_state"] = $sSaveState;
		$aComponent["disabled"] = $bIsDisabled;

		if (strlen($sValue) > 0) {
			// Check that selected value among allowed values otherwise Intercom will crash
			if (in_array($sValue, array_keys($aAllowedValues))) {
				$aComponent["value"] = (string) $sValue; // Force key to be a strina, otherwise it won't be valid {@link https://developers.intercom.com/canvas-kit-reference/reference/dropdown}
			} else {
				IssueLog::Debug('Selected value not set for attribute as it was not among the allowed values', ConfigHelper::GetLogChannel(), [
					'attcode' => $sID,
					'value' => $sValue,
					'allowed_values' => $aAllowedValues,
				]);
			}
		}

		if ($bSubmitAction) {
			$aComponent["action"] = [
				"type" => "submit",
			];
		}

		if (strlen($sSaveState) > 0) {
			$aComponent["save_state"] = $sSaveState;
		}

		// Prepare options
		$aOptions = [];

		foreach ($aAllowedValues as $sAllowedValueKey => $sAllowedValueLabel) {
			$aOptions[] = [
				"type" => "option",
				"id" => (string) $sAllowedValueKey, // Force key to be a strina, otherwise it won't be valid {@link https://developers.intercom.com/canvas-kit-reference/reference/dropdown}
				"text" => $sAllowedValueLabel,
			];
		}

		// Assemble final component
		$aComponent["options"] = $aOptions;

		return $aComponent;
	}

	//------------------------
	// Internal helpers
	//------------------------

	/**
	 * @param string $sID
	 * @param string $sLabel
	 * @param string $sStyle Must be a valid style, see {@link https://developers.intercom.com/canvas-kit-reference/reference/button}
	 * @param bool $bDisabled Whether the button is disabled or not, meaning that the user cannot click on it
	 *
	 * @return array Canvas Kit Button component for a button WITHOUT any action yet {@link https://developers.intercom.com/canvas-kit-reference/reference/button}
	 */
	protected static function PrepareBaseButton($sID, $sLabel, $sStyle = 'primary', $bDisabled = false)
	{
		return [
			"type" => "button",
			"id" => $sID,
			"label" => $sLabel,
			"style" => $sStyle,
			"disabled" => $bDisabled,
		];
	}
}