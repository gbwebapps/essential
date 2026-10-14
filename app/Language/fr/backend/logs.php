<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Accès', 
	], 
	'links' => [
		'filters' => 'Rechercher', 
		'resetFilters' => 'Effacer la recherche', 
		'resetSorting' => 'Réinitialiser le tri', 
		'reloadList' => 'Recharger la liste', 
	], 
	'errors' => [
		'uuid' => 'UUID non conforme.', 
		'id' => 'ID non conforme.', 
	], 
	'actions' => [
		'disconnectSession' => 'Déconnecter'
	],
	'labels' => [
		'username' => 'Nom d\'utilisateur', 
		'typeToken' => 'Type', 
		'createdAt' => 'Ajouté le ', 
		'operatingSystem' => 'Système d\'exploitation', 
		'browser' => 'Navigateur web', 
		'ipAddress' => 'Adresse IP', 
		'dateFrom' => 'Date de début', 
		'dateTo' => 'Date de fin', 
		'login' => 'Connexion', 
		'logout' => 'Déconnexion', 
		'logoutReason' => 'Mode de déconnexion', 
		'manual' => 'Manuel', 
		'timeout' => 'Expiré', 
		'deleted' => 'Supprimé', 
		'banned' => 'Banni', 
		'undefined' => 'Indéfini', 
		'pending' => 'En cours...', 
		'duration' => 'Durée',
		'mobile' => 'Appareil mobile',
		'robot' => 'Robot',
		'filtersAndOptions' => 'Options et filtres',
		'showSystemDetails' => 'Afficher les détails système'
	],
	'placeholders' => [
		'searchUsername' => 'Rechercher par nom d\'utilisateur...', 
		'dateFrom' => 'Rechercher par date de début...', 
		'dateTo' => 'Rechercher par date de fin...',
		'searchLogoutReason' => 'Rechercher par motif de déconnexion...'
	], 
	'audits' => [
	    'disconnectSession' => 'Interruption forcée de la session en cours',
	],
	'messages' => [
		'noLogsFound' => 'Aucun journal trouvé.', 
		'areYouSureDisconnectSession' => 'Êtes-vous sûr de vouloir déconnecter <b>%s %s?</b>', 
		'validationErrors' => 'Erreurs de validation.', 
		'validationToastErrors' => '%s', 
		'disconnectSessionSuccess' => '%s %s a été déconnecté avec succès.', 
		'disconnectSessionError' => 'La déconnexion a échoué.', 
		'protectedAdmin' => 'Protégé contre les modifications.', 
		'cannotModifyDeleted' => 'Impossible de modifier un administrateur supprimé.',
	]
];
