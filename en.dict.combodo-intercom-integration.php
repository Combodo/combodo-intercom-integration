<?php
/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

// Intercom
// - Datamodel
Dict::Add('EN US', 'English', 'English', array(
	'Class:UserRequest/Attribute:origin/Value:chat' => 'chat',
	'Class:UserRequest/Attribute:intercom_ref' => 'Intercom conversation ID',
	'Class:UserRequest/Attribute:intercom_ref+' => 'ID of the conversation within the Intercom app',
));

// - Sync app: Conversation details
Dict::Add('EN US', 'English', 'English', array(
	'combodo-intercom-integration:SyncApp:HomeButton:Title' => 'Home',
	'combodo-intercom-integration:SyncApp:BackButton:Title' => 'Back',
	'combodo-intercom-integration:SyncApp:DoneButton:Title' => 'Done',
	'combodo-intercom-integration:SyncApp:HomeCanvas:CreateTicket' => 'Create a new ticket',
	'combodo-intercom-integration:SyncApp:HomeCanvas:LinkedTickets:NoTicket' => 'No ticket linked to this conversation yet',
	'combodo-intercom-integration:SyncApp:HomeCanvas:LinkedTickets:SomeTickets' => '%1$d ticket(s) linked to this conversation',
	'combodo-intercom-integration:SyncApp:HomeCanvas:Hint:Title' => 'Linked tickets',
	'combodo-intercom-integration:SyncApp:HomeCanvas:Hint:Text' => 'If you need to link a ticket to this conversation, you can either choose one of the ongoing tickets or create a new one.',
	'combodo-intercom-integration:SyncApp:HomeCanvas:OngoingTickets:NoTicket' => 'No ongoing ticket for this person',
	'combodo-intercom-integration:SyncApp:HomeCanvas:OngoingTickets:SomeTickets' => '%1$d ongoing ticket(s) for this person',
	'combodo-intercom-integration:SyncApp:ListOngoingTicketsCanvas:Title' => 'Ongoing ticket(s) for this person',
	'combodo-intercom-integration:SyncApp:ListLinkedTicketsCanvas:Title' => 'Linked ticket(s)',
	'combodo-intercom-integration:SyncApp:ViewTicketCanvas:Subtitle:LinkedToThisConversation' => 'Linked to this conversation',
	'combodo-intercom-integration:SyncApp:ViewTicketCanvas:Subtitle:LinkedToAnotherConversation' => 'Linked to conversation [#%1$s](https://app.intercom.com/a/apps/%2$s/inbox/inbox/all/conversations/%1$s)',
	'combodo-intercom-integration:SyncApp:ViewTicketCanvas:Subtitle:LinkedToNoConversation' => 'Not linked to any conversation',
	'combodo-intercom-integration:SyncApp:ViewTicketCanvas:LinkTicket' => 'Link to conversation',
	'combodo-intercom-integration:SyncApp:ViewTicketCanvas:OpeniTopBackoffice' => 'Open in '.ITOP_APPLICATION_SHORT.' backoffice',
	'combodo-intercom-integration:SyncApp:LinkTicketCanvas:Success:Title' => 'Ticket linked',
	'combodo-intercom-integration:SyncApp:LinkTicketCanvas:Success:Description' => '%1$s has been linked to this conversation',
	'combodo-intercom-integration:SyncApp:LinkTicketCanvas:Failure:Title' => 'Error',
	'combodo-intercom-integration:SyncApp:LinkTicketCanvas:Failure:Description' => 'Ticket could not be linked to this conversation due to the following error: %1$s',
));

// - Sync app: Messenger
Dict::Add('EN US', 'English', 'English', array(
	'combodo-intercom-integration:SyncApp:TicketCreatedMessage:Description' => 'A ticket has been created for from this conversation. You can track its progression with the following link: %1$s',
	'combodo-intercom-integration:SyncApp:TicketLinkedMessage:Title' => '%1$s linked a ticket',
));
