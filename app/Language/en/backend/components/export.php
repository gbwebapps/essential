<?php declare(strict_types = 1);

return [
	'panels' => [
		'main' => 'Export CSV data from table <span class="text-primary fw-bold">%s</span>', 
	], 
    'links' => [
        'export' => 'Export CSV', 
    ],
    'buttons' => [
    	'undo' => 'Undo', 
    	'export' => 'Export', 
    ], 
    'messages' => [
        'validateToastErrors' => '%s',
        'invalidEntity' => 'Unknown table name.', 
        'noColumnsSelected' => 'No columns selected.', 
        'noDataFound' => 'No records found.', 
        'exportSuccess' => 'Exported %d records from table %s.', 
        'processedRows' => 'Processed %d rows', 
    ],
];