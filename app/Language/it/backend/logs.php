<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Accessi', 
	], 
	'links' => [
		'filters' => 'Cerca', 
		'resetFilters' => 'Pulisci ricerca', 
		'resetSorting' => 'Resetta ordinamento', 
		'reloadList' => 'Ricarica lista', 
	], 
	'errors' => [
		'uuid' => 'UUID non conforme.', 
		'id' => 'ID non conforme.', 
	], 
	'actions' => [
		'disconnectSession' => 'Disconnetti'
	],
	'labels' => [
		'username' => 'Nome utente', 
		'typeToken' => 'Tipo', 
		'createdAt' => 'Aggiunto il ', 
		'operatingSystem' => 'Sistema Operativo', 
		'browser' => 'Browser Web', 
		'ipAddress' => 'Indirizzo IP', 
		'dateFrom' => 'Data inizio', 
		'dateTo' => 'Data fine', 
		'login' => 'Accesso', 
		'logout' => 'Disconnessione', 
		'logoutReason' => 'Modalità disconnessione', 
		'manual' => 'Manuale', 
		'timeout' => 'Scaduto', 
		'deleted' => 'Eliminato', 
		'banned' => 'Bannato', 
		'undefined' => 'Indefinito', 
		'pending' => 'In corso...', 
		'duration' => 'Durata',
		'mobile' => 'Dispositivo mobile',
		'robot' => 'Robot',
		'filtersAndOptions' => 'Opzioni e filtri',
		'showSystemDetails' => 'Mostra dettagli di sistema'
	],
	'placeholders' => [
		'searchUsername' => 'Cerca per nome utente...', 
		'dateFrom' => 'Cerca per data inizio...', 
		'dateTo' => 'Cerca per data fine...',
		'searchLogoutReason' => 'Cerca per motivo di disconnessione...'
	], 
	'audits' => [
		'disconnectSession' => 'Interruzione forzata sessione in corso', 
	], 
	'messages' => [
		'noLogsFound' => 'Nessun log trovato.', 
		'areYouSureDisconnectSession' => 'Sei sicuro di voler disconnettere <b>%s %s?</b>', 
		'validationErrors' => 'Errori di validazione.', 
		'validationToastErrors' => '%s', 
		'disconnectSessionSuccess' => '%s %s è stato disconnesso con successo.', 
		'disconnectSessionError' => 'Disconnessione fallita.', 
		'protectedAdmin' => 'Protetto da modifiche.', 
		'cannotModifyDeleted' => 'Non è possibile modificare un admin eliminato.',
	]
];
