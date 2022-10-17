<?php
/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound;

use Combodo\iTop\Extension\IntercomIntegration\Exception\ModuleException;
use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use Combodo\iTop\Extension\IntercomIntegration\Helper\DatamodelObjectFinder;
use Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Webhook;
use Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\WebhookForNewConversationMessage;
use DBObjectSearch;
use DBObjectSet;
use Dict;
use Exception;
use IssueLog;
use MetaModel;

/**
 * Class IncomingWebhooksHandler
 *
 * Handle incoming webhooks sent by the Intercom platform.
 * @link https://developers.intercom.com/building-apps/docs/webhooks
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Service
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class IncomingWebhooksHandler extends AbstractIncomingEventsHandler
{
	/**
	 * @inheritDoc
	 */
	public function HandleOperation()
	{
		$aResponse = [];

		$oWebhook = Webhook::FromWebhookEventData($this->aData);
		switch ($oWebhook->GetTopicCode()) {
			case Webhook::ENUM_TOPIC_PING:
				$aResponse['status'] = 'ok';
				break;

			case Webhook::ENUM_TOPIC_CONVERSATION_USER_REPLIED:
			case Webhook::ENUM_TOPIC_CONVERSATION_ADMIN_REPLIED:
			case Webhook::ENUM_TOPIC_CONVERSATION_ADMIN_NOTED:
				// Prepare model from data
				$oNewMessageWebhook = WebhookForNewConversationMessage::FromWebhookEventData($this->aData);

				// Retrieve class/attributes mapping
				/** @var string $sTicketClass */
				$sTicketClass = ConfigHelper::GetModuleSetting('sync_app.ticket.class');
				/** @var array<string> $aTicketAttCodesMapping */
				$aTicketAttCodesMapping = ConfigHelper::GetModuleSetting('sync_app.ticket.attributes_mapping');
				// - Public log
				$sTicketPublicLogAttCode = 'public_log';
				if (isset($aTicketAttCodesMapping['public_log'])) {
					$sTicketPublicLogAttCode = $aTicketAttCodesMapping['public_log'];
				}
				// - Private log
				$sTicketPrivateLogAttCode = 'private_log';
				if (isset($aTicketAttCodesMapping['private_log'])) {
					$sTicketPrivateLogAttCode = $aTicketAttCodesMapping['private_log'];
				}
				// - Intercom ref.
				$sTicketIntercomRefAttCode = 'intercom_ref';
				if (isset($aTicketAttCodesMapping['intercom_ref'])) {
					$sTicketIntercomRefAttCode = $aTicketAttCodesMapping['intercom_ref'];
				}
				// - Intercom sync. activate
				$sTicketIntercomSyncActivatedAttCode = 'intercom_sync_activated';
				if (isset($aTicketAttCodesMapping['intercom_sync_activated'])) {
					$sTicketIntercomSyncActivatedAttCode = $aTicketAttCodesMapping['intercom_sync_activated'];
				}

				// Add new log entry
				// - Prepare entry
				$oConvSourceUser = DatamodelObjectFinder::GetUserFromContact($oNewMessageWebhook->GetItopContact());
				$sLogMessageDisclaimer = Dict::S('combodo-intercom-integration:SyncApp:SynchedTicket:LogEntry:NewMessageFromConversation');
				// Note: Icon is set in a "code" tag as it is one of the few the {@see \HTMLDOMSanitizer} allows to have a "class" attribute... 😕
				$sLogMessage = <<<HTML
<p>
	<code class="fas fa-link" style="background-color: transparent;"></code>
	<i>{$sLogMessageDisclaimer}</i>
</p>
<br />
{$oNewMessageWebhook->GetMessage()}
HTML;

				$aLogEntryAsArray = [
					'user_id' => is_null($oConvSourceUser) ? 0 : $oConvSourceUser->GetKey(),
					'user_login' => is_null($oConvSourceUser) ? Dict::Format('combodo-intercom-integration:SyncApp:SynchedTicket:LogEntry:FallbackUserLogin', $oNewMessageWebhook->GetFullname()) : $oConvSourceUser->Get('login'),
					'date' => '@' . $oNewMessageWebhook->GetFirstSentDateTime()->format('U'),
					'message' => $sLogMessage,
				];
				$sLogEntryAsJsonObject = json_decode(json_encode($aLogEntryAsArray));
				$sLogAttCode = $oWebhook->GetTopicCode() === Webhook::ENUM_TOPIC_CONVERSATION_ADMIN_NOTED ? $sTicketPrivateLogAttCode : $sTicketPublicLogAttCode;

				// - Retrieve linked tickets
				$oSearch = DBObjectSearch::FromOQL("SELECT $sTicketClass WHERE $sTicketIntercomRefAttCode = :intercom_ref AND $sTicketIntercomSyncActivatedAttCode = 'yes'");
				$oSet = new DBObjectSet($oSearch, [], [$sTicketIntercomRefAttCode => $oNewMessageWebhook->GetIntercomID()]);
				$oSet->OptimizeColumnLoad([$oSearch->GetClassAlias() => [$sLogAttCode]]);

				// - Update linked tickets with log entry
				while ($oTicket = $oSet->Fetch()) {
					// TODO: When extension min. iTop version will be 2.7.2, change this to follow the new API
					// - Reset the current change $oTicket::SetCurrentChange(null);
					// - Change the desired info $oTicket::SetTrackInfo('Created from Intercom'); $oTicket::SetTrackOrigin('custom-extension');
					/** @var \CMDBChange $oChange */
					$oChange = MetaModel::NewObject('CMDBChange');
					$oChange->Set('date', $oNewMessageWebhook->GetFirstSentDateTime()->format('U'));
					$oChange->Set('origin', 'custom-extension');
					$oChange->Set('userinfo', 'Intercom chat integration');
					$oChange->DBInsert();

					$oTicket::SetCurrentChange($oChange);

					/** @var \ormCaseLog $oLog */
					$oLog = $oTicket->Get($sLogAttCode);
					$oLog->AddLogEntryFromJSON($sLogEntryAsJsonObject, false);
					$oTicket->Set($sLogAttCode, $oLog);
					$oTicket->DBUpdate();
				}
				break;

			default:
				$aResponse['status'] = 'error';
				$aResponse['message'] = 'Webhook topic not supported ('.$oWebhook->GetTopicCode().')';
				break;
		}

		// Note: Response won't be processed by Intercom, it's just for debugging / testing purposes
		return json_encode($aResponse);
	}

	/**
	 * Signature is verified via SHA1 algorithm
	 * @inheritDoc
	 */
	protected function CheckAccess()
	{
		parent::CheckAccess();

		// Verify client secret
		$sClientSecret = ConfigHelper::GetModuleSetting('sync_app.client_secret');
		$sDigest = hash_hmac('sha1', $this->sPayload, $sClientSecret);
		// Note: Signature starts with the "sha1=" prefix, so we have to add it during the comparison {@link https://developers.intercom.com/intercom-api-reference/reference/webhook-models-1#signed-notifications}
		if ('sha1='.$sDigest !== $this->sSignature) {
			$sErrorMessage = 'Signature does not match payload and secret key';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetLogChannel(), [
				'signature' => $this->sSignature,
				'digest (hash_hmac sha1)' => $sDigest,
				'secret' => $sClientSecret,
				'payload' => $this->sPayload,
			]);
			throw new ModuleException($sErrorMessage);
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function ReadEventSignature()
	{
		if (false === isset($_SERVER['HTTP_X_HUB_SIGNATURE'])) {
			$sErrorMessage = 'Missing signature in HTTP header';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetLogChannel());
			throw new ModuleException($sErrorMessage);
		}

		return $_SERVER['HTTP_X_HUB_SIGNATURE'];
	}
}
