<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Tokens',
	],
	'options' => [
		'first' => 'Prima opzione', 
		'second' => 'Seconda opzione', 
		'thirst' => 'Terza opzione', 
		],
	'links' => [
		'filters' => 'Cerca', 
		'resetFilters' => 'Pulisci ricerca', 
		'resetSorting' => 'Resetta ordinamento', 
		'reloadList' => 'Ricarica lista', 
		'export' => 'Esporta CSV', 
		],
	'labels' => [
		'id' => 'ID',
		'uuid' => 'UUID', 
		'username' => 'Nome utente', 
		'tokenCreate' => 'Data inizio', 
		'tokenExpire' => 'Data scadenza', 
		'tokenType' => 'Tipo', 
		'operatingSystem' => 'Sistema operativo', 
		'browser' => 'Browser web', 
		'ipAddress' => 'Indirizzo IP', 
		'dateFrom' => 'Data inizio', 
		'dateTo' => 'Data fine',
		'createdAt' => 'Aggiunto il ', 
		'session' => 'Sessione', 
		'activation' => 'Attivazione', 
		'cookie' => 'Ricordami'
	],
	'errors' => [
		'uuid' => 'UUID non conforme.', 
		'id' => 'ID non conforme.', 
	], 
	'actions' => [
		'hardDelete' => 'Elimina'
	], 
	'buttons' => [
		'actions' => 'Azioni'
	], 
	'placeholders' => [
		'searchUsername' => 'Cerca per nome utente...', 
		'searchType' => 'Cerca per tipo...', 
		'dateFrom' => 'Cerca per data inizio...', 
		'dateTo' => 'Cerca per data fine...',
	], 
	'audits' => [
		'deleteToken' => 'Elimina token %s %s'
	], 
	'messages' => [
		'validationErrors' => 'Errori di validazione.', 
		'validationToastErrors' => '%s', 
		'noTokensFound' => 'Non sono presenti tokens.', 
		'areYouSureHardDelete' => 'Sei sicuro di eliminare definitivamente il token di  <b>%s %s</b>?', 
		'deleteTokenSuccess' => 'Il token di %s %s è stato eliminato con successo.', 
		'deleteTokenError' => 'Eliminazione del token non è andata a buon fine', 
		'protectedAdmin' => 'Protetto da modifiche.', 
		'cannotModifyDeleted' => 'Non è possibile modificare un admin eliminato.', 
	]
];