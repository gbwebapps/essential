<?php declare(strict_types = 1);

return [
	'panels' => [
		'main' => 'Exportación de datos CSV de la tabla <span class="text-primary fw-bold">%s</span>',
	],
    'links' => [
        'export' => 'Exportar CSV',
    ],
    'buttons' => [
    	'undo' => 'Cancelar',
    	'export' => 'Exportar',
    ],
    'messages' => [
        'validateToastErrors' => '%s',
        'invalidEntity' => 'Nombre de tabla desconocido.',
        'noColumnsSelected' => 'No se han seleccionado columnas.',
        'noDataFound' => 'No se han encontrado registros.',
        'exportSuccess' => 'Se han exportado %d registros de la tabla %s.',
        'processedRows' => 'Procesadas %d filas',
    ],
];