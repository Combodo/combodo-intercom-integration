<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

/**
 * Endpoint to process webhook calls from Intercom to update a ticket from the chat conversation
 *
 * @link https://developers.intercom.com/building-apps/docs/webhooks
 */

use Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound\IncomingWebhooksHandler;
use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;

// Necessary for autoloaders to be enabled
require_once APPROOT.'/application/startup.inc.php';

// Processing
try {
	$oWebhooksHandler = new IncomingWebhooksHandler();
	$sResponse = $oWebhooksHandler->HandleOperation();

	IssueLog::Debug($sResponse, ConfigHelper::GetLogChannel());
	echo $sResponse;
} catch (Exception $oException) {
	IssueLog::Error($oException->getMessage(), ConfigHelper::GetLogChannel(), [
		'stacktrace' => $oException->getTraceAsString(),
	]);
	echo $oException->getMessage();
}
