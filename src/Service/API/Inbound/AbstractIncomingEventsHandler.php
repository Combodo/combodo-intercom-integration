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

		IssueLog::Debug('Incoming events handler instantiation', ConfigHelper::GetLogChannel(), [
			'handler_class' => static::class,
			'signature' => $this->sSignature,
			'payload' => $this->sPayload,
		]);

		$this->CheckAccess();
		$this->CheckConsistency();
	}

	/**
	 * Process the operation by calling the right callback (static::OperationXXX) depending on the event payload metadata
	 */
	abstract public function HandleOperation();

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
			$sErrorMessage = 'Parameter "sync_app.client_secret" must be set in the module\'s parameters for incoming events to work';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetLogChannel(), ['sync_app.client_secret' => $sClientSecret]);
			throw new ModuleException($sErrorMessage);
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

			$sErrorMessage = 'Invalid payload, could not be parsed to JSON';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetLogChannel(), [
				'json_error' => json_last_error_msg(),
				'payload' => $this->sPayload,
			]);
			throw new ModuleException($sErrorMessage);
		}
	}

	/**
	 * @return string The signature of the event that will be used to authenticate the call
	 */
	abstract protected function ReadEventSignature();
}
