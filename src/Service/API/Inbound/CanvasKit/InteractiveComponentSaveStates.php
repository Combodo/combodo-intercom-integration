<?php

/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */


namespace Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound\CanvasKit;


/**
 * Class InteractiveComponentSaveStates
 *
 * Enumeration of the possible save state of an interactive component, used for a visual hint of the current state of the component
 * Example in the "dropdown" component {@link https://developers.intercom.com/canvas-kit-reference/reference/dropdown}
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound\CanvasKit
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 1.1.0
 */
class InteractiveComponentSaveStates
{
	/** @var string "Unsaved", meaning that the component value hasn't been saved yet */
	const UNSAVED = 'unsaved';
	/** @var string "Saved", meaning that the component value has been saved */
	const SAVED = 'saved';
	/** @var string "Failed", meaning that the component failed when trying to save its value */
	const FAILED = 'failed';
}