<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */


namespace Combodo\iTop\Extension\IntercomIntegration\Model\Intercom;


use Combodo\iTop\Extension\IntercomIntegration\Exception\ModuleException;

/**
 * Class Conversation is DTO for Intercom "Conversation Model"
 *
 * @link https://developers.intercom.com/intercom-api-reference/reference/conversation-model
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Model\Intercom
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class Conversation
{
	/** @var string ID of the conversation in the Intercom workspace */
	protected $sIntercomID;
	protected $oSourcePart;
	protected $aConversationParts;

	/**
	 * @param array $aData {@link https://developers.intercom.com/building-apps/docs/canvas-kit#initialize-request}
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public static function FromCanvasKitInitializeConversationDetailsData($aData)
	{
		if (false === isset($aData['conversation'])) {
			$sErrorMessage = 'Could not create conversation model from Canvas Kit initialize Conversation Details as there is no "conversation" entry in the data';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetLogChannel(), [
				'data' => $aData,
			]);
			throw new ModuleException($sErrorMessage);
		}

		return new static($aData['conversation']);
	}

	public function __construct($aData)
	{
		$this->sIntercomID = $aData['id'];
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Conversation::$sIntercomID
	 * @return string
	 */
	public function GetIntercomID()
	{
		return $this->sIntercomID;
	}
}