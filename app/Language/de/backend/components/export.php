<?php declare(strict_types = 1);

return [
	'panels' => [
		'main' => 'CSV-Datenexport aus der Tabelle <span class="text-primary fw-bold">%s</span>', 
	], 
    'links' => [
        'export' => 'CSV exportieren', 
    ],
    'buttons' => [
    	'undo' => 'Abbrechen', 
    	'export' => 'Exportieren', 
    ], 
    'messages' => [
        'validateToastErrors' => '%s',
        'invalidEntity' => 'Unbekannter Tabellenname.', 
        'noColumnsSelected' => 'Es wurden keine Spalten ausgewählt.', 
        'noDataFound' => 'Keine Datensätze gefunden.', 
        'exportSuccess' => '%d Datensätze erfolgreich aus der Tabelle %s exportiert.', 
        'processedRows' => '%d Zeilen verarbeitet', 
    ],
];
