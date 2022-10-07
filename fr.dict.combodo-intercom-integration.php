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
	'Class:UserRequest/Attribute:intercom_ref+' => 'Identifiant de la conversation das l\'application Intercom. Peut être utile quand les équipes '.ITOP_APPLICATION_SHORT.' et Intercom doivent s\'assurer qu\'elles parlent bien de la même chose.',
));
