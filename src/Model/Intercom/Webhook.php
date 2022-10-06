<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */


namespace Combodo\iTop\Extension\IntercomIntegration\Model\Intercom;


use Combodo\iTop\Extension\IntercomIntegration\Exception\ModuleException;
use DateTime;

/**
 * Class Webhook is DTO for Intercom "Webhook Model"
 *
 * @link https://developers.intercom.com/intercom-api-reference/reference/webhook-models-1
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Model\Intercom
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class Webhook
{
	/** @var string Topic for when testing the webhook configuration from the Intercom app dashboard */
	const ENUM_TOPIC_PING = 'ping';
	/** @var string Topic for when a user replies in a conversation */
	const ENUM_TOPIC_CONVERSATION_USER_REPLIED = 'conversation.user.replied';
	/** @var string Topic for when an admin replies in a conversation */
	const ENUM_TOPIC_CONVERSATION_ADMIN_REPLIED = 'conversation.admin.replied';
	/** @var string Topic for when an admin adds a note in a conversation */
	const ENUM_TOPIC_CONVERSATION_ADMIN_NOTED = 'conversation.admin.noted';

	/** @var string ID of the workspace the webhook is from */
	protected $sWorkspaceID;
	/**
	 * @var string Topic code of the webhook
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Webhook::ENUM_TOPIC_CONVERSATION_USER_REPLIED
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Webhook::ENUM_TOPIC_CONVERSATION_ADMIN_REPLIED
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Webhook::ENUM_TOPIC_CONVERSATION_ADMIN_NOTED
	 */
	protected $sTopicCode;
	/** @var \DateTime Date the webhook was sent for the first time/attempt */
	protected $oFirstSentDateTime;
	/** @var array Item containing in the data of the webhook */
	protected $aItem;

	/**
	 * @param array $aData {@link https://developers.intercom.com/intercom-api-reference/reference/webhook-models-1}
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public static function FromWebhookEventData($aData)
	{
		return new static($aData);
	}

	public function __construct($aData)
	{
		$this->sWorkspaceID = $aData['app_id'];
		$this->sTopicCode = $aData['topic'];
		$this->oFirstSentDateTime = new DateTime('@' . $aData['first_sent_at']);
		$this->aItem = $aData['data']['item'];
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Webhook::$sWorkspaceID
	 * @return string
	 */
	public function GetWorkspaceID()
	{
		return $this->sWorkspaceID;
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Webhook::$sTopicCode
	 * @return string
	 */
	public function GetTopicCode()
	{
		return $this->sTopicCode;
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Webhook::$oFirstSentDateTime
	 * @return \DateTime
	 */
	public function GetFirstSentDateTime()
	{
		return $this->oFirstSentDateTime;
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Webhook::$aItem
	 * @return array
	 */
	public function GetItem()
	{
		return $this->aItem;
	}
}