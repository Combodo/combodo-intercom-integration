<?php
/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

// Intercom
// - Datamodel
Dict::Add('IT IT', 'Italian', 'Italiano', array(
    'Class:UserRequest/Attribute:origin/Value:chat' => 'chat',
    'Class:UserRequest/Attribute:intercom_ref' => 'ID conversazione Intercom',
    'Class:UserRequest/Attribute:intercom_ref+' => 'ID della conversazione nell\'app Intercom. Può essere utile quando i team di '.ITOP_APPLICATION_SHORT.' e Intercom devono assicurarsi di parlare dello stesso caso.',
    'Class:UserRequest/Attribute:intercom_url' => 'URL conversazione Intercom',
    'Class:UserRequest/Attribute:intercom_url+' => 'Apri la conversazione corrispondente direttamente in Intercom (richiede un account Intercom)',
    'Class:UserRequest/Attribute:intercom_sync_activated' => 'Sincronizzazione Intercom attivata?',
    'Class:UserRequest/Attribute:intercom_sync_activated+' => 'Indica se la sincronizzazione dei log da Intercom è attivata. Se impostato su \'Sì\', i nuovi messaggi sulla conversazione Intercom verranno automaticamente aggiunti qui.',
    'Class:UserRequest/Attribute:intercom_sync_activated/Value:yes' => 'Sì',
    'Class:UserRequest/Attribute:intercom_sync_activated/Value:no' => 'No',
));

// - Sync app: Ticket sync.
// Note: Don't need to translate this in other languages as we can't know the Intercom agent language
Dict::Add('IT IT', 'Italian', 'Italiano', array(
    'combodo-intercom-integration:SyncApp:SynchedTicket:LogEntry:NewMessageFromConversation' => 'Messaggio sincronizzato dalla conversazione Intercom',
    'combodo-intercom-integration:SyncApp:SynchedTicket:LogEntry:FallbackUserLogin' => '%1$s (Intercom)',
));

// - Sync app: Conversation details canvas
// Note: Don't need to translate this in other languages as we can't know the Intercom agent language
Dict::Add('IT IT', 'Italian', 'Italiano', array(
    'combodo-intercom-integration:SyncApp:HomeButton:Title' => 'Home',
    'combodo-intercom-integration:SyncApp:BackButton:Title' => 'Indietro',
    'combodo-intercom-integration:SyncApp:DoneButton:Title' => 'Fatto',
    'combodo-intercom-integration:SyncApp:SubmitButton:Title' => 'Invia',
    'combodo-intercom-integration:SyncApp:CreateButton:Title' => 'Crea',
    'combodo-intercom-integration:SyncApp:PauseLinkedTicketsSync:Title' => 'Metti in pausa la sincronizzazione dei ticket collegati',
    'combodo-intercom-integration:SyncApp:ResumeLinkedTicketsSync:Title' => 'Riprendi la sincronizzazione dei ticket collegati',
    'combodo-intercom-integration:SyncApp:HomeCanvas:CreateTicket' => 'Crea un nuovo ticket',
    'combodo-intercom-integration:SyncApp:HomeCanvas:LinkedTickets:NoTicket' => 'Nessun ticket collegato a questa conversazione',
    'combodo-intercom-integration:SyncApp:HomeCanvas:LinkedTickets:SomeTickets' => '%1$d ticket collegati a questa conversazione',
    'combodo-intercom-integration:SyncApp:HomeCanvas:LinkedTicketsExplanation:Title' => 'Ticket collegati',
    'combodo-intercom-integration:SyncApp:HomeCanvas:LinkedTicketsExplanation:Text:NoTicketLinked' => 'Per collegare un ticket a questa conversazione, scegli uno dei ticket in corso o creane uno nuovo.',
    'combodo-intercom-integration:SyncApp:HomeCanvas:LinkedTicketsExplanation:Text:SyncActive' => 'La sincronizzazione è attiva, i nuovi messaggi (sia risposte che note) verranno aggiunti ai ticket collegati.',
    'combodo-intercom-integration:SyncApp:HomeCanvas:LinkedTicketsExplanation:Text:SyncInactive' => 'La sincronizzazione è in pausa, i nuovi messaggi (sia risposte che note) NON verranno aggiunti ai ticket collegati.',
    'combodo-intercom-integration:SyncApp:HomeCanvas:LinkedTicketsExplanation:Text:SyncPartial' => 'Solo %1$s ticket sincronizzati. Clicca il pulsante \'Riprendi\' per impostare tutti i ticket collegati come sincronizzati.',
    'combodo-intercom-integration:SyncApp:HomeCanvas:OngoingTickets:NoTicket' => 'Nessun ticket in corso per questa persona',
    'combodo-intercom-integration:SyncApp:HomeCanvas:OngoingTickets:SomeTickets' => '%1$d ticket in corso per questa persona',
    'combodo-intercom-integration:SyncApp:ListOngoingTicketsCanvas:Title' => 'Ticket in corso per questa persona',
    'combodo-intercom-integration:SyncApp:ListLinkedTicketsCanvas:Title' => 'Ticket collegati',
    'combodo-intercom-integration:SyncApp:ViewTicketCanvas:Subtitle:LinkedToThisConversation' => 'Collegato a questa conversazione',
    'combodo-intercom-integration:SyncApp:ViewTicketCanvas:Subtitle:LinkedToAnotherConversation' => 'Collegato alla conversazione [#%1$s](%2$s)',
    'combodo-intercom-integration:SyncApp:ViewTicketCanvas:Subtitle:LinkedToNoConversation' => 'Non collegato a nessuna conversazione',
    'combodo-intercom-integration:SyncApp:ViewTicketCanvas:LinkTicket' => 'Collega alla conversazione',
    'combodo-intercom-integration:SyncApp:ViewTicketCanvas:OpeniTopBackoffice' => 'Apri nel backoffice di '.ITOP_APPLICATION_SHORT,
    'combodo-intercom-integration:SyncApp:LinkTicketCanvas:Success:Title' => 'Ticket collegato',
    'combodo-intercom-integration:SyncApp:LinkTicketCanvas:Success:Description' => '%1$s è stato collegato a questa conversazione',
    'combodo-intercom-integration:SyncApp:LinkTicketCanvas:Failure:Title' => 'Errore',
    'combodo-intercom-integration:SyncApp:LinkTicketCanvas:Failure:Description' => 'Impossibile collegare il ticket a questa conversazione a causa del seguente errore: %1$s',
    'combodo-intercom-integration:SyncApp:CreateTicketCanvas:Title' => 'Crea un nuovo ticket',
    'combodo-intercom-integration:SyncApp:CreateTicketCanvas:Subtitle' => 'Gli attributi obbligatori sono contrassegnati con *',
    'combodo-intercom-integration:SyncApp:CreateTicketCanvas:Success:Title' => 'Ticket creato',
    'combodo-intercom-integration:SyncApp:CreateTicketCanvas:Success:Description' => '%1$s è stato creato e collegato a questa conversazione',
    'combodo-intercom-integration:SyncApp:CreateTicketCanvas:Failure:Title' => 'Errore',
    'combodo-intercom-integration:SyncApp:CreateTicketCanvas:Failure:Description' => 'Impossibile creare il ticket, verifica che tutti i campi obbligatori siano compilati. Se il problema persiste, contatta l\'amministratore di '.ITOP_APPLICATION_SHORT,
));

// - Sync app: Messenger canvas
// Note: Don't need to translate this in other languages as we can't know the Intercom agent language
Dict::Add('IT IT', 'Italian', 'Italiano', array(
    'combodo-intercom-integration:SyncApp:TicketCreatedMessage:Title' => 'È stato creato un ticket da questa conversazione. Puoi seguirne l\'avanzamento con il seguente link',
    'combodo-intercom-integration:SyncApp:TicketLinkedMessage:Title' => '%1$s ha collegato un ticket',
));
