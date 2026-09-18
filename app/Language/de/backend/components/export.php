<?php declare(strict_types = 1);

return [
	'panels' => [
		'main' => 'CSV-Datenexport aus der Tabelle <span class="text-primary fw-bold">%s</span>', 
	], 
    'links' => [
        'export' => 'CSV exportieren', 
    ],
    'labels' => [
        'selectDeselectAll' => 'Alle auswählen / abwählen',
        'selectColumns' => 'Wählen Sie die Spalten aus, die in den Export einbezogen werden sollen. Der Primärschlüssel wird vom System zwingend mit eingeschlossen.', 
        'dataElaboration' => 'Datenaufbereitung...',  
        'runningExportation' => 'Export läuft...', 
    ], 
    'buttons' => [
    	'undo' => 'Abbrechen', 
    	'export' => 'Exportieren', 
        'operationUndo' => 'Vorgang abbrechen', 
        'exportationStart' => 'Export starten',
    ], 
    'messages' => [
        'validateToastErrors' => '%s',
        'invalidEntity' => 'Unbekannter Tabellenname.', 
        'noColumnsSelected' => 'Es wurden keine Spalten ausgewählt.', 
        'noDataFound' => 'Keine Datensätze gefunden.', 
        'exportSuccess' => '%d Datensätze aus der Tabelle %s exportiert.', 
        'processedRows' => '%d Zeilen verarbeitet', 
    ],
];
