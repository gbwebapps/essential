<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Dati', 
		'showAll' => 'Lista', 
		'add' => 'Aggiungi', 
		'edit' => 'Aggiorna', 
		'show' => 'Dettaglio'
	],
	'panels' => [
		'generalData' => 'Dati generali', 
		'metaData' => 'Meta dati', 
		'permissions' => 'Permessi'
	],
	'linksBar' => [
		'index' => 'Dati', 
		'showAll' => 'Lista', 
		'add' => 'Aggiungi', 
		'edit' => 'Aggiorna', 
		'show' => 'Dettaglio'
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
		'selectAll' => 'Seleziona tutti'
	],
	'labels' => [
		'firstname' => 'Nome', 
		'lastname' => 'Cognome', 
		'email' => 'Email', 
		'phone' => 'Telefono', 
		'status' => 'Stato', 
		'active' => 'Attivo', 
		'unactive' => 'Inattivo', 
		'noImage' => 'Nessuna immagine', 
		'createdAt' => 'Aggiunto il ', 
		'updatedAt' => 'Aggiornato il ', 
		'resettedAt' => 'Reset il ',
		'suspendedAt' => 'Sospeso il ',
		'note' => 'Note aggiuntive', 
		'permissions' => 'Permessi'
	],
	'placeholders' => [
		'firstname' => 'Inserisci nome...', 
		'lastname' => 'Inserisci cognome...', 
		'email' => 'Inserisci email...', 
		'phone' => 'Inserisci telefono...', 
		'note' => 'Inserisci note aggiuntive...', 
		'searchFirstname' => 'Cerca per nome...', 
		'searchLastname' => 'Cerca per cognome...', 
		'searchEmail' => 'Cerca per email...', 
		'searchPhone' => 'Cerca per telefono...'
	], 
	'actions' => [
		'show' => 'Dettaglio', 
		'edit' => 'Aggiorna', 
		'reset' => 'Resetta password', 
		'delete' => 'Elimina',
	], 
	'buttons' => [
		'actions' => 'Azioni', 
		'resetData' => 'Resetta dati', 
		'sendData' => 'Invia dati', 
		'refreshData' => 'Ricarica dati', 
		'reload' => 'Ricarica pannello', 
		'print' => 'Stampa', 
		'export' => 'Esporta'
	],
	'messages' => [
		'areYouSureResetData' => 'Sei sicuro di voler resettare i dati?', 
		'areYouSureRefreshData' => 'Sei sicuro di voler ricaricare i dati?', 
		'areYouSureChangeStatus' => 'Sei sicuro di modificare lo stato di %s %s?', 
		'areYouSureReset' => 'Sei sicuro di resettare la password di %s %s?', 
		'areYouSureDelete' => 'Sei sicuro di eliminare  %s %s?', 
		'noRecordsFound' => 'Non sono presenti amministratori.', 
		'validationErrors' => 'Errori di validazione.', 
		'addSuccess' => 'Amministratore %s %s aggiunto con successo.', 
		'addSuccessNoEmail' => 'Amministratore %s %s aggiunto con successo, ma la mail non è stata inviata. Contattare amministratore.', 
		'addError' => 'Aggiunta amministratore non andata a buon fine.', 
		'editSuccess' => 'Amministratore %s %s aggiornato con successo.', 
		'editError' => 'Aggiornamento amministratore non andato a buon fine.', 
		'noDataChanged' => 'Non sono state effettuate modifiche.', 
		'delSuccess' => 'Amministratore %s %s eliminato con successo.',
		'delError' => 'Eliminazione amministratore non andata a buon fine.',
		'changeStatusSuccess' => 'Modifica stato %s %s effettuato con successo.',
		'changeStatusError' => 'Modifica stato amministratore non andato a buon fine.', 
		'wrongPermissionsFormat' => 'Uno o più permessi con formato non corretto.'
	]
];