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
		'filters' => 'Filtri', 
		'resetFilters' => 'Resetta filtri', 
		'resetSorting' => 'Resetta ordinamento', 
		'reloadList' => 'Ricarica lista', 
		],
	'labels' => [
		'id' => 'ID',
		'uuid' => 'UUID', 
		'username' => 'Username', 
		'tokenCreate' => 'Data inizio', 
		'tokenExpire' => 'Data scadenza', 
		'tokenType' => 'Tipo', 
		'operatingSystem' => 'Sistema operativo', 
		'browser' => 'Browser web', 
		'ipAddress' => 'Indirizzo IP', 
		'dateFrom' => 'Data inizio partenza', 
		'dateTo' => 'Data inizio arrivo',
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
		'searchUsername' => 'Cerca per username...', 
		'searchType' => 'Cerca per tipo...', 
		'dateFrom' => 'Cerca per data inizio di partenza...', 
		'dateTo' => 'Cerca per data inizio di arrivo...',
	], 
	'messages' => [
		'validationErrors' => 'Errori di validazione.', 
		'validationToastErrors' => '%s', 
		'noTokensFound' => 'Non sono presenti tokens.', 
		'areYouSureHardDelete' => 'Sei sicuro di eliminare definitivamente il token di  %s %s?', 
		'deleteTokenSuccess' => 'Il token di %s %s è stato eliminato con successo.', 
		'deleteTokenError' => 'Eliminazione del token non è andata a buon fine', 
		'protectedAdmin' => 'Protetto da modifiche.', 
		'cannotModifyDeleted' => 'Non è possibile modificare un admin eliminato.', 
	]
];