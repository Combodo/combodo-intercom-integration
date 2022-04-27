<?php
/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound;

use ApplicationContext;
use AttributeCaseLog;
use AttributeText;
use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Admin;
use Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Contact;
use Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Conversation;
use Combodo\iTop\Extension\IntercomIntegration\Service\API\Outbound\ApiRequestSender;
use Combodo\iTop\Extension\IntercomIntegration\Service\API\Outbound\ApiUrlGenerator;
use DBObjectSearch;
use DBObjectSet;
use Dict;
use Exception;
use IssueLog;
use MetaModel;
use utils;

/**
 * Class
 * IncomingCanvasKitsHandler
 *
 * Handle
 * incoming
 * webhooks
 * sent
 * by
 * the
 * Intercom
 * platform.
 *
 * @link    https://developers.intercom.com/building-apps/docs/canvas-kit
 *
 * @package Combodo\iTop\Extension\IntercomIntegration\Service
 * @author  Guillaume
 *          Lajarige
 *          <guillaume.lajarige@combodo.com>
 * @since   1.1.0
 */
class IncomingCanvasKitsHandler extends AbstractIncomingEventsHandler
{
	const ENUM_TICKETS_LIST_TYPE_LINKED = 'linked';
	const ENUM_TICKETS_LIST_TYPE_ONGOING = 'ongoing';
	const ENUM_ICONS_PROVIDER_MATERIAL_IO = 'material.io';
	const ENUM_ICONS_PROVIDER_ICONS8 = 'icons8';

	/** @var int Max. number of tickets displayed in the Canvas Kit, this is to avoid UI to be too difficult to read and to exceed response's Canvas Kit's "content" max size */
	const MAX_TICKETS_DISPLAY = 30;
	/** @var string Prefix of "component_id" for IDs that aim at displaying a specific linked ticket (class/ID will be append) */
	const COMPONENT_ID_VIEW_LINKED_TICKET_PREFIX = 'view-linked-ticket';
	/** @var string Prefix of "component_id" for IDs that aim at displaying a specific ongoing ticket (class/ID will be append) */
	const COMPONENT_ID_VIEW_ONGOING_TICKET_PREFIX = 'view-ongoing-ticket';
	/** @var string Prefix of "component_id" for IDs that aim at linking a specific ticket (class/ID will be append) */
	const COMPONENT_ID_LINK_TICKET_PREFIX = 'link-ticket';
	/** @var int Width in pixels of an icon displayed in a canvas header */
	const HEADER_ICON_WIDTH = 32;
	/** @var int Height in pixels of an icon displayed in a canvas header */
	const HEADER_ICON_HEIGHT = 32;
	/** @var int Width in pixels of an icon displayed as decoration in a list item */
	const LIST_ITEM_ICON_WIDTH = 18;
	/** @var int Height in pixels of an icon displayed as decoration in a list item */
	const LIST_ITEM_ICON_HEIGHT = 18;

	/** @var string ID of the workspace the request is from */
	protected $sWorkspaceID;

	/**
	 * @inheritDoc
	 */
	public function __construct()
	{
		parent::__construct();
		$this->sWorkspaceID = $this->aData['workspace_id'];
	}

	/**
	 * @inheritDoc
	 */
	public function HandleOperation()
	{
		// Retrieve callback name from operation parameter
		$sOperation = utils::ReadParam('operation');
		if (strlen($sOperation) === 0) {
			throw new Exception(ConfigHelper::GetModuleCode().': Missing "operation" parameter');
		}

		switch ($sOperation) {
			case 'initialize-conversation-details':
				$sOperationCallbackName = 'Operation_InitializeConversationDetailsFlow';
				break;

			case 'submit-conversation-details':
				// Retrieve submitted "component_id" and check if the corresponding callback exists
				$sComponentID = $this->aData['component_id'];
				$sProcessedComponentID = $sComponentID;

				// Special case for viewing / linking a ticket as the class/ID will be part of the "component_id"
				// As we cannot pass context data through canvases, we have to pass that context as a suffix of the components IDs
				$aSpecialCasesPrefixes = [
					static::COMPONENT_ID_VIEW_LINKED_TICKET_PREFIX,
					static::COMPONENT_ID_VIEW_ONGOING_TICKET_PREFIX,
					static::COMPONENT_ID_LINK_TICKET_PREFIX
				];
				foreach ($aSpecialCasesPrefixes as $sSpecialCasePrefix) {
					if (strpos($sProcessedComponentID, $sSpecialCasePrefix) === 0) {
						$sProcessedComponentID = explode('::', $sProcessedComponentID)[0];
						break;
					}
				}

				$sOperationCallbackName = 'Operation_'.utils::ToCamelCase($sOperation).'Flow_'.utils::ToCamelCase($sProcessedComponentID).'Component';
				if (false === is_callable([static::class, $sOperationCallbackName])) {
					$sErrorMessage = ConfigHelper::GetModuleCode().': Callback method for operation not found';
					IssueLog::Error($sErrorMessage, ConfigHelper::GetModuleCode(), [
						'operation' => $sOperation,
						'component_id' => $sComponentID,
						'processed_component_id' => $sProcessedComponentID,
						'callback_method' => $sOperationCallbackName,
						'data' => $this->aData,
					]);
					throw new Exception($sErrorMessage);
				}
				break;

			default:
				$sErrorMessage = ConfigHelper::GetModuleCode().': Operation not supported';
				IssueLog::Error($sErrorMessage, ConfigHelper::GetModuleCode(), [
					'operation' => $sOperation,
					'data' => $this->aData,
				]);
				throw new Exception($sErrorMessage);
		}

		// Note: json_encode is not done globally here to allow any operation to return something else than JSON
		return $this->$sOperationCallbackName();
	}

	//-------------------------------
	// Conversation details methods
	//-------------------------------

	/**
	 * @return false|string The JSON encoded response of a Canvas Kit representing the initial display of the "conversation details"
	 */
	protected function Operation_InitializeConversationDetailsFlow()
	{
		// Make object models
		$oContactModel = Contact::FromCanvasKitInitializeConversationDetailsData($this->aData);
		$oConversationModel = Conversation::FromCanvasKitInitializeConversationDetailsData($this->aData);

		// Prepare component for ticket already linked to the conversation
		$oSet = $this->GetLinkedTicketsSet($oConversationModel);
		$iLinkedTicketsCount = $oSet->CountWithLimit(static::MAX_TICKETS_DISPLAY);
		$sTitle = $iLinkedTicketsCount === 0 ? Dict::S('combodo-intercom-integration:SyncApp:HomeCanvas:LinkedTickets:NoTicket') : Dict::Format('combodo-intercom-integration:SyncApp:HomeCanvas:LinkedTickets:SomeTickets',
			$iLinkedTicketsCount);
		$bDisabled = $iLinkedTicketsCount === 0;

		$aListLinkedTicketsComponent = [
			"type" => "item",
			"id" => "list-linked-tickets",
			"title" => $sTitle,
			"image" => $this->GetIconsFolderAbsUrl(static::ENUM_ICONS_PROVIDER_MATERIAL_IO)."link_black_18dp.svg",
			"image_width" => static::LIST_ITEM_ICON_WIDTH,
			"image_height" => static::LIST_ITEM_ICON_HEIGHT,
			"action" => [
				"type" => "submit",
			],
			"disabled" => $bDisabled,
		];

		// Prepare component for ongoing tickets for the contact
		$oSet = $this->GetOngoingTicketsSet($oContactModel);
		$iOngoingTicketsCount = $oSet->CountWithLimit(static::MAX_TICKETS_DISPLAY);
		$sTitle = $iOngoingTicketsCount === 0 ? Dict::S('combodo-intercom-integration:SyncApp:HomeCanvas:OngoingTickets:NoTicket') : Dict::Format('combodo-intercom-integration:SyncApp:HomeCanvas:OngoingTickets:SomeTickets',
			$iOngoingTicketsCount);
		$bDisabled = $iOngoingTicketsCount === 0;

		$aListOngoingTicketsComponent = [
			"type" => "item",
			"id" => "list-ongoing-tickets",
			"title" => $sTitle,
			"image" => $this->GetIconsFolderAbsUrl(static::ENUM_ICONS_PROVIDER_MATERIAL_IO)."format_list_bulleted_black_18dp.svg",
			"image_width" => static::LIST_ITEM_ICON_WIDTH,
			"image_height" => static::LIST_ITEM_ICON_HEIGHT,
			"action" => [
				"type" => "submit",
			],
			"disabled" => $bDisabled,
		];

		// Prepare response
		$aResponse = [
			"canvas" => [
				"content" => [
					"components" => [
						[
							"type" => "list",
							"items" => [
								[
									"type" => "item",
									"id" => "create-ticket",
									"title" => Dict::S('combodo-intercom-integration:SyncApp:HomeCanvas:CreateTicket'),
									"image" => $this->GetIconsFolderAbsUrl(static::ENUM_ICONS_PROVIDER_MATERIAL_IO)."add_black_18dp.svg",
									"image_width" => static::LIST_ITEM_ICON_WIDTH,
									"image_height" => static::LIST_ITEM_ICON_HEIGHT,
									"action" => [
										"type" => "submit",
									],
								],
								$aListLinkedTicketsComponent,
								$aListOngoingTicketsComponent,
							],
						],
						[
							"type" => "spacer",
							"size" => "m",
						],
						[
							"type" => "text",
							"text" => "*".Dict::S('combodo-intercom-integration:SyncApp:HomeCanvas:Hint:Title')."*",
							"style" => "header",
						],
						[
							"type" => "text",
							"text" => Dict::S('combodo-intercom-integration:SyncApp:HomeCanvas:Hint:Text'),
							"style" => "paragraph",
						],
					],
				],
			],
		];

		return json_encode($aResponse);
	}

	/**
	 * This is just an indirection to the initialize method which displays the home canvas
	 *
	 * @return false|string {@see static::Operation_InitializeConversationDetailsFlow}
	 */
	protected function Operation_SubmitConversationDetailsFlow_HomeComponent()
	{
		return $this->Operation_InitializeConversationDetailsFlow();
	}

	/**
	 * @return false|string The JSON encoded response of a Canvas Kit representing the list of the tickets linked to this conversation in the "conversation details"
	 */
	protected function Operation_SubmitConversationDetailsFlow_ListLinkedTicketsComponent()
	{
		// Make object models
		$oConversationModel = Conversation::FromCanvasKitInitializeConversationDetailsData($this->aData);

		$aResponse = [
			"canvas" => $this->PrepareTicketsListCanvas($this->GetLinkedTicketsSet($oConversationModel), static::ENUM_TICKETS_LIST_TYPE_LINKED),
		];

		return json_encode($aResponse);
	}

	/**
	 * @return false|string The JSON encoded response of a Canvas Kit representing the list of the ongoing tickets in the "conversation details"
	 */
	protected function Operation_SubmitConversationDetailsFlow_ListOnGoingTicketsComponent()
	{
		// Make object models
		$oContactModel = Contact::FromCanvasKitInitializeConversationDetailsData($this->aData);

		$aResponse = [
			"canvas" => $this->PrepareTicketsListCanvas($this->GetOngoingTicketsSet($oContactModel), static::ENUM_TICKETS_LIST_TYPE_ONGOING),
		];

		return json_encode($aResponse);
	}

	/**
	 * @return false|string The JSON encoded response of a Canvas Kit representing the linked ticket's details in the "conversation details"
	 */
	protected function Operation_SubmitConversationDetailsFlow_ViewLinkedTicketComponent()
	{
		$aResponse = [
			"canvas" => $this->PrepareViewTicketCanvas(static::ENUM_TICKETS_LIST_TYPE_LINKED),
		];

		return json_encode($aResponse);
	}

	/**
	 * @return false|string The JSON encoded response of a Canvas Kit representing the ongoing ticket's details in the "conversation details"
	 */
	protected function Operation_SubmitConversationDetailsFlow_ViewOngoingTicketComponent()
	{
		$aResponse = [
			"canvas" => $this->PrepareViewTicketCanvas(static::ENUM_TICKETS_LIST_TYPE_ONGOING),
		];

		return json_encode($aResponse);
	}

	/**
	 * @return false|string The JSON encoded response of a Canvas Kit representing the status of the attempt to link the ticket in the "conversation details"
	 */
	protected function Operation_SubmitConversationDetailsFlow_LinkTicketComponent()
	{
		// Make object models
		$oConversationModel = Conversation::FromCanvasKitInitializeConversationDetailsData($this->aData);
		$oAdminModel = Admin::FromCanvasKitInitializeConversationDetailsData($this->aData);

		$oTicket = $this->GetTicketFromComponentID();
		$sTicketClass = get_class($oTicket);
		$sTicketID = $oTicket->GetKey();
		$aTicketAttCodesMapping = ConfigHelper::GetModuleSetting('sync_app.ticket.attributes_mapping');

		$aComponents = [];
		try {
			$oTicket->Set($aTicketAttCodesMapping['intercom_ref'], $oConversationModel->GetIntercomID());
			$oTicket->DBUpdate();

			$aComponents[] = [
				"type" => "image",
				"url" => $this->GetIconsFolderAbsUrl(static::ENUM_ICONS_PROVIDER_ICONS8).'icons8-link.svg',
				"align" => "center",
				"width" => static::HEADER_ICON_WIDTH,
				"height" => static::HEADER_ICON_WIDTH,
			];
			$aComponents[] = [
				"type" => "text",
				"style" => "header",
				"align" => "center",
				"text" => Dict::S('combodo-intercom-integration:SyncApp:LinkTicketCanvas:Success:Title'),
			];
			$aComponents[] = [
				"type" => "text",
				"style" => "paragraph",
				"text" => Dict::Format('combodo-intercom-integration:SyncApp:LinkTicketCanvas:Success:Description', $oTicket->GetRawName()),
			];

			// Send "note" in conversation to trace that ticket has been linked so other admins can see it even though the ticket is linked to another conv in the future
			$sMessageTitleForHtml = utils::HtmlEntities(Dict::Format('combodo-intercom-integration:SyncApp:TicketLinkedMessage:Title', $oAdminModel->GetFullname()));
			$sTicketPortalUrl = ApplicationContext::MakeObjectUrl($sTicketClass, $sTicketID, ConfigHelper::GetModuleSetting('sync_app.portal_url_maker_class'));
			ApiRequestSender::Send(
				ApiUrlGenerator::ForConversationReply($oConversationModel->GetIntercomID()),
				[
					"message_type" => "note",
					"type" => "admin",
					"admin_id" => $oAdminModel->GetIntercomID(),
					"body" => <<<HTML
<html>
<body>
	<p>🔗 {$sMessageTitleForHtml}</p>
	<p><a href="{$sTicketPortalUrl}" target="_blank">{$oTicket->GetName()}</a></p>
</body>
</html>
HTML,
				]
			);
		} catch (Exception $oException) {
			$aComponents[] = [
				"type" => "image",
				"url" => $this->GetIconsFolderAbsUrl(static::ENUM_ICONS_PROVIDER_ICONS8).'icons8-error-cloud.svg',
				"align" => "center",
				"width" => static::HEADER_ICON_WIDTH,
				"height" => static::HEADER_ICON_WIDTH,
			];
			$aComponents[] = [
				"type" => "text",
				"style" => "header",
				"align" => "center",
				"text" => Dict::S('combodo-intercom-integration:SyncApp:LinkTicketCanvas:Failure:Title'),
			];
			$aComponents[] = [
				"type" => "text",
				"style" => "paragraph",
				"text" => Dict::Format('combodo-intercom-integration:SyncApp:LinkTicketCanvas:Failure:Description', $oException->getMessage()),
			];

			IssueLog::Error('Could not link ticket to conversation', ConfigHelper::GetModuleCode(), [
				'ticket' => $sTicketClass.'::'.$sTicketID,
				'conversation_id' => $oConversationModel->GetIntercomID(),
				'exception_message' => $oException->getMessage(),
				'exception_stacktrace' => $oException->getTraceAsString(),
			]);
		}
		$aComponents[] = $this->PrepareDividerComponent();
		$aComponents[] = $this->PrepareBackButtonComponent('home', Dict::S('combodo-intercom-integration:SyncApp:DoneButton:Title'));

		$aResponse = [
			"canvas" => [
				"content" => [
					"components" => $aComponents,
				],
			],
		];

		return json_encode($aResponse);
	}

	/**
	 * @return false|string The JSON encoded response of a Canvas Kit representing the ticket creation form in the "conversation details"
	 */
	protected function Operation_SubmitConversationDetailsFlow_CreateTicketComponent()
	{
		$sTicketClass = ConfigHelper::GetModuleSetting('sync_app.ticket.class');
		$aTicketAttCodesMapping = ConfigHelper::GetModuleSetting('sync_app.ticket.attributes_mapping');
		/** @var array<string> $aTicketDetailsAttCodes */
		$aTicketDetailsAttCodes = ConfigHelper::GetModuleSetting('sync_app.search_ticket.details_attributes');
		$sTicketDefaultState = MetaModel::GetDefaultState($sTicketClass);

		$aAttCodesToDisplay = [];
		// Retrieve ticket mandatory editable attributes to add to the form (read-only attributes are skipped)
		$aAllAttCodes = MetaModel::FlattenZList(MetaModel::GetZListItems($sTicketClass, 'details'));
		foreach ($aAllAttCodes as $sAttCode) {
			$oAttDef = MetaModel::GetAttributeDef($sTicketClass, $sAttCode);
			$iFlags = MetaModel::GetInitialStateAttributeFlags($sTicketClass, $sTicketDefaultState, $sAttCode);

			// TODO
			// Skip non applicable attribute types
			if ($oAttDef->IsExternalField() || $oAttDef->IsLinkSet()) {
				continue;
			}

			// Skip organization / caller as they will be automatically prefilled
			if (in_array($sAttCode, [$aTicketAttCodesMapping['org_id'], $aTicketAttCodesMapping['caller_id']])) {
				continue;
			}

			// Value is mandatory
			$bMandatory = ((!$oAttDef->IsNullAllowed()) || ($iFlags & OPT_ATT_MANDATORY));
		}

		//

		// Prepare response
		$aResponse = [
			"canvas" => [
				"content" => [
					"components" => [
						// TODO
					],
				],
			],
		];

		return json_encode($aResponse);
	}

	//-------------------------------
	// Helper methods
	//-------------------------------

	/**
	 * Signature
	 * is
	 * verified
	 * via
	 * SHA256
	 * algorithm
	 *
	 * @inheritDoc
	 */
	protected function CheckAccess()
	{
		parent::CheckAccess();

		// Verify client secret
		$sClientSecret = ConfigHelper::GetModuleSetting('sync_app.client_secret');
		$sDigest = hash_hmac('sha256', $this->sPayload, $sClientSecret);
		if ($sDigest !== $this->sSignature) {
			$sErrorMessage = ConfigHelper::GetModuleCode().': Signature does not match payload and secret key';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetModuleCode(), [
				'signature' => $this->sSignature,
				'digest (hash_hmac sha1)' => $sDigest,
				'secret' => $sClientSecret,
				'payload' => $this->sPayload,
			]);
			throw new Exception($sErrorMessage);
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function ReadEventSignature()
	{
		if (false === isset($_SERVER['HTTP_X_BODY_SIGNATURE'])) {
			$sErrorMessage = ConfigHelper::GetModuleCode().': Missing signature in HTTP header';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetModuleCode());
			throw new Exception($sErrorMessage);
		}

		return $_SERVER['HTTP_X_BODY_SIGNATURE'];
	}

	/**
	 * @param string|null $sProvider Specify which specific folder to return {@see static::ENUM_ICONS_PROVIDER_MATERIAL_IO}, {@see static::ENUM_ICONS_PROVIDER_ICONS8}
	 *
	 * @return string Absolute URL to the folder of icons used in the Canvas Kit
	 * @throws \Exception
	 */
	protected function GetIconsFolderAbsUrl($sProvider = null)
	{
		$sURL = utils::GetAbsoluteUrlModulesRoot().ConfigHelper::GetModuleCode().'/asset/img/';

		switch ($sProvider) {
			case static::ENUM_ICONS_PROVIDER_MATERIAL_IO:
				$sURL .= 'material-icons/';
				break;

			case static::ENUM_ICONS_PROVIDER_ICONS8:
				$sURL .= 'icons8/';
				break;
		}

		return $sURL;
	}

	/**
	 * @return \DBObject The ticket corresponding to the data passed in the component_id (<COMPONENT_ID_PREFIX>::<TICKET_CLASS>::<TICKET_ID>)
	 * @throws \ArchivedObjectException
	 * @throws \CoreException
	 */
	protected function GetTicketFromComponentID()
	{
		// Retrieve ticket class / ID, they are the last 2 strings between "::"
		$aComponentIDParts = explode('::', $this->aData['component_id']);
		$sTicketClass = $aComponentIDParts[count($aComponentIDParts) - 2];
		$sTicketID = $aComponentIDParts[count($aComponentIDParts) - 1];

		return MetaModel::GetObject($sTicketClass, $sTicketID, true, true);
	}

	/**
	 * @param \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Conversation $oConversationModel
	 *
	 * @return \DBObjectSet Set
	 *                      of
	 *                      tickets
	 *                      linked
	 *                      to
	 *                      $oConversationModel
	 */
	protected function GetLinkedTicketsSet(Conversation $oConversationModel)
	{
		$sTicketClass = ConfigHelper::GetModuleSetting('sync_app.ticket.class');
		$aTicketAttCodesMapping = ConfigHelper::GetModuleSetting('sync_app.ticket.attributes_mapping');

		$oSearch = DBObjectSearch::FromOQL("SELECT $sTicketClass WHERE {$aTicketAttCodesMapping['intercom_ref']} = :intercom_ref");
		$oSearch->AllowAllData(true);
		$oSet = new DBObjectSet($oSearch, [], ['intercom_ref' => $oConversationModel->GetIntercomID()]);

		return $oSet;
	}

	/**
	 * @param \Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Contact $oContactModel
	 *
	 * @return \DBObjectSet Set
	 *                      of
	 *                      ongoing
	 *                      tickets
	 *                      for
	 *                      $oContactModel
	 */
	protected function GetOngoingTicketsSet(Contact $oContactModel)
	{
		$sTicketClass = ConfigHelper::GetModuleSetting('sync_app.ticket.class');
		$aTicketAttCodesMapping = ConfigHelper::GetModuleSetting('sync_app.ticket.attributes_mapping');
		$aTicketDoneStates = ConfigHelper::GetModuleSetting('sync_app.search_ticket.done_states');

		$oSearch = DBObjectSearch::FromOQL("SELECT $sTicketClass WHERE {$aTicketAttCodesMapping['caller_id']} = :caller_id AND {$aTicketAttCodesMapping['status']} NOT IN (:states)");
		$oSearch->AllowAllData(true);
		$oSet = new DBObjectSet($oSearch, [], ['caller_id' => $oContactModel->GetItopContact()->GetKey(), 'states' => $aTicketDoneStates]);

		return $oSet;
	}

	//-------------------------------
	// Canvas Kit: Components
	//-------------------------------

	/**
	 * @param \DBObjectSet $oSet Set of tickets to display
	 * @param string       $sType List type {@see static::ENUM_TICKETS_LIST_TYPE_LINKED}, {@see static::ENUM_TICKETS_LIST_TYPE_ONGOING}
	 *
	 * @return array Canvas Kit List component for the tickets from $oSet {@link https://developers.intercom.com/canvas-kit-reference/reference/list}
	 */
	protected function PrepareTicketsListComponent(DBObjectSet $oSet, $sType)
	{
		$aItems = [];

		$sClass = $oSet->GetClass();
		$sClassAlias = $oSet->GetClassAlias();
		$aAttCodesToLoad = [];

		// Check if optional attributes are required
		$sStateAttCode = MetaModel::GetStateAttributeCode($sClass);
		$bHasStateAttCode = strlen($sStateAttCode) > 0;
		if ($bHasStateAttCode) {
			$aAttCodesToLoad[] = $sStateAttCode;
		}

		$sSubtitleAttCode = ConfigHelper::GetModuleSetting('sync_app.search_ticket.subtitle_attribute');
		$bHasSubtitleAttCode = strlen($sSubtitleAttCode) > 0;
		if ($bHasSubtitleAttCode) {
			$aAttCodesToLoad[] = $sSubtitleAttCode;
			$oSubtitleAttDef = MetaModel::GetAttributeDef($sClass, $sSubtitleAttCode);
		}

		// Prepare items for the list component
		$oSet->OptimizeColumnLoad([$sClassAlias => $aAttCodesToLoad]);
		$sPrefix = constant('static::COMPONENT_ID_VIEW_'.strtoupper($sType).'_TICKET_PREFIX');
		while ($oTicket = $oSet->Fetch()) {
			$aItem = [
				"type" => "item",
				"id" => $sPrefix."::{$sClass}::{$oTicket->GetKey()}",
				"title" => $oTicket->GetRawName(),
				"action" => [
					"type" => "submit",
				],
			];

			// Add optional attributes
			if ($bHasStateAttCode) {
				$aItem["subtitle"] = $oTicket->GetStateLabel();
			}
			// Note: $sSubtitleAttCode is not in the "subtitle" entry on purpose as we want the state to always be displayed first
			if ($bHasSubtitleAttCode) {
				$aItem["tertiary_text"] = $oSubtitleAttDef->GetValueLabel($oTicket->Get($sSubtitleAttCode));
			}

			$aItems[] = $aItem;
		}

		// Assemble final component
		$aComponent = [
			"type" => "list",
			"items" => $aItems,
		];

		return $aComponent;
	}

	/**
	 * @param string $sComponentToGoBackToID
	 *
	 * @return array Canvas Kit Button component for go back to the $sComponentToGoBackToID canvas {@link https://developers.intercom.com/canvas-kit-reference/reference/button}
	 */
	protected function PrepareBackButtonComponent($sComponentToGoBackToID, $sLabel = null)
	{
		if (is_null($sLabel)) {
			$sLabel = Dict::S('combodo-intercom-integration:SyncApp:BackButton:Title');
		}

		$aComponent = [
			"type" => "button",
			"id" => $sComponentToGoBackToID,
			"label" => $sLabel,
			"style" => "link",
			"action" => [
				"type" => "submit",
			],
		];

		return $aComponent;
	}

	/**
	 * @return array Canvas Kit Button component for a divider {@link https://developers.intercom.com/canvas-kit-reference/reference/divider}
	 */
	protected function PrepareDividerComponent()
	{
		$aComponent = [
			"type" => "divider",
		];

		return $aComponent;
	}

	//-------------------------------
	// Canvas Kit: Canvas
	//-------------------------------

	/**
	 * @param \DBObjectSet $oSet Set of tickets to display
	 * @param string       $sType List type {@see static::ENUM_TICKETS_LIST_TYPE_LINKED}, {@see static::ENUM_TICKETS_LIST_TYPE_ONGOING}
	 *
	 * @return array Canvas Kit Canvas for a list of tickets from $oSet {@link https://developers.intercom.com/canvas-kit-reference/reference/canvas}
	 */
	protected function PrepareTicketsListCanvas(DBObjectSet $oSet, $sType)
	{
		$aCanvas = [
			"content" => [
				"components" => [
					[
						"type" => "text",
						"style" => "header",
						"text" => Dict::S('combodo-intercom-integration:SyncApp:List'.ucfirst($sType).'TicketsCanvas:Title'),
					],
					[
						"type" => "spacer",
						"size" => "xs",
					],
					$this->PrepareTicketsListComponent($oSet, $sType),
					[
						"type" => "spacer",
						"size" => "m",
					],
					$this->PrepareBackButtonComponent('home'),
				],
			],
		];

		return $aCanvas;
	}

	/**
	 * @param string $sReferrerListType List type {@see static::ENUM_TICKETS_LIST_TYPE_LINKED}, {@see static::ENUM_TICKETS_LIST_TYPE_ONGOING}
	 *
	 * @return array Canvas Kit Canvas for a list of tickets from $oSet {@link https://developers.intercom.com/canvas-kit-reference/reference/canvas}
	 */
	protected function PrepareViewTicketCanvas($sReferrerListType)
	{
		// Make object models
		$oConversationModel = Conversation::FromCanvasKitInitializeConversationDetailsData($this->aData);

		$oTicket = $this->GetTicketFromComponentID();
		$sTicketClass = get_class($oTicket);
		$aTicketAttCodesMapping = ConfigHelper::GetModuleSetting('sync_app.ticket.attributes_mapping');

		$sTicketIntercomRef = $oTicket->Get($aTicketAttCodesMapping['intercom_ref']);
		$bTicketAlreadyLinked = strlen($sTicketIntercomRef) > 0;
		$bTicketAlreadyLinkedToThisConversation = $bTicketAlreadyLinked && ($sTicketIntercomRef === $oConversationModel->GetIntercomID());
		$bTicketAlreadyLinkedToAnotherConversation = $bTicketAlreadyLinked && ($sTicketIntercomRef !== $oConversationModel->GetIntercomID());

		$aAttComponents = [];
		$aAttCodes = ConfigHelper::GetModuleSetting('sync_app.view_ticket.details_attributes');
		foreach ($aAttCodes as $sAttCode) {
			$oAttDef = MetaModel::GetAttributeDef($sTicketClass, $sAttCode);
			$sAttLabel = MetaModel::GetLabel($sTicketClass, $sAttCode);

			switch (true) {
				case $oAttDef instanceof AttributeCaseLog:
					// Unsupported type
					continue 2;

				case $oAttDef instanceof AttributeText:
					$sAttValueLabel = $oTicket->Get($sAttCode);
					if ($oAttDef->GetFormat() === 'html') {
						$sAttValueLabel = utils::HtmlToText($sAttValueLabel);
					};
					break;

				default:
					$sAttValueLabel = $oAttDef->GetValueLabel($oTicket->Get($sAttCode));
					break;
			}

			// Label
			$aAttComponents[] = [
				"type" => "text",
				"style" => "header",
				"text" => $sAttLabel,
			];
			// Value
			$aAttComponents[] = [
				"type" => "text",
				"style" => "paragraph",
				// Note: We need to cast to string to force integers and such to be surrounded by quotes,
				// otherwise the JSON won't be valid for Intercom even though it's a valid JSON.
				"text" => (string) $sAttValueLabel,
			];
		}

		// Prepare components
		// - Header
		if ($bTicketAlreadyLinkedToThisConversation) {
			$sSubtitle = Dict::S('combodo-intercom-integration:SyncApp:ViewTicketCanvas:Subtitle:LinkedToThisConversation');
		} elseif ($bTicketAlreadyLinkedToAnotherConversation) {
			$sSubtitle = Dict::Format('combodo-intercom-integration:SyncApp:ViewTicketCanvas:Subtitle:LinkedToAnotherConversation', $oTicket->Get($aTicketAttCodesMapping['intercom_ref']), $this->sWorkspaceID);
		} else {
			$sSubtitle = Dict::S('combodo-intercom-integration:SyncApp:ViewTicketCanvas:Subtitle:LinkedToNoConversation');
		}
		$aHeaderComponents = [
			[
				"type" => "text",
				"style" => "header",
				"text" => $oTicket->GetRawName(),
			],
			[
				"type" => "text",
				"style" => "muted",
				"text" => $sSubtitle,
			],
			[
				"type" => "divider",
				"margin_bottom" => "none",
			]
		];

		// - Buttons
		$aButtonsComponents = [
			[
				"type" => "spacer",
				"size" => "m",
			],
		];
		// - Link to ticket
		$aButtonsComponents[] = [
			"type" => "button",
			"id" => static::COMPONENT_ID_LINK_TICKET_PREFIX."::{$sTicketClass}::{$oTicket->GetKey()}",
			"label" => Dict::S('combodo-intercom-integration:SyncApp:ViewTicketCanvas:LinkTicket'),
			"style" => "primary",
			"disabled" => $bTicketAlreadyLinkedToThisConversation,
			"action" => [
				"type" => "submit",
			],
		];
		// - Open in iTop backoffice button
		if (ConfigHelper::GetModuleSetting('sync_app.view_ticket.show_open_in_backoffice_button')) {
			$aButtonsComponents[] = [
				"type" => "button",
				"id" => "open-ticket-in-itop",
				"label" => Dict::S('combodo-intercom-integration:SyncApp:ViewTicketCanvas:OpeniTopBackoffice'),
				"style" => "secondary",
				"action" => [
					"type" => "url",
					"url" => ApplicationContext::MakeObjectUrl($sTicketClass, $sTicketID),
				],
			];
		}
		// - Back button
		$aButtonsComponents[] = $this->PrepareBackButtonComponent('list-'.$sReferrerListType.'-tickets');

		// Prepare response
		$aCanvas = [
			"content" => [
				"components" => array_merge($aHeaderComponents, $aAttComponents, $aButtonsComponents),
			],
		];

		return $aCanvas;
	}
}
