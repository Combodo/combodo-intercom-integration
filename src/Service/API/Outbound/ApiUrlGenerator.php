<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Service\API\Outbound;


/**
 * Class ApiUrlGenerator
 *
 * Generate URL to the Intercom API
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Service
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class ApiUrlGenerator
{
	/** @var string Base URL for the Intercom API */
	const API_ENDPOINT_ROOT_URL = 'https://api.intercom.io/';

	/**
	 * @param string $sConversationID
	 * @link https://developers.intercom.com/intercom-api-reference/reference/reply-to-a-conversation
	 *
	 * @return string
	 */
	public static function ForConversationReply($sConversationID)
	{
		return static::API_ENDPOINT_ROOT_URL.'conversations/'.$sConversationID.'/reply';
	}
}