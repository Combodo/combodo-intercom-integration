<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Exception;


use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use Exception;

/**
 * Class ModuleException
 *
 * Exception that just prefixes the message with the module code to ease understanding the context when it pops to the user
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Exception
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class ModuleException extends Exception
{
	/**
	 * Prefixes the exception $sMessage with the module code to ease understanding the context when it pops to the user
	 *
	 * @inheritDoc
	 */
	public function __construct($message, $code = null, $previous = null)
	{
		parent::__construct(ConfigHelper::GetModuleCode().': '.$message, $code, $previous);
	}
}