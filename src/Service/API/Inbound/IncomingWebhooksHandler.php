<?php
/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound;

use Combodo\iTop\Extension\IntercomIntegration\Exception\ModuleException;
use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use Exception;
use IssueLog;

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
		// TODO: Implement some webhooks?

		return '{}';
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
		if ($sDigest !== $this->sSignature) {
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
