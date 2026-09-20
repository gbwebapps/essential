<?php declare(strict_types = 1);

return [
	'panels' => [
		'main' => 'Esportazione dati CSV dalla tabella <span class="text-primary fw-bold">%s</span>', 
	], 
    'links' => [
        'export' => 'Esporta CSV', 
    ],
    'labels' => [
        'selectDeselectAll' => 'Seleziona / Deseleziona tutte',
        'selectColumns' => 'Seleziona le colonne da includere nell\'esportazione. La chiave primaria verrà inclusa forzatamente dal sistema.', 
        'dataElaboration' => 'Preparazione dei dati...',  
        'runningExportation' => 'Esportazione in corso...', 
    ], 
    'buttons' => [
    	'undo' => 'Annulla', 
    	'export' => 'Esporta', 
        'operationUndo' => 'Annulla operazione', 
        'exportationStart' => 'Avvia esportazione',
    ], 
    'messages' => [
        'validateToastErrors' => '%s',
        'invalidEntity' => 'Nome tabella sconosciuto.', 
        'noColumnsSelected' => 'Non sono state selezionate colonne.', 
        'noDataFound' => 'Non sono stati trovati records.', 
        'exportSuccess' => 'Esportati %d records dalla tabella %s.', 
        'processedRows' => 'Elaborate %d righe', 
    ],
];