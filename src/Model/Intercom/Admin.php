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
 * Class Admin is DTO for Intercom "Admin Model"
 *
 * @link https://developers.intercom.com/intercom-api-reference/reference/admin-model
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Model\Intercom
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class Admin
{
	/** @var string Unique ID of the contact in Intercom */
	protected $sIntercomID;
	/** @var string Fullname of the contact as provided by Intercom */
	protected $sFullname;
	/** @var string Email of the contact */
	protected $sEmail;

	/**
	 * @param array $aData {@link https://developers.intercom.com/building-apps/docs/canvas-kit#initialize-request}
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public static function FromCanvasKitInitializeConversationDetailsData($aData)
	{
		if (false === isset($aData['admin'])) {
			$sErrorMessage = 'Could not create admin model from Canvas Kit initialize Conversation Details as there is no "admin" entry in the data';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetLogChannel(), [
				'data' => $aData,
			]);
			throw new ModuleException($sErrorMessage);
		}

		return new static($aData['admin']);
	}

	public function __construct($aData)
	{
		$this->sIntercomID = $aData['id'];
		$this->sFullname = $aData['name'];
		$this->sEmail = $aData['email'];
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
}