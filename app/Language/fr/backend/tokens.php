<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Jetons',
	],
	'options' => [
		'first' => 'Première option', 
		'second' => 'Deuxième option', 
		'thirst' => 'Troisième option', 
		],
	'links' => [
		'filters' => 'Rechercher', 
		'resetFilters' => 'Effacer la recherche', 
		'resetSorting' => 'Réinitialiser le tri', 
		'reloadList' => 'Recharger la liste', 
		'export' => 'Exporter en CSV', 
		],
	'labels' => [
		'id' => 'ID',
		'uuid' => 'UUID', 
		'username' => 'Nom d\'utilisateur', 
		'tokenCreate' => 'Date de début', 
		'tokenExpire' => 'Date d\'expiration', 
		'tokenType' => 'Type', 
		'operatingSystem' => 'Système d\'exploitation', 
		'browser' => 'Navigateur web', 
		'ipAddress' => 'Adresse IP', 
		'dateFrom' => 'Date de début', 
		'dateTo' => 'Date de fin',
		'createdAt' => 'Ajouté le ', 
		'session' => 'Session', 
		'activation' => 'Activation', 
		'cookie' => 'Se souvenir de moi'
	],
	'errors' => [
		'uuid' => 'UUID non conforme.', 
		'id' => 'ID non conforme.', 
	], 
	'actions' => [
		'hardDelete' => 'Supprimer'
	], 
	'buttons' => [
		'actions' => 'Actions'
	], 
	'placeholders' => [
		'searchUsername' => 'Rechercher par nom d\'utilisateur...', 
		'searchType' => 'Rechercher par type...', 
		'dateFrom' => 'Rechercher par date de début...', 
		'dateTo' => 'Rechercher par date de fin...',
	], 
	'audits' => [
	    'deleteToken' => 'Supprimer le jeton %s %s',
	],
	'messages' => [
		'validationErrors' => 'Erreurs de validation.', 
		'validationToastErrors' => '%s', 
		'noTokensFound' => 'Aucun jeton trouvé.', 
		'areYouSureHardDelete' => 'Êtes-vous sûr de vouloir supprimer définitivement le jeton de <b>%s %s</b> ?', 
		'deleteTokenSuccess' => 'Le jeton de %s %s a été supprimé avec succès.', 
		'deleteTokenError' => 'La suppression du jeton a échoué.', 
		'protectedAdmin' => 'Protégé contre les modifications.', 
		'cannotModifyDeleted' => 'Impossible de modifier un administrateur supprimé.', 
	]
];
