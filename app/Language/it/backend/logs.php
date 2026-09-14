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
		'hardDelete' => 'Disconnetti'
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
		'duration' => 'Durata'
	],
	'placeholders' => [
		'searchUsername' => 'Cerca per nome utente...', 
		'dateFrom' => 'Cerca per data inizio...', 
		'dateTo' => 'Cerca per data fine...',
		'searchLogoutReason' => 'Cerca per motivo di disconnessione...'
	], 
	'messages' => [
		'noLogsFound' => 'Nessun log trovato.', 
		'areYouSureHardDelete' => 'Sei sicuro di voler disconnettere %s %s?', 
		'validationErrors' => 'Errori di validazione.', 
		'validationToastErrors' => '%s', 
		'deleteTokenSuccess' => '%s %s è stato disconnesso con successo.', 
		'deleteTokenError' => 'Disconnessione fallita.', 
		'protectedAdmin' => 'Protetto da modifiche.', 
		'cannotModifyDeleted' => 'Non è possibile modificare un admin eliminato.',
	]
];