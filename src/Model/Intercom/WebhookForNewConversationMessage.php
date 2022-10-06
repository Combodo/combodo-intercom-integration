<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */


namespace Combodo\iTop\Extension\IntercomIntegration\Model\Intercom;


use Combodo\iTop\Extension\IntercomIntegration\Exception\ModuleException;
use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use Combodo\iTop\Extension\IntercomIntegration\Helper\DatamodelObjectFinder;
use DateTime;
use Exception;
use IssueLog;
use MetaModel;

/**
 * Class WebhookForNewConversationMessage is DTO for Intercom "Webhook Model" of a new conversation model
 *
 * @link https://developers.intercom.com/intercom-api-reference/reference/webhook-models-1
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Model\Intercom
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class WebhookForNewConversationMessage extends Webhook
{
	/** @var string ID of the conversation in the Intercom workspace */
	protected $sIntercomID;
	/** @var strign Fullname of the author of the message */
	protected $sFullname;
	/** @var string Message as HTML */
	protected $sMessage;

	/** @var \DBObject|null The iTop Contact based on $sFullname */
	protected $oItopContact;

	public function __construct($aData)
	{
		parent::__construct($aData);

		$this->sIntercomID = $this->aItem['id'];

		$aConversationPart = $this->aItem['conversation_parts']['conversation_parts'][0];
		$this->sFullname = $aConversationPart['author']['name'];
		$this->sMessage = $aConversationPart['body'];
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\WebhookForNewConversationMessage::$sIntercomID
	 * @return string
	 */
	public function GetIntercomID()
	{
		return $this->sIntercomID;
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\WebhookForNewConversationMessage::$sFullname
	 * @return string
	 */
	public function GetFullname()
	{
		return $this->sFullname;
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\WebhookForNewConversationMessage::$sMessage
	 * @return string
	 */
	public function GetMessage()
	{
		return $this->sMessage;
	}

	/**
	 * @return \Contact
	 * @throws \ArchivedObjectException
	 * @throws \CoreException
	 * @throws \Exception
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Contact\Contact::$oItopContact
	 */
	public function GetItopContact()
	{
		if (is_null($this->oItopContact)) {
			try {
				$this->oItopContact = DatamodelObjectFinder::GetContactFromFriendlyname($this->sFullname);
			} catch (Exception $oException) {
				$sErrorMessage = 'Unable to retrieve contact object from the Webhook model as the object does not exist';
				IssueLog::Error($sErrorMessage, ConfigHelper::GetLogChannel(), [
					'intercom_id' => $this->GetIntercomID(),
					'workspace_id' => $this->GetWorkspaceID(),
					'fullname' => $this->GetFullname(),
				]);
				throw $oException;
			}
		}

		return $this->oItopContact;
	}
}