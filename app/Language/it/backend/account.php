<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Profilo', 
		'general' => 'Dati generali', 
		'edit' => 'Aggiorna', 
		'permissions' => 'Permessi', 
		'images' => 'Immagini', 
		'tokens' => 'Accessi', 
		'resetPassword' => 'Reset password', 
		'security' => 'Sicurezza'
	],
	'leftMenu' => [
		'general' => 'Dati generali', 
		'edit' => 'Aggiorna', 
		'permissions' => 'Permessi', 
		'images' => 'Immagini', 
		'tokens' => 'Accessi', 
		'resetPassword' => 'Reset password', 
		'security' => 'Sicurezza'
	],
	'labels' => [
		'firstname' => 'Nome', 
		'lastname' => 'Cognome', 
		'email' => 'Email', 
		'phone' => 'Telefono', 
		'assigned' => 'Assegnato', 
		'notAssigned' => 'Non assegnato', 
		'note' => 'Note aggiuntive', 
		'operatingSystem' => 'Sistema operativo', 
		'browser' => 'Browser web', 
		'ipAddress' => 'Indirizzo IP', 
		'createdToken' => 'Data inizio', 
		'expiredToken' => 'Data scadenza', 
		'typeToken' => 'Tipo', 
		'session' => 'Sessione',
        'activation' => 'Attivazione',
	], 
	'placeholders' => [
		'firstname' => 'Inserisci nome...', 
		'lastname' => 'Inserisci cognome...', 
		'email' => 'Inserisci email...', 
		'phone' => 'Inserisci telefono...', 
		'note' => 'Inserisci note aggiuntive...', 
	], 
	'buttons' => [
		'sendData' => 'Invia dati', 
		'refreshData' => 'Ricarica dati', 
		'reload' => 'Ricarica pannello', 
		'resetPassword' => 'Procedi al reset della password',
	], 
	'errors' => [
		'id' => 'ID non conforme.', 
	], 
	'messages' => [
		'noDataChanged' => 'Non sono state effettuate modifiche.', 
		'editSuccess' => 'Amministratore %s %s aggiornato con successo.', 
		'editError' => 'Aggiornamento amministratore non andato a buon fine.', 
		'resetPasswordSuccessNoEmail' => 'Password amministratore %s %s resettata con successo, ma la mail non è stata inviata. Contattare amministratore.', 
		'resetPasswordSuccess' => 'Password amministratore %s %s resettata con successo.', 
		'resetPasswordError' => 'Reset password amministratore non andato a buon fine.', 
		'validateToastErrors' => '%s',
		'validationErrors' => 'Errori di validazione.', 
		'areYouSureRefreshData' => 'Sei sicuro di voler ricaricare i dati?', 
		'areYouSureDeleteToken' => 'Sei sicuro di eliminare il token di  %s %s?', 
		'areYouSureStartingReset' => 'Sei sicuro di voler iniziare il processo di reset password?', 
		'noTokensFound' => 'Non sono presenti accessi.', 
		'deleteTokenSuccess' => 'Il token di %s %s è stato eliminato con successo.', 
		'deleteTokenError' => 'Eliminazione token non andata a buon fine', 
		'expiringDate' => '<div class="pb-3">Hai richiesto il reset della password.</div><div class="pb-3">Verifica la tua email e completa l\'operazione.</div>Hai tempo fino al %s</div>',
	]
];