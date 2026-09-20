<?php declare(strict_types = 1);

return [
    'panels' => [
        'main' => 'CSV data export from the table <span class="text-primary fw-bold">%s</span>',
    ],
    'links' => [
        'export' => 'Export CSV',
    ],
    'labels' => [
        'selectDeselectAll' => 'Select / Deselect all',
        'selectColumns' => 'Select the columns to include in the export. The primary key will be forcibly included by the system.',
        'dataElaboration' => 'Preparing data...',
        'runningExportation' => 'Exporting in progress...',
    ],
    'buttons' => [
        'undo' => 'Cancel',
        'export' => 'Export',
        'operationUndo' => 'Cancel operation',
        'exportationStart' => 'Start export',
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