<?php
/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound;

use AttributeText;
use BinaryExpression;
use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use DBObjectSearch;
use DBObjectSet;
use Exception;
use FieldExpression;
use IssueLog;
use MetaModel;
use utils;
use VariableExpression;

/**
 * Class AbstractIncomingEventsHandler
 *
 * Handle incoming events sent by the Intercom platform.
 * Needs to be extended.
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Service
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
abstract class AbstractIncomingEventsHandler
{
	/**
	 * @var string Each webhook event is signed via a Hash-based Message Authentication Code (HMAC) using the webhooks secret key. The HMAC-SHA1 algorithm is used to generate the webhook payload signature. The signature is passed along with each request in the headers as ‘X-Tawk-Signature.’
	 */
	protected $sSignature;
	/** @var false|string Raw received payload */
	protected $sPayload;
	/** @var array|false|null Parsed JSON data from the $sPayload */
	protected $aData;

	public function __construct()
	{
		$this->sSignature = $this->ReadEventSignature();
		$this->sPayload = file_get_contents('php://input');
		$this->aData = json_decode($this->sPayload, true);

		$this->CheckAccess();
		$this->CheckConsistency();
	}

	/**
	 * Process the operation by calling the right callback (static::OperationXXX) depending on the event payload metadata
	 */
	abstract public function HandleOperation();

	/**
	 * @return string The friendlyname of the created ticket
	 *
	 * @throws \ArchivedObjectException
	 * @throws \CoreCannotSaveObjectException
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \CoreWarning
	 * @throws \MySQLException
	 * @throws \OQLException
	 * @used-by \Combodo\iTop\Extension\TawkIntegration\Service\IncomingWebhooksHandler::HandleOperation()
	 */
	protected function OperationTicketCreate()
	{
		/** @var array $aConf */
		$aConf = ConfigHelper::GetModuleSetting('webhooks.create_ticket');
		// Check configuration consistency
		if ((false === isset($aConf['ticket_class']))
		|| (false === is_array($aConf['ticket_default_values']))) {
			$sErrorMessage = ConfigHelper::GetModuleCode().': Wrong configuration for "create_ticket" webhook, check documentation';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetModuleCode(), [
				'configuration' => $aConf,
			]);
			throw new Exception($sErrorMessage);
		}

		// Prepare ticket
		/** @var string $sTicketClass */
		$sTicketClass = $aConf['ticket_class'];
		$oTicket = MetaModel::NewObject($sTicketClass, $aConf['ticket_default_values']);

		// Look for matching caller
		$sCallerFriendlyname = $this->aData['requester']['name'];
		$sCallerEmail = (isset($this->aData['requester']['email']) && false === is_null($this->aData['requester']['email'])) ? $this->aData['requester']['email'] : null;
		$oCaller = $this->ContactLookupFromEmail($sCallerFriendlyname, $sCallerEmail);
		if (is_null($oCaller)) {
			$sErrorMessage = ConfigHelper::GetModuleCode().': No match found for the Contact';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetModuleCode(), [
				'caller_friendlyname' => $sCallerFriendlyname,
				'caller_email' => $sCallerEmail,
			]);
			throw new Exception($sErrorMessage);
		}

		// Fill ticket
		// - Caller
		$oTicket->Set('org_id', $oCaller->Get('org_id'));
		$oTicket->Set('caller_id', $oCaller->GetKey());
		// - Origin
		$oTicket->Set('origin', 'chat');
		// - Title
		$oTicket->Set('title', $this->aData['ticket']['subject']);
		// - Description
		$sDescription = $this->aData['ticket']['message'];
		//   Convert to HTML if necessary
		$oDescriptionAttDef = MetaModel::GetAttributeDef($sTicketClass, 'description');
		if (($oDescriptionAttDef instanceof AttributeText) && ($oDescriptionAttDef->GetFormat() === 'html')) {
			$sDescription = utils::TextToHtml($sDescription);
		}
		$oTicket->Set('description', $sDescription);
		// - Tawk.to ref
		$oTicket->Set('tawkto_ref', $this->aData['ticket']['humanId']);

		$oTicket->DBInsert();
		return $oTicket->GetRawName();
	}

	/**
	 * Check if the incoming event is legit and consistent, if not an exception is thrown.
	 *
	 * @throws \Exception
	 */
	protected function CheckAccess()
	{
		// Retrieve configured client secret
		$sClientSecret = ConfigHelper::GetModuleSetting('sync_app.client_secret');
		if (strlen($sClientSecret) === 0) {
			$sErrorMessage = ConfigHelper::GetModuleCode().': Parameter "sync_app.client_secret" must be set in the module\'s parameters for incoming events to work';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetModuleCode(), ['sync_app.client_secret' => $sClientSecret]);
			throw new Exception($sErrorMessage);
		}
	}

	/**
	 * Check if the received payload is well-formed
	 * @throws \Exception
	 */
	protected function CheckConsistency()
	{
		// Verify payload
		if (false === is_array($this->aData)) {
			// Redecode on purpose to get json last error message
			$aData = json_decode($this->sPayload, true);

			$sErrorMessage = ConfigHelper::GetModuleCode().': Invalid payload, could not be parsed to JSON';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetModuleCode(), [
				'json_error' => json_last_error_msg(),
				'payload' => $this->sPayload,
			]);
			throw new Exception($sErrorMessage);
		}
	}

	/**
	 * @param string      $sFriendlyname Friendlyname / fullname of the contact to find
	 * @param null|string $sEmail Email of the contact to find
	 *
	 * @return \DBObject|null Return the **first** Contact object matching $sFriendlyname (and $sEmail is provided), null if none found.
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \MissingQueryArgument
	 * @throws \MySQLException
	 * @throws \MySQLHasGoneAwayException
	 * @throws \OQLException
	 */
	protected function ContactLookupFromEmail($sFriendlyname, $sEmail = null)
	{
		$oSearch = DBObjectSearch::FromOQL('SELECT Contact WHERE friendlyname = :friendlyname');
		$oSearch->AllowAllData(true);
		$aParams = ['friendlyname' => $sFriendlyname];

		if (false === is_null($sEmail)) {
			$oSearch->AddConditionExpression(new BinaryExpression(new FieldExpression('email'), '=', new VariableExpression('email')));
			$aParams['email'] = $sEmail;
		}

		$oSet = new DBObjectSet($oSearch, [], $aParams);
		$iCount = $oSet->CountWithLimit(2);

		if ($iCount === 0) {
			return null;
		} else {
			return $oSet->Fetch();
		}
	}

	/**
	 * @return string The signature of the event that will be used to authenticate the call
	 */
	abstract protected function ReadEventSignature();
}
