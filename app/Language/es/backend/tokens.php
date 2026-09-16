<?php declare(strict_types = 1);

return [
	'titles' => [
		'index' => 'Tokens',
	],
	'options' => [
		'first' => 'Primera opción',
		'second' => 'Segunda opción',
		'thirst' => 'Tercera opción',
		],
	'links' => [
		'filters' => 'Buscar',
		'resetFilters' => 'Limpiar búsqueda',
		'resetSorting' => 'Restablecer orden',
		'reloadList' => 'Recargar lista',
		'export' => 'Exportar CSV',
		],
	'labels' => [
		'id' => 'ID',
		'uuid' => 'UUID',
		'username' => 'Nombre de usuario',
		'tokenCreate' => 'Fecha de inicio',
		'tokenExpire' => 'Fecha de caducidad',
		'tokenType' => 'Tipo',
		'operatingSystem' => 'Sistema operativo',
		'browser' => 'Navegador web',
		'ipAddress' => 'Dirección IP',
		'dateFrom' => 'Fecha de inicio',
		'dateTo' => 'Fecha de fin',
		'createdAt' => 'Añadido el ',
		'session' => 'Sesión',
		'activation' => 'Activación',
		'cookie' => 'Recuérdame'
	],
	'errors' => [
		'uuid' => 'UUID no conforme.',
		'id' => 'ID no conforme.',
	],
	'actions' => [
		'hardDelete' => 'Eliminar'
	],
	'buttons' => [
		'actions' => 'Acciones'
	],
	'placeholders' => [
		'searchUsername' => 'Buscar por nombre de usuario...',
		'searchType' => 'Buscar por tipo...',
		'dateFrom' => 'Buscar por fecha de inicio...',
		'dateTo' => 'Buscar por fecha de fin...',
	],
	'messages' => [
		'validationErrors' => 'Errores de validación.',
		'validationToastErrors' => '%s',
		'noTokensFound' => 'No hay tokens disponibles.',
		'areYouSureHardDelete' => '¿Estás seguro de eliminar definitivamente el token de <b>%s %s</b>?',
		'deleteTokenSuccess' => 'El token de %s %s se ha eliminado con éxito.',
		'deleteTokenError' => 'La eliminación del token no se ha podido completar',
		'protectedAdmin' => 'Protegido contra modificaciones.',
		'cannotModifyDeleted' => 'No es posible modificar un administrador eliminado.',
	]
];