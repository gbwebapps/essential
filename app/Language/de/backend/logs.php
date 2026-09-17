<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Zugriffe', 
	], 
	'links' => [
		'filters' => 'Suchen', 
		'resetFilters' => 'Suche bereinigen', 
		'resetSorting' => 'Sortierung zurücksetzen', 
		'reloadList' => 'Liste neu laden', 
	], 
	'errors' => [
		'uuid' => 'Ungültige UUID.', 
		'id' => 'Ungültige ID.', 
	], 
	'actions' => [
		'hardDelete' => 'Abmelden'
	],
	'labels' => [
		'username' => 'Benutzername', 
		'typeToken' => 'Typ', 
		'createdAt' => 'Hinzugefügt am ', 
		'operatingSystem' => 'Betriebssystem', 
		'browser' => 'Webbrowser', 
		'ipAddress' => 'IP-Adresse', 
		'dateFrom' => 'Startdatum', 
		'dateTo' => 'Enddatum', 
		'login' => 'Anmeldung', 
		'logout' => 'Abmeldung', 
		'logoutReason' => 'Abmeldemodus', 
		'manual' => 'Manuell', 
		'timeout' => 'Abgelaufen', 
		'deleted' => 'Gelöscht', 
		'banned' => 'Gesperrt', 
		'undefined' => 'Undefiniert', 
		'pending' => 'In Bearbeitung...', 
		'duration' => 'Dauer'
	],
	'placeholders' => [
		'searchUsername' => 'Nach Benutzernamen suchen...', 
		'dateFrom' => 'Nach Startdatum suchen...', 
		'dateTo' => 'Nach Enddatum suchen...',
		'searchLogoutReason' => 'Nach Abmeldegrund suchen...'
	], 
	'messages' => [
		'noLogsFound' => 'Keine Protokolle gefunden.', 
		'areYouSureHardDelete' => 'Sind Sie sicher, dass Sie <b>%s %s</b> abmelden möchten?', 
		'validationErrors' => 'Validierungsfehler.', 
		'validationToastErrors' => '%s', 
		'deleteTokenSuccess' => '%s %s wurde erfolgreich abgemeldet.', 
		'deleteTokenError' => 'Abmeldung fehlgeschlagen.', 
		'protectedAdmin' => 'Vor Änderungen geschützt.', 
		'cannotModifyDeleted' => 'Ein gelöschter Administrator kann nicht bearbeitet werden.',
	]
];
