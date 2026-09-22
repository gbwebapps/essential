<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Tokens',
	],
	'options' => [
		'first' => 'Erste Option', 
		'second' => 'Zweite Option', 
		'thirst' => 'Dritte Option', 
		],
	'links' => [
		'filters' => 'Suchen', 
		'resetFilters' => 'Suche bereinigen', 
		'resetSorting' => 'Sortierung zurücksetzen', 
		'reloadList' => 'Liste neu laden', 
		'export' => 'CSV exportieren', 
		],
	'labels' => [
		'id' => 'ID',
		'uuid' => 'UUID', 
		'username' => 'Benutzername', 
		'tokenCreate' => 'Startdatum', 
		'tokenExpire' => 'Ablaufdatum', 
		'tokenType' => 'Typ', 
		'operatingSystem' => 'Betriebssystem', 
		'browser' => 'Webbrowser', 
		'ipAddress' => 'IP-Adresse', 
		'dateFrom' => 'Startdatum', 
		'dateTo' => 'Enddatum',
		'createdAt' => 'Hinzugefügt am ', 
		'session' => 'Sitzung', 
		'activation' => 'Aktivierung', 
		'cookie' => 'Angemeldet bleiben'
	],
	'errors' => [
		'uuid' => 'Ungültige UUID.', 
		'id' => 'Ungültige ID.', 
	], 
	'actions' => [
		'hardDelete' => 'Löschen'
	], 
	'buttons' => [
		'actions' => 'Aktionen'
	], 
	'placeholders' => [
		'searchUsername' => 'Nach Benutzernamen suchen...', 
		'searchType' => 'Nach Typ suchen...', 
		'dateFrom' => 'Nach Startdatum suchen...', 
		'dateTo' => 'Nach Enddatum suchen...',
	], 
	'audits' => [
	    'deleteToken' => 'Token %s %s löschen',
	],
	'messages' => [
		'validationErrors' => 'Validierungsfehler.', 
		'validationToastErrors' => '%s', 
		'noTokensFound' => 'Keine Tokens vorhanden.', 
		'areYouSureHardDelete' => 'Sind Sie sicher, dass Sie das Token von <b>%s %s</b> endgültig löschen möchten?', 
		'deleteTokenSuccess' => 'Das Token von %s %s wurde erfolgreich gelöscht.', 
		'deleteTokenError' => 'Löschen des Tokens fehlgeschlagen.', 
		'protectedAdmin' => 'Vor Änderungen geschützt.', 
		'cannotModifyDeleted' => 'Ein gelöschter Administrator kann nicht bearbeitet werden.', 
	]
];
