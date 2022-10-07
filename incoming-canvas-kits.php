<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

/**
 * Endpoint to process canvas kit calls from Intercom to display the sync. app. GUI within the chat dashboard
 *
 * @link https://developers.intercom.com/building-apps/docs/canvas-kit
 */

use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound\IncomingCanvasKitsHandler;

// Necessary for autoloaders to be enabled
require_once APPROOT.'/application/startup.inc.php';
require_once APPROOT.'/application/itopwebpage.class.inc.php';

// Processing
try {
	LoginWebPage::DoLoginEx(null, false, LoginWebPage::EXIT_HTTP_401);

	$oWebhooksHandler = new IncomingCanvasKitsHandler();
	$sResponse = $oWebhooksHandler->HandleOperation();

	IssueLog::Debug('Tip: When debugging Canvas Kit response, if it does not display on Intercom even though the JSON seems valid, paste it in the Canvas Kit Builder (https://app.intercom.com/a/canvas-kit-builder) as the error might not be in the JSON syntax but in the components specs.', ConfigHelper::GetLogChannel());
	IssueLog::Debug('Canvas Kit response', ConfigHelper::GetLogChannel(), [
		'response' => $sResponse,
	]);
	echo $sResponse;
} catch (Exception $oException) {
	IssueLog::Error($oException->getMessage(), ConfigHelper::GetLogChannel(), [
		'stacktrace' => $oException->getTraceAsString(),
	]);
	echo $oException->getMessage();
}
