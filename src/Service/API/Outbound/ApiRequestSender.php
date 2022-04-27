<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Service\API\Outbound;


use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use utils;

/**
 * Class ApiRequestSender
 *
 * Send request to the Intercom API
 *
 * @link https://developers.intercom.com/intercom-api-reference/reference/introduction
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Service
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class ApiRequestSender
{
	/**
	 * @param string $sUrl URL of the request (operation) to call
	 * @param array $aData Data to send (will be JSON encoded)
	 *
	 * @return void
	 *
	 */
	public static function Send($sUrl, $aData)
	{
		utils::DoPostRequest($sUrl, $aData, static::PrepareAuthenticationHeader());
	}

	/**
	 * @return string The authentication header based on the access token eg. "Authorization: Bearer ABCDEFGHI"
	 */
	protected static function PrepareAuthenticationHeader()
	{
		return 'Authorization: Bearer '.ConfigHelper::GetModuleSetting('sync_app.access_token');
	}
}