<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Gruppen', 
	],
	'panels' => [
		'addGroup' => 'Gruppe hinzufügen', 
		'listGroup' => 'Gruppenliste', 
		'exceptionsPerms' => 'Berechtigungsausnahmen'
	], 
	'options' => [
		'first' => 'Erste Option', 
		'second' => 'Zweite Option', 
		'thirst' => 'Dritte Option', 
	], 
	'labels' => [
		'id' => 'ID', 
		'groupName' => 'Gruppenname', 
		'groupDescription' => 'Gruppenbeschreibung', 
		'assignedToGroup' => 'Der GRUPPE zugewiesen', 
		'notAssignedToGroup' => 'Nicht der GRUPPE zugewiesen', 
		'admins' => 'Administrator suchen', 
		'name' => 'Gruppenname',
		'description' => 'Gruppenbeschreibung',
		'permissions' => 'Berechtigungen', 
		'query' => 'Suchfeld', 
		'groupNameBar' => 'Gruppe %s',
	], 
	'placeholders' => [
		'groupName' => 'Gruppenname eingeben...', 
		'groupDescription' => 'Gruppenbeschreibung eingeben...', 
		'admins' => 'Geben Sie die ersten drei Buchstaben ein...'
	], 
	'buttons' => [
		'delete' => 'Löschen', 
		'refreshData' => 'Daten neu laden', 
		'resetData' => 'Daten zurücksetzen', 
		'sendData' => 'Daten speichern', 
	], 
	'errors' => [
		'permission' => 'Ungültige Berechtigung.', 
		'wrongUUID' => 'Ungültiges UUID-Format.', 
		'wrongID' => 'Ungültiges ID-Format', 
	],
	'audits' => [
	    'addGroup'       => 'Gruppe %s hinzugefügt',
	    'editGroup'      => 'Gruppe %s aktualisiert',
	    'deleteGroup'    => 'Gruppe %s gelöscht',
	    'saveExceptions' => 'Ausnahme %s %s eingefügt',
	],
	'messages' => [
		'areYouSureDeleteGroup' => 'Sind Sie sicher, dass Sie die Gruppe <b>%s</b> löschen möchten?', 
		'areYouSureToReload' => 'Sind Sie sicher, dass Sie die Daten neu laden möchten?', 
		'areYouSureToResetData' => 'Sind Sie sicher, dass Sie die Daten zurücksetzen möchten?', 
		'noAdminFound' => 'Kein Administrator gefunden.', 
		'noGroupFound' => 'Keine Gruppe gefunden.', 
		'noDataChanged' => 'Es wurden keine Änderungen vorgenommen.', 
		'validationErrors' => 'Validierungsfehler.', 
		'validateToastErrors' => '%s', 
		'addError' => 'Hinzufügen der Gruppe fehlgeschlagen.', 
		'addSuccess' => 'Gruppe erfolgreich hinzugefügt.', 
		'editError' => 'Aktualisierung der Gruppe fehlgeschlagen.',
		'editSuccess' => 'Gruppe erfolgreich aktualisiert.',
		'delError' => 'Löschen der Gruppe fehlgeschlagen.', 
		'delSuccess' => 'Gruppe erfolgreich gelöscht.', 
		'saveExceptionsSuccess' => 'Ausnahme erfolgreich für %s %s hinzugefügt.', 
		'saveExceptionsError' => 'Hinzufügen der Ausnahme fehlgeschlagen.', 
		'protectedAdmin' => 'Vor Änderungen geschützt.', 
		'cannotModifyDeleted' => 'Ein gelöschter Administrator kann nicht bearbeitet werden.', 
		'hasAdminsAttached' => 'Eine Gruppe, der Administratoren zugeordnet sind, kann nicht gelöscht werden.'
	]
];
