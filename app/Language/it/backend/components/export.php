<?php declare(strict_types = 1);

return [
	'panels' => [
		'main' => 'Esportazione dati CSV dalla tabella <span class="text-primary fw-bold">%s</span>', 
	], 
    'links' => [
        'export' => 'Esporta CSV', 
    ],
    'buttons' => [
    	'undo' => 'Annulla', 
    	'export' => 'Esporta', 
    ], 
    'messages' => [
        'validateToastErrors' => '%s',
        'invalidEntity' => 'Nome tabella sconosciuto.', 
        'noColumnsSelected' => 'Non sono state selezionate colonne.', 
        'noDataFound' => 'Non sono stati trovati records.', 
        'exportSuccess' => 'Esportazione tabella %s avvenuta con successo.', 
        'processedRows' => 'Elaborate %d righe', 
    ],
];