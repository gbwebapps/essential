<?php declare(strict_types = 1);

return [
	'panels' => [
		'main' => 'Seleziona le colonne da esportare',
	], 
	'labels' => [
		'entity' => 'Tabella', 
        'columns' => 'Colonne', 
        'noColumns' => 'Non sono state selezionate colonne.', 
        'order' => 'Direzione ordinamento', 
        'column' => 'Colonna ordinamento', 
        'trash_filter' => 'Tipo di visualizzazione', 
        'page' => 'Pagina ordinamento', 
        'selectAll' => 'Seleziona / Deseleziona tutte'
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
        'exportSuccess' => 'Esportazione avvenuta con successo.', 
    ],
];