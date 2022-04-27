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

$sAppRootFilePath = 'approot.inc.php';
// Depending on where the module is executed (env-xxx, datamodels with symlinks, extensions with symlinks, ...), approot file path can be different. So we try to find it.
for ($iDepth = 0; $iDepth <= 5; $iDepth++) {
	if (file_exists($sAppRootFilePath)) {
		require_once $sAppRootFilePath;
		break;
	}

	$sAppRootFilePath = '../'.$sAppRootFilePath;
}
// Necessary for autoloaders to be enabled
require_once APPROOT.'/application/startup.inc.php';

// Processing
try {
	$oWebhooksHandler = new IncomingCanvasKitsHandler();
	$sResponse = $oWebhooksHandler->HandleOperation();

	IssueLog::Debug('Canvas Kit response', ConfigHelper::GetModuleCode(), ['response' => $sResponse]);
	echo $sResponse;
} catch (Exception $oException) {
	IssueLog::Error($oException->getMessage(), ConfigHelper::GetModuleCode(), [
		'stacktrace' => $oException->getTraceAsString(),
	]);
	echo $oException->getMessage();
}
