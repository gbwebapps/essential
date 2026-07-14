<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Impostazioni', 
	],
	'panels' => [
		'authSetting' => 'Sicurezza', 
		'uploadSetting' => 'Upload'
	], 
	'linksBar' => [
		'tools' => 'Strumenti', 
	], 
	'labels' => [
		/* Etichette delle Sezioni */
		'security' => 'Sicurezza e tentativi di accesso',
		'twoFactor' => 'Autenticazione 2FA',
		'sessions' => 'Sessioni e persistenza',

		/* Campi Auth */
		'attempts' => 'Controllo tentativi falliti',
		'attemptsLimit' => 'Limite tentativi di accesso',
		'attemptsInterval' => 'Intervallo di monitoraggio tentativi',
		'twoFactor' => 'Stato autenticazione 2FA',
		'twoFactorLimit' => 'Limite tentativi OTP falliti',
		'twoFactorTime' => 'Finestra calcolo brute-force OTP',
		'twoFactorIssuer' => 'Nome applicazione emittente',
		'twoFactorDigits' => 'Cifre codice OTP',
		'twoFactorWindow' => 'Tolleranza disallineamento orario',
		'twoFactorEmailExpiry' => 'Scadenza codice OTP via email',
		'twoFactorEmailFrom' => 'Email mittente invio OTP',
		'twoFactorMethods' => 'Metodi 2FA supportati',

		/* Metodi Specifici */
		'none' => 'Nessuno',
		'email' => 'Codice via e-mail',
		'totp' => 'App di autenticazione',

		/* Tempi Sessione */
		'sessionTime' => 'Durata inattività sessione backend',
		'rememberMeTime' => 'Durata persistenza "Remember Me"',
		'activationTime' => 'Validità token attivazione',

		/* Campi Upload */
		'renameImages' => 'Rinomina immagini in upload',
		'overwriteImages' => 'Sovrascrivi immagini esistenti',
		'cropCenter' => 'Ritaglio centrale',
		'maxImageX' => 'Larghezza immagine', 
		'maxImageY' => 'Altezza immagine', 
		'resizeMediumX' => 'Larghezza anteprima media',
		'resizeMediumY' => 'Altezza anteprima media',
		'resizeSmallX' => 'Larghezza anteprima piccola',
		'resizeSmallY' => 'Altezza anteprima piccola',
		'maxFileSize' => 'Peso massimo file', 
		'allowedExtensions' => 'Estensioni', 

		'uploadImageRules' => 'Regole caricamento immagini', 
		'dimensionsOriginal' => 'Dimensioni immagini',
		'dimensionsMedium' => 'Dimensioni anteprime medie',
		'dimensionsSmall' => 'Dimensioni anteprime piccole',
		'uploadWeightRules' => 'Peso ed estensioni',

		'disabled' => 'Disabilitato',
		'enabled' => 'Abilitato',
	], 
	'placeholders' => [
		'attemptsLimit' => 'Inserisci limite tentativi accesso...',
		'attemptsInterval' => 'Inserisci intervallo di monitoraggio tentativi...',
		'twoFactorLimit' => 'Inserisci limite tentativi OTP falliti...',
		'twoFactorTime' => 'Inserisci finestra calcolo brute-force OTP...',
		'twoFactorIssuer' => 'Inserisci nome applicazione emittente...',
		'twoFactorDigits' => 'Inserisci cifre codice OTP...',
		'twoFactorWindow' => 'Inserisci tolleranza disallineamento orario...',
		'twoFactorEmailExpiry' => 'Inserisci scadenza codice OTP via email...',
		'twoFactorEmailFrom' => 'Inserisci email mittente invio OTP...',
		'sessionTime' => 'Inserisci durata inattività sessione backend...',
		'rememberMeTime' => 'Inserisci durata persistenza "Remember Me"...',
		'activationTime' => 'Inserisci validità token attivazione...',
		'maxImageX' => 'Inserisci larghezza immagine...', 
		'maxImageY' => 'Inserisci altezza immagine...', 
		'resizeMediumX' => 'Inserisci larghezza anteprima media...',
		'resizeMediumY' => 'Inserisci altezza anteprima media...',
		'resizeSmallX' => 'Inserisci larghezza anteprima piccola...',
		'resizeSmallY' => 'Inserisci altezza anteprima piccola...',
		'maxFileSize' => 'Inserisci dimensione massima...',
	], 
	'buttons' => [
		'refreshData' => 'Refresh dati', 
		'sendData' => 'Invia dati', 
		'restoreData' => 'Ripristina dati'
	], 
	'options' => [
		'first' => 'Prima opzione', 
		'second' => 'Seconda opzione', 
		'thirst' => 'Terza opzione', 
	], 
	'messages' => [
		'validationErrors' => 'Errori di validazione.', 
		'saveSuccess' => 'Impostazioni aggiornate con successo.', 
		'deleteSuccess' => 'Impostazioni eliminate con successo dal database.', 
		'noDataChanged' => 'Non sono state effettuate modifiche.', 
	], 
];