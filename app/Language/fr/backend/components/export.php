<?php declare(strict_types = 1);

return [
	'panels' => [
		'main' => 'Exportation des données CSV de la table <span class="text-primary fw-bold">%s</span>', 
	], 
    'links' => [
        'export' => 'Exporter en CSV', 
    ],
    'buttons' => [
    	'undo' => 'Annuler', 
    	'export' => 'Exporter', 
    ], 
    'messages' => [
        'validateToastErrors' => '%s',
        'invalidEntity' => 'Nom de table inconnu.', 
        'noColumnsSelected' => 'Aucune colonne n\'a été sélectionnée.', 
        'noDataFound' => 'Aucun enregistrement trouvé.', 
        'exportSuccess' => '%d enregistrements exportés de la table %s.', 
        'processedRows' => '%d lignes traitées', 
    ],
];
