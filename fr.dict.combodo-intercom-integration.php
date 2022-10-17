<?php
/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

// Intercom
// - Datamodel
Dict::Add('FR FR', 'French', 'Français', array(
	'Class:UserRequest/Attribute:origin/Value:chat' => 'chat',
	'Class:UserRequest/Attribute:intercom_ref' => 'ID conv. Intercom',
	'Class:UserRequest/Attribute:intercom_ref+' => 'Identifiant de la conversation das l\'application Intercom. Peut être utile quand les équipes '.ITOP_APPLICATION_SHORT.' et Intercom doivent s\'assurer qu\'elles parlent bien de la même chose.',
	'Class:UserRequest/Attribute:intercom_url' => 'URL conv. Intercom',
	'Class:UserRequest/Attribute:intercom_url+' => 'Accéder à la conversation correspondante directement dans Intercom (nécessite un compte Intercom)',
	'Class:UserRequest/Attribute:intercom_sync_activated' => 'Synchro. Intercom activée ?',
	'Class:UserRequest/Attribute:intercom_sync_activated+' => 'Etat de la synchronisation des journaux depuis Intercom. Si \'Oui\', les nouveaux messages de la conversation Intercom seront automatiquement ajoutés ici.',
	'Class:UserRequest/Attribute:intercom_sync_activated/Value:yes' => 'Oui',
	'Class:UserRequest/Attribute:intercom_sync_activated/Value:no' => 'Non',
));
