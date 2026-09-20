<?php declare(strict_types = 1);

return [
    'panels' => [
        'main' => 'Exportación de datos CSV de la tabla <span class="text-primary fw-bold">%s</span>',
    ],
    'links' => [
        'export' => 'Exportar CSV',
    ],
    'labels' => [
        'selectDeselectAll' => 'Seleccionar / Deseleccionar todas',
        'selectColumns' => 'Selecciona las columnas que se incluirán en la exportación. El sistema incluirá forzosamente la clave primaria.',
        'dataElaboration' => 'Preparando los datos...',
        'runningExportation' => 'Exportación en curso...',
    ],
    'buttons' => [
        'undo' => 'Cancelar',
        'export' => 'Exportar',
        'operationUndo' => 'Cancelar operación',
        'exportationStart' => 'Iniciar exportación',
    ],
    'messages' => [
        'validateToastErrors' => '%s',
        'invalidEntity' => 'Nombre de tabla desconocido.',
        'noColumnsSelected' => 'No se han seleccionado columnas.',
        'noDataFound' => 'No se encontraron registros.',
        'exportSuccess' => 'Se han exportado %d registros de la tabla %s.',
        'processedRows' => '%d filas procesadas',
    ],
];