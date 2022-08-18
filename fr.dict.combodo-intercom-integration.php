<?php
/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

// Intercom
// - Datamodel
Dict::Add('FR FR', 'French', 'Français', array(
	'Class:UserRequest/Attribute:origin/Value:chat' => 'chat',
	'Class:UserRequest/Attribute:intercom_ref' => 'Intercom conversation ID',
	'Class:UserRequest/Attribute:intercom_ref+' => 'Identifiant de la conversation das l\'application Intercom',
));

// - Sync app: Ticket sync.
// Note: Don't need to translate this in other languages as we can't know the Intercom agent language
Dict::Add('FR FR', 'French', 'Français', array(
	'combodo-intercom-integration:SyncApp:SynchedTicket:LogEntry:FallbackUserLogin' => '%1$s (Intercom)',
));
