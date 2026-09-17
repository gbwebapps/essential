<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Groupes', 
	],
	'panels' => [
		'addGroup' => 'Ajouter un groupe', 
		'listGroup' => 'Liste des groupes', 
		'exceptionsPerms' => 'Exceptions d\'autorisations'
	], 
	'options' => [
		'first' => 'Première option', 
		'second' => 'Deuxième option', 
		'thirst' => 'Troisième option', 
	], 
	'labels' => [
		'id' => 'ID', 
		'groupName' => 'Nom du groupe', 
		'groupDescription' => 'Description du groupe', 
		'assignedToGroup' => 'Assigné au GROUPE', 
		'notAssignedToGroup' => 'Non assigné au GROUPE', 
		'admins' => 'Rechercher un administrateur', 
		'name' => 'Nom du groupe',
		'description' => 'Description du groupe',
		'permissions' => 'Autorisations', 
		'query' => 'Champ de recherche'
	], 
	'placeholders' => [
		'groupName' => 'Saisir le nom du groupe...', 
		'groupDescription' => 'Saisir la description du groupe...', 
		'admins' => 'Saisir les trois premières lettres...'
	], 
	'buttons' => [
		'delete' => 'Supprimer', 
		'refreshData' => 'Recharger les données', 
		'resetData' => 'Réinitialiser les données', 
		'sendData' => 'Enregistrer les données', 
	], 
	'errors' => [
		'permission' => 'Autorisation non conforme.', 
		'wrongUUID' => 'Format d\'UUID incorrect.', 
		'wrongID' => 'Format d\'ID incorrect', 
	],
	'messages' => [
		'areYouSureDeleteGroup' => 'Êtes-vous sûr de vouloir supprimer le groupe <b>%s</b> ?', 
		'areYouSureToReload' => 'Êtes-vous sûr de vouloir recharger les données ?', 
		'areYouSureToResetData' => 'Êtes-vous sûr de vouloir effacer les données ?', 
		'noAdminFound' => 'Aucun administrateur trouvé.', 
		'noGroupFound' => 'Aucun groupe trouvé.', 
		'noDataChanged' => 'Aucune modification n\'a été effectuée.', 
		'validationErrors' => 'Erreurs de validation.', 
		'validateToastErrors' => '%s', 
		'addError' => 'L\'ajout du groupe a échoué.', 
		'addSuccess' => 'Groupe ajouté avec succès.', 
		'editError' => 'La mise à jour du groupe a échoué.',
		'editSuccess' => 'Groupe mis à jour avec succès.',
		'delError' => 'La suppression du groupe a échoué.', 
		'delSuccess' => 'Groupe supprimé avec succès.', 
		'saveExceptionsSuccess' => 'Exception ajoutée avec succès pour %s %s.', 
		'saveExceptionsError' => 'L\'ajout de l\'exception a échoué.', 
		'protectedAdmin' => 'Protégé contre les modifications.', 
		'cannotModifyDeleted' => 'Impossible de modifier un administrateur supprimé.', 
		'hasAdminsAttached' => 'Impossible de supprimer un groupe auquel des administrateurs sont associés.'
	]
];
