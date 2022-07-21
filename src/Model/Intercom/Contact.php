<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */


namespace Combodo\iTop\Extension\IntercomIntegration\Model\Intercom;


use Combodo\iTop\Extension\IntercomIntegration\Exception\ModuleException;
use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use Exception;
use IssueLog;
use MetaModel;

/**
 * Class Contact is DTO for Intercom "Contact Model"
 *
 * @link https://developers.intercom.com/intercom-api-reference/reference/contacts-model
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Model\Intercom
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class Contact
{
	/** @var string Unique ID of the contact in Intercom */
	protected $sIntercomID;
	/** @var string ID of the workspace in Intercom */
	protected $sWorkspaceID;
	/** @var string Fullname of the contact as provided by Intercom */
	protected $sFullname;
	/** @var string Email of the contact */
	protected $sEmail;
	/** @var string|null The datamodel class of the contact in iTop, null if not provided */
	protected $sItopClass;
	/** @var string|null The ID of the contact in iTop, null if not provided */
	protected $sItopID;

	/** @var \DBObject|null The iTop Contact based on $sITopClass / sItopID */
	protected $oItopContact;

	/**
	 * @param array $aData {@link https://developers.intercom.com/building-apps/docs/canvas-kit#initialize-request}
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public static function FromCanvasKitInitializeConversationDetailsData($aData)
	{
		if (false === isset($aData['contact'])) {
			$sErrorMessage = 'Could not create contact model from Canvas Kit initialize Conversation Details as there is no "contact" entry in the data';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetLogChannel(), [
				'data' => $aData,
			]);
			throw new ModuleException($sErrorMessage);
		}

		return new static($aData['contact']);
	}

	public function __construct($aData)
	{
		$this->sIntercomID = $aData['id'];
		$this->sWorkspaceID = $aData['workspace_id'];
		$this->sFullname = $aData['name'];
		$this->sEmail = $aData['email'];
		$this->sItopClass = isset($aData['custom_attributes']['itop_contact_class']) ? $aData['custom_attributes']['itop_contact_class'] : null;
		$this->sItopID = isset($aData['custom_attributes']['itop_contact_id']) ? $aData['custom_attributes']['itop_contact_id'] : null;
		// $oItopContact is lazy loaded
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Contact\Contact::$sIntercomID
	 * @return string
	 */
	public function GetIntercomID()
	{
		return $this->sIntercomID;
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Contact\Contact::$sWorkspaceID
	 * @return string
	 */
	public function GetWorkspaceID()
	{
		return $this->sWorkspaceID;
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Contact\Contact::$sFullname
	 * @return string
	 */
	public function GetFullname()
	{
		return $this->sFullname;
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Contact\Contact::$sEmail
	 * @return string
	 */
	public function GetEmail()
	{
		return $this->sEmail;
	}

	/**
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Contact\Contact::$sItopClass
	 * @return string|null
	 */
	public function GetItopClass()
	{
		return $this->sItopClass;
	}

	/**
	 * @return string|null
	 *@see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Contact\Contact::$sItopID
	 */
	public function GetItopID()
	{
		return $this->sItopID;
	}

	/**
	 * @return \DBObject
	 * @throws \ArchivedObjectException
	 * @throws \CoreException
	 * @throws \Exception
	 * @see \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Contact\Contact::$oItopContact
	 */
	public function GetItopContact()
	{
		if (is_null($this->oItopContact)) {
			try {
				$this->oItopContact = MetaModel::GetObject($this->GetItopClass(), $this->GetItopID(), true, true);
			} catch (Exception $oException) {
				$sErrorMessage = 'Unable to retrieve contact object from the Canvas Kit contact model as the object does not exist';
				IssueLog::Error($sErrorMessage, ConfigHelper::GetLogChannel(), [
					'intercom_id' => $this->GetIntercomID(),
					'workspace_id' => $this->GetWorkspaceID(),
					'fullname' => $this->GetFullname(),
					'email' => $this->GetEmail(),
					'itop_contact_class' => $this->GetItopClass(),
					'itop_contact_id' => $this->GetItopID(),
				]);
				throw $oException;
			}
		}

		return $this->oItopContact;
	}
}