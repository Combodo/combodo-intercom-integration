<?php
/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound;

use ApplicationContext;
use AttributeCaseLog;
use AttributeExternalKey;
use AttributeText;
use Combodo\iTop\Extension\IntercomIntegration\Exception\ModuleException;
use Combodo\iTop\Extension\IntercomIntegration\Helper\ConfigHelper;
use Combodo\iTop\Extension\IntercomIntegration\Helper\IconHelper;
use Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Admin;
use Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Contact;
use Combodo\iTop\Extension\IntercomIntegration\Model\Intercom\Conversation;
use Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound\CanvasKit\AlertComponentsFactory;
use Combodo\iTop\Extension\IntercomIntegration\Service\API\Inbound\CanvasKit\ComponentFactory;
use Combodo\iTop\Extension\IntercomIntegration\Service\API\Outbound\ApiRequestSender;
use Combodo\iTop\Extension\IntercomIntegration\Service\API\Outbound\ApiUrlGenerator;
use DBObject;
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
	/** @var string Type of list displayed in the Canvas Kit */
	const ENUM_TICKETS_LIST_TYPE_LINKED = 'linked';
	/** @var string Type of list displayed in the Canvas Kit */
	const ENUM_TICKETS_LIST_TYPE_ONGOING = 'ongoing';

	/** @var int Max. number of tickets displayed in the Canvas Kit, this is to avoid UI to be too difficult to read and to exceed response's Canvas Kit's "content" max size */
	const MAX_TICKETS_DISPLAY = 30;
	/** @var string Prefix of "component_id" for IDs that aim at displaying a specific linked ticket (class/ID will be append) */
	const COMPONENT_ID_VIEW_LINKED_TICKET_PREFIX = 'view-linked-ticket';
	/** @var string Prefix of "component_id" for IDs that aim at displaying a specific ongoing ticket (class/ID will be append) */
	const COMPONENT_ID_VIEW_ONGOING_TICKET_PREFIX = 'view-ongoing-ticket';
	/** @var string Prefix of "component_id" for IDs that aim at linking a specific ticket (class/ID will be append) */
	const COMPONENT_ID_LINK_TICKET_PREFIX = 'link-ticket';
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
					$sErrorMessage = 'Callback method for operation not found';
					IssueLog::Error($sErrorMessage, ConfigHelper::GetLogChannel(), [
						'operation' => $sOperation,
						'component_id' => $sComponentID,
						'processed_component_id' => $sProcessedComponentID,
						'callback_method' => $sOperationCallbackName,
						'data' => $this->aData,
					]);
					throw new ModuleException($sErrorMessage);
				}
				break;

			default:
				$sErrorMessage = 'Operation not supported';
				IssueLog::Error($sErrorMessage, ConfigHelper::GetLogChannel(), [
					'operation' => $sOperation,
					'data' => $this->aData,
				]);
				throw new ModuleException($sErrorMessage);
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
			"image" => IconHelper::GetIconsFolderAbsUrl(IconHelper::ENUM_ICONS_PROVIDER_MATERIAL_IO)."link_black_18dp.svg",
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
			"image" => IconHelper::GetIconsFolderAbsUrl(IconHelper::ENUM_ICONS_PROVIDER_MATERIAL_IO)."format_list_bulleted_black_18dp.svg",
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
									"image" => IconHelper::GetIconsFolderAbsUrl(IconHelper::ENUM_ICONS_PROVIDER_MATERIAL_IO)."add_black_18dp.svg",
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
						ComponentFactory::MakeMediumSpacer(),
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

			$aComponents = array_merge($aComponents, AlertComponentsFactory::MakeLinkAlertComponents(
				Dict::S('combodo-intercom-integration:SyncApp:LinkTicketCanvas:Success:Title'),
				Dict::Format('combodo-intercom-integration:SyncApp:LinkTicketCanvas:Success:Description', $oTicket->GetRawName())
			));

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
			$aComponents = array_merge($aComponents, AlertComponentsFactory::MakeErrorAlertComponents(
				Dict::S('combodo-intercom-integration:SyncApp:LinkTicketCanvas:Failure:Title'),
				Dict::Format('combodo-intercom-integration:SyncApp:LinkTicketCanvas:Failure:Description', $oException->getMessage())
			));

			IssueLog::Error('Could not link ticket to conversation', ConfigHelper::GetLogChannel(), [
				'ticket' => $sTicketClass.'::'.$sTicketID,
				'conversation_id' => $oConversationModel->GetIntercomID(),
				'exception_message' => $oException->getMessage(),
				'exception_stacktrace' => $oException->getTraceAsString(),
			]);
		}
		$aComponents[] = ComponentFactory::MakeDivider();
		$aComponents[] = ComponentFactory::MakeBackButton('home', Dict::S('combodo-intercom-integration:SyncApp:DoneButton:Title'));

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
		$oCreatedTicket = null;
		$sTicketClass = ConfigHelper::GetModuleSetting('sync_app.ticket.class');

		$bSubmittedForm = isset($this->aData['input_values']);
		if ($bSubmittedForm) {
			$oCreatedTicket = $this->CreateTicketFromFormValues($sTicketClass, $this->aData['input_values']);
		}

		// TODO: Remove after dev
IssueLog::Debug('input values', ConfigHelper::GetLogChannel(), [
	'values' => $this->aData['input_values'],
]);

		// Display form until valid submission
		if (is_null($oCreatedTicket)) {
			$aCanvas = $this->PrepareCreateTicketCanvas($sTicketClass);

			// Form was submitted but ticket was not created, display canvas again with error feedback
			if ($bSubmittedForm) {
				// Prepare feedback
				$aFeedbackComponents = AlertComponentsFactory::MakeErrorAlertComponents(
					Dict::S('combodo-intercom-integration:SyncApp:CreateTicketCanvas:Failure:Title'),
					Dict::S('combodo-intercom-integration:SyncApp:CreateTicketCanvas:Failure:Description')
				);
				$aFeedbackComponents[] = ComponentFactory::MakeMediumSpacer();

				// Prepend feedback to current canvas
				$aCanvas['content']['components'] = array_merge($aFeedbackComponents, $aCanvas['content']['components']);
			}
		}
		// Show confirmation once creation form is validated
		else {
			$aCanvas = $this->PrepareTicketCreationConfirmationCanvas($oCreatedTicket);
		}

		// Prepare response
		$aResponse = [
			"canvas" => $aCanvas,
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
		if (false === isset($_SERVER['HTTP_X_BODY_SIGNATURE'])) {
			$sErrorMessage = 'Missing signature in HTTP header';
			IssueLog::Error($sErrorMessage, ConfigHelper::GetLogChannel());
			throw new ModuleException($sErrorMessage);
		}

		return $_SERVER['HTTP_X_BODY_SIGNATURE'];
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

	protected function CreateTicketFromFormValues($sTicketClass, $aValues)
	{
		try
		{
			// Make object models
			$oContactModel = Contact::FromCanvasKitInitializeConversationDetailsData($this->aData);

			// Prepare default value
			$aTicketDefaultValues = [];
			$oCaller = $oContactModel->GetItopContact();
			/** @var array<string> $aTicketConfiguredAttCodes */
			$aTicketConfiguredAttCodes = ConfigHelper::GetModuleSetting('sync_app.create_ticket.form_attributes');

//			if (isset($aTicketConfiguredAttCodes['org_id'])) {
//				$aTicketDefaultValues[$aTicketConfiguredAttCodes['org_id']] = $oCaller->Get('org_id');
//			}
//			if (isset($aTicketConfiguredAttCodes['caller_id'])) {
//				$aTicketDefaultValues[$aTicketConfiguredAttCodes['caller_id']] = $oCaller->GetKey();
//			}

			// Create object
			$oTicket = MetaModel::NewObject($sTicketClass, $aTicketDefaultValues);

			// Apply values to the object
			foreach ($aValues as $sAttCode => $value)
			{
				$oAttDef = MetaModel::GetAttributeDef($sTicketClass, $sAttCode);
				switch ($oAttDef->GetEditClass()) {
					case 'Text':
					case 'HTML':
						if ($oAttDef->GetFormat() === 'html') {
							$value = utils::TextToHtml($value);
						};
						$oTicket->Set($sAttCode, $value);
						break;

					case 'ExtKey':
						// TODO
						break;

					// Note: Default behavior includes non-supported types, but it's ok as they are not supposed to be submitted. Therefore, we don't guarantee their behavior.
					default:
						$oTicket->Set($sAttCode, $value);
						break;
				}
			}

			// Check if object seems consistent before trying to create it to avoid incrementing class ID counter with aborted objects
			list($bCheckResult, $aCheckIssues) = $oTicket->CheckToWrite();
			if ($bCheckResult) {
				$oTicket->DBInsert();
			} else {
				$oTicket = null;
				IssueLog::Error('Ticket could not be created, blocked by CheckToWrite controls', ConfigHelper::GetLogChannel(), [
					'ticket_class' => $sTicketClass,
					'check_issues' => $aCheckIssues,
				]);
			}
		} catch (Exception $oException) {
			$oTicket = null;
			IssueLog::Error('Ticket could not be created, exception occurred', ConfigHelper::GetLogChannel(), [
				'ticket_class' => $sTicketClass,
				'exception_message' => $oException->getMessage(),
			]);
		}

		return $oTicket;
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
		$sListItemPrefix = constant('static::COMPONENT_ID_VIEW_'.strtoupper($sType).'_TICKET_PREFIX');

		$aCanvas = [
			"content" => [
				"components" => [
					[
						"type" => "text",
						"style" => "header",
						"text" => Dict::S('combodo-intercom-integration:SyncApp:List'.ucfirst($sType).'TicketsCanvas:Title'),
					],
					ComponentFactory::MakeExtraSmallSpacer(),
					ComponentFactory::MakeObjectsList($oSet, $sListItemPrefix),
					ComponentFactory::MakeMediumSpacer(),
					ComponentFactory::MakeBackButton('home'),
				],
			],
		];

		return $aCanvas;
	}

	/**
	 * @param string $sReferrerListType List type {@see static::ENUM_TICKETS_LIST_TYPE_LINKED}, {@see static::ENUM_TICKETS_LIST_TYPE_ONGOING}
	 *
	 * @return array Canvas Kit Canvas for a read-only view of the ticket {@link https://developers.intercom.com/canvas-kit-reference/reference/canvas}
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

				case $oAttDef instanceof AttributeExternalKey:
					// Get friendlyname label instead of the raw value (ID)
					$sAttValueLabel = $oAttDef->GetValueLabel($oTicket->Get($sAttCode.'_friendlyname'));
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
			ComponentFactory::MakeDivider(),
		];

		// - Buttons
		$aButtonsComponents = [
			ComponentFactory::MakeMediumSpacer(),
		];
		// - Link to ticket
		$aButtonsComponents[] = ComponentFactory::MakeSubmitButton(
			static::COMPONENT_ID_LINK_TICKET_PREFIX."::{$sTicketClass}::{$oTicket->GetKey()}",
			Dict::S('combodo-intercom-integration:SyncApp:ViewTicketCanvas:LinkTicket'),
			$bTicketAlreadyLinkedToThisConversation
		);
		// - Open in iTop backoffice button
		if (ConfigHelper::GetModuleSetting('sync_app.view_ticket.show_open_in_backoffice_button')) {
			$aButtonsComponents[] = ComponentFactory::MakeUrlButton(
				'open-ticket-in-itop',
				Dict::S('combodo-intercom-integration:SyncApp:ViewTicketCanvas:OpeniTopBackoffice'),
				ApplicationContext::MakeObjectUrl($sTicketClass, $oTicket->GetKey())
			);
		}
		// - Back button
		$aButtonsComponents[] = ComponentFactory::MakeBackButton('list-'.$sReferrerListType.'-tickets');

		// Prepare canvas
		$aCanvas = [
			"content" => [
				"components" => array_merge($aHeaderComponents, $aAttComponents, $aButtonsComponents),
			],
		];

		return $aCanvas;
	}

	/**
	 * @param string $sTicketClass Valid class of the DM for which the form wil be displayed
	 *
	 * @return array Canvas Kit Canvas for a ticket creation form {@link https://developers.intercom.com/canvas-kit-reference/reference/canvas}
	 */
	protected function PrepareCreateTicketCanvas($sTicketClass)
	{
		$aTicketAttCodesMapping = ConfigHelper::GetModuleSetting('sync_app.ticket.attributes_mapping');
		/** @var array<string> $aTicketConfiguredAttCodes */
		$aTicketConfiguredAttCodes = ConfigHelper::GetModuleSetting('sync_app.create_ticket.form_attributes');
		$sTicketDefaultState = MetaModel::GetDefaultState($sTicketClass);

		// Mock ticket to apply form values and trigger dependant fields
		$aMockValues = array_merge(
			is_null($this->aData['input_values']) ? [] : $this->aData['input_values'],
			['org_id' => 3, 'caller_id' => 6]
		);
		$oMockTicket = MetaModel::NewObject($sTicketClass, $aMockValues);

		$aAttComponents = [];
		// Retrieve ticket attributes to add to the form: either mandatory or requested via the configuration (read-only attributes are skipped)
		$aTicketAllAttCodes = MetaModel::FlattenZList(MetaModel::GetZListItems($sTicketClass, 'details'));
		foreach ($aTicketAllAttCodes as $sAttCode) {
			$oAttDef = MetaModel::GetAttributeDef($sTicketClass, $sAttCode);
			$iFlags = MetaModel::GetInitialStateAttributeFlags($sTicketClass, $sTicketDefaultState, $sAttCode);

			// Skip non-applicable attribute types
			if ((false === $oAttDef->IsWritable()) || $oAttDef->IsMagic() || $oAttDef->IsExternalField() || $oAttDef->IsLinkSet()) {
				IssueLog::Debug('Attribute skipped from creation form as it is not applicable', ConfigHelper::GetLogChannel(), [
					'ticket_class' => $sTicketClass,
					'attcode' => $sAttCode,
				]);
				continue;
			}

			// Value is mandatory
			$bMandatory = ((!$oAttDef->IsNullAllowed()) || ($iFlags & OPT_ATT_MANDATORY));

			// Skip attributes not explicitly requested unless they must be filled
			if (!in_array($sAttCode, $aTicketConfiguredAttCodes)) {
				// Not mandatory, skip it
				if (false === $bMandatory) {
					continue;
				}
				// Mandatory but already has a value, skip it
				if (strlen($oMockTicket->Get($sAttCode)) > 0) {
					continue;
				}
			}

			// Prepare label
			$sLabel = MetaModel::GetLabel($sTicketClass, $sAttCode);
			if ($bMandatory) {
				$sLabel .= ' *';
			}

			// Prepare default value
			$sDefaultValue = isset($aMockValues[$sAttCode]) && (strlen($aMockValues[$sAttCode]) > 0) ? $aMockValues[$sAttCode] : $oAttDef->GetDefaultValue($oMockTicket);

			$aAttributeComponent = [];
			switch ($oAttDef->GetEditClass()) {
				case 'String':
				case 'Integer':
					$aAllowedValues = $oAttDef->GetAllowedValues(['this' => $oMockTicket] /* No args as the ticket is being created */);
					// Simple "string" attributes
					if (is_null($aAllowedValues)) {
						$aAttributeComponent = ComponentFactory::MakeStringField($sAttCode, $sLabel, $sDefaultValue, null);
					}
					// Enums and such
					else {
						$aAttributeComponent = ComponentFactory::MakeEnumValuesDropdown($sAttCode, $aAllowedValues, $sLabel, $sDefaultValue, true);
					}
					break;

				case 'Text':
				case 'HTML':
					// TODO: Handle HTML properly (in and out)
					$aAttributeComponent = ComponentFactory::MakeTextareaField($sAttCode, $sLabel, $sDefaultValue, null);
					break;

				case 'ExtKey':
					// Note: Decode HTML entities as AttributeDefinition::GetAllowedValues() gives them encoded 😥
					$aAllowedValues = array_map(function($sValue){
						return utils::HtmlEntityDecode($sValue);
					}, $oAttDef->GetAllowedValues(['this' => $oMockTicket]));

					$aAttributeComponent = ComponentFactory::MakeEnumValuesDropdown($sAttCode, $aAllowedValues, $sLabel, $sDefaultValue, true);
					break;

				// No date(time) input in the Intercom framework. We can imagine using a regular text input with the expected format in the placeholder but it's not great.
				case 'Date':
				case 'DateTime':
				// Default = Not supported yet (Document, Image, Duration, CaseLog, CustomFields, LinkedSet, Dashboard -""-, Friendlyname -""-, ExtField...)
				default:
					continue 2;
			}

			if (empty($aAttributeComponent)) {
				$sAttDefClass = get_class($oAttDef);
				$aAttributeComponent = [
					"type" => "text",
					"id" => $sAttCode,
					"style" => "error",
					"text" => Dict::S('Core:'.$sAttDefClass)."  attribute ($sAttCode) not supported by the Intercom API",
				];
			}

			$aAttComponents[] = $aAttributeComponent;
		}

		// Header components
		$aHeaderComponents = [
			[
				"type" => "text",
				"style" => "header",
				"text" => Dict::S('combodo-intercom-integration:SyncApp:CreateTicketCanvas:Title'),
			],
			[
				"type" => "text",
				"style" => "muted",
				"text" => Dict::S('combodo-intercom-integration:SyncApp:CreateTicketCanvas:Subtitle'),
			],
			ComponentFactory::MakeDivider(),
		];

		// Button components
		$aButtonsComponents = [
			ComponentFactory::MakeMediumSpacer(),
			ComponentFactory::MakeSubmitButton('create-ticket', Dict::S('combodo-intercom-integration:SyncApp:CreateButton:Title')),
			ComponentFactory::MakeBackButton('home'),
		];

		// Prepare canvas
		$aCanvas = [
			"content" => [
				"components" => array_merge($aHeaderComponents, $aAttComponents, $aButtonsComponents),
			],
		];

		return $aCanvas;
	}

	/**
	 * @param \DBObject $oTicket Ticket that has just been created
	 *
	 * @return array Canvas Kit Canvas for a confirmation message {@link https://developers.intercom.com/canvas-kit-reference/reference/canvas}
	 * @throws \CoreException
	 */
	protected function PrepareTicketCreationConfirmationCanvas(DBObject $oTicket)
	{
		$aComponents = AlertComponentsFactory::MakeSuccessAlertComponents(
			Dict::S('combodo-intercom-integration:SyncApp:CreateTicketCanvas:Success:Title'),
			Dict::Format('combodo-intercom-integration:SyncApp:CreateTicketCanvas:Success:Description', $oTicket->GetRawName())
		);

		// Prepare canvas
		$aCanvas = [
			"content" => [
				"components" => $aComponents,
			],
		];

		return $aCanvas;
	}
}
