<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */


namespace Combodo\iTop\Extension\IntercomIntegration\Model\Intercom;


use Combodo\iTop\Extension\IntercomIntegration\Exception\ModuleException;
use DateTime;

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
	/** @var \DateTime Date the conversation started */
	protected $oStartDateTime;
	// https://developers.intercom.com/intercom-api-reference/reference/conversation-model#source-object
	/**
	 * @var array{
	 *     type: string,
	 *     id: string,
	 *     body: string,
	 *     author: \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Contact|\Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Admin
	 * } Source part of the conversation, meaning the message that started the conversation
	 * @link https://developers.intercom.com/intercom-api-reference/reference/conversation-model#source-object
	 */
	protected $aSourcePart;
	/**
	 * @var array Parts of the conversation after the source part
	 * @link https://developers.intercom.com/intercom-api-reference/reference/conversation-model#conversation-part-object
	 */
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
		$this->oStartDateTime = new DateTime('@' . $aData['created_at']);
		$this->aSourcePart = $aData['source'];
		$this->aConversationParts = $aData['conversation_parts']['conversation_parts'];
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Conversation::$sIntercomID
	 * @return string
	 */
	public function GetIntercomID()
	{
		return $this->sIntercomID;
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Conversation::$oStartDateTime
	 * @return \DateTime
	 */
	public function GetStartDateTime()
	{
		return $this->oStartDateTime;
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Conversation::$aSourcePart
	 * @return array
	 */
	public function GetSourcePart()
	{
		return $this->aSourcePart;
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Conversation::$aConversationParts
	 * @return array
	 */
	public function GetConversationParts()
	{
		return $this->aConversationParts;
	}
}