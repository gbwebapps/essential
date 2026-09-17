<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Bienvenue', 
		'login' => 'Se connecter', 
		'resetPassword' => 'Réinitialiser le mot de passe', 
		'setPassword' => 'Définir le mot de passe', 
		'verify' => 'Vérifier le code OTP'
	],
	'labels' => [
		'email' => 'E-mail', 
		'username' => 'Nom d\'utilisateur', 
		'password' => 'Mot de passe', 
		'rememberMe' => 'Se souvenir de moi', 
		'newPassword' => 'Saisir le mot de passe', 
		'confirmNewPassword' => 'Confirmer le mot de passe', 
		'code' => 'Code OTP'
	], 
	'placeholders' => [
		'email' => 'Saisir l\'adresse e-mail ici...', 
		'username' => 'Saisir le nom d\'utilisateur ici...', 
		'password' => 'Saisir le mot de passe ici...', 
		'newPassword' => 'Saisir le mot de passe ici...', 
		'confirmNewPassword' => 'Confirmer le mot de passe', 
		'code' => 'Saisir le code OTP ici...', 
	], 
	'buttons' => [
		'login' => 'Se connecter', 
		'resetPassword' => 'Réinitialiser le mot de passe', 
		'setPassword' => 'Définir le mot de passe', 
		'verify' => 'Vérifier le code OTP'

	], 
	'links' => [
		'login' => 'Se connecter', 
		'resetPassword' => 'Réinitialiser le mot de passe'
	], 
	'messages' => [
		'goodbye' => 'Au revoir %s %s.', 
		'welcome' => 'Bonjour %s %s.', 
		'loginFailed' => 'Tentative de connexion échouée.', 
	    'loginNeeded' => 'Veuillez vous connecter pour accéder.', 
	    'resetPasswordFailed' => 'Erreur lors de la réinitialisation du mot de passe.', 
		'setPasswordSuccess' => 'Mot de passe défini. <a class="fw-bold text-white" href="' . base_url('backend/auth/login') . '"><i class="fa-solid fa-right-to-bracket"></i> Se connecter.</a>', 
		'setPasswordFailed' => 'Erreur lors de la définition du mot de passe.', 
		'setPasswordError' => 'Erreurs lors de la définition du mot de passe.', 
		'checkAuthError' => 'Code d\'authentification invalide ou expiré.', 
		'tooManyAttempts' => 'Trop de tentatives ont été effectuées.', 
	    'currentSessionOn' => 'Session de %s %s toujours en cours.', 
		'validationErrors' => 'Erreurs de validation.', 
		'resetPasswordSuccess' => 'Processus de réinitialisation du mot de passe configuré. Veuillez vérifier vos e-mails pour terminer l\'opération.', 
		'resetPasswordSuccessNoEmail' => 'Processus de réinitialisation du mot de passe configuré, mais l\'e-mail n\'a pas pu être envoyé. Veuillez contacter l\'administrateur.', 
		'expiredCode' => 'Code OTP expiré.',
		'wrongCode' => 'Code OTP incorrect.', 
	]
];
