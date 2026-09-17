<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Willkommen', 
		'login' => 'Anmelden', 
		'resetPassword' => 'Passwort zurücksetzen', 
		'setPassword' => 'Passwort festlegen', 
		'verify' => 'OTP verifizieren'
	],
	'labels' => [
		'email' => 'E-Mail', 
		'username' => 'Benutzername', 
		'password' => 'Passwort', 
		'rememberMe' => 'Angemeldet bleiben', 
		'newPassword' => 'Passwort eingeben', 
		'confirmNewPassword' => 'Passwort bestätigen', 
		'code' => 'OTP-Code'
	], 
	'placeholders' => [
		'email' => 'E-Mail-Adresse hier eingeben...', 
		'username' => 'Benutzername hier eingeben...', 
		'password' => 'Passwort hier eingeben...', 
		'newPassword' => 'Passwort hier eingeben...', 
		'confirmNewPassword' => 'Passwort bestätigen', 
		'code' => 'OTP-Code hier eingeben...', 
	], 
	'buttons' => [
		'login' => 'Anmelden', 
		'resetPassword' => 'Passwort zurücksetzen', 
		'setPassword' => 'Passwort festlegen', 
		'verify' => 'OTP verifizieren'

	], 
	'links' => [
		'login' => 'Anmelden', 
		'resetPassword' => 'Passwort zurücksetzen'
	], 
	'messages' => [
		'goodbye' => 'Auf Wiedersehen %s %s.', 
		'welcome' => 'Guten Tag %s %s.', 
		'loginFailed' => 'Anmeldeversuch fehlgeschlagen.', 
	    'loginNeeded' => 'Bitte melden Sie sich an, um fortzufahren.', 
	    'resetPasswordFailed' => 'Fehler beim Zurücksetzen des Passworts.', 
		'setPasswordSuccess' => 'Passwort festgelegt. <a class="fw-bold text-white" href="' . base_url('backend/auth/login') . '"><i class="fa-solid fa-right-to-bracket"></i> Anmelden.</a>', 
		'setPasswordFailed' => 'Fehler beim Festlegen des Passworts.', 
		'setPasswordError' => 'Fehler beim Festlegen des Passworts.', 
		'checkAuthError' => 'Ungültiger oder abgelaufener Authentifizierungscode.', 
		'tooManyAttempts' => 'Es wurden zu viele Versuche unternommen.', 
	    'currentSessionOn' => 'Sitzung von %s %s läuft noch.', 
		'validationErrors' => 'Validierungsfehler.', 
		'resetPasswordSuccess' => 'Passwort-Reset-Prozess eingerichtet. Bitte überprüfen Sie Ihre E-Mail, um den Vorgang abzuschließen.', 
		'resetPasswordSuccessNoEmail' => 'Passwort-Reset-Prozess eingerichtet, aber die E-Mail konnte nicht gesendet werden. Bitte kontaktieren Sie den Administrator.', 
		'expiredCode' => 'OTP-Code abgelaufen.',
		'wrongCode' => 'Falscher OTP-Code.', 
	]
];
