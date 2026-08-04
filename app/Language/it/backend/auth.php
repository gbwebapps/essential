<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Benvenuto', 
		'login' => 'Accedi', 
		'resetPassword' => 'Resetta password', 
		'setPassword' => 'Imposta password', 
		'verify' => 'Verifica OTP'
	],
	'labels' => [
		'email' => 'Email', 
		'username' => 'Username', 
		'password' => 'Password', 
		'rememberMe' => 'Ricordami', 
		'newPassword' => 'Inserisci password', 
		'confirmNewPassword' => 'Conferma password', 
		'code' => 'Codice OTP'
	], 
	'placeholders' => [
		'email' => 'Inserisci qui indirizzo email...', 
		'username' => 'Inserisci qui username...', 
		'password' => 'Inserisci qui password...', 
		'newPassword' => 'Inserisci qui password...', 
		'confirmNewPassword' => 'Conferma password', 
		'code' => 'Inserisci qui codice OTP...', 
	], 
	'buttons' => [
		'login' => 'Accedi', 
		'resetPassword' => 'Resetta password', 
		'setPassword' => 'Imposta password', 
		'verify' => 'Verifica OTP'

	], 
	'links' => [
		'login' => 'Accedi', 
		'resetPassword' => 'Resetta password'
	], 
	'messages' => [
		'goodbye' => 'Arrivederci %s %s.', 
		'welcome' => 'Buongiorno %s %s.', 
		'loginFailed' => 'Tentativo di accesso fallito.', 
	    'loginNeeded' => 'Effettuare il login per accedere.', 
	    'resetPasswordFailed' => 'Errore durante reset password.', 
		'setPasswordSuccess' => 'Password impostata. <a class="fw-bold text-white" href="' . base_url('backend/auth/login') . '"><i class="fa-solid fa-right-to-bracket"></i> Accedi.</a>', 
		'setPasswordFailed' => 'Errore durante impostazione password.', 
		'setPasswordError' => 'Errore durante impostazione password.', 
		'checkAuthError' => 'Codice di autenticazione non valido oppure scaduto.', 
		'tooManyAttempts' => 'Sono stati effettuati troppi tentativi.', 
	    'currentSessionOn' => '%s %s sessione ancora in corso.', 
		'validationErrors' => 'Errori di validazione.', 
		'resetPasswordSuccess' => 'Processo di reset password impostato. Verifica l\'email inviata per terminare le operazioni.', 
		'resetPasswordSuccessNoEmail' => 'Processo di reset password impostato, ma l\'email non è stata inviata. Contattare l\'amministratore.', 
		'expiredCode' => 'Codice OTP scaduto.',
		'wrongCode' => 'Codice OTP errato.', 
	]
];