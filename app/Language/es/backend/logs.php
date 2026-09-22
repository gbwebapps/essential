<?php declare(strict_types = 1);

return [
	'titles' => [
		'index' => 'Accesos',
	],
	'links' => [
		'filters' => 'Buscar',
		'resetFilters' => 'Limpiar búsqueda',
		'resetSorting' => 'Restablecer orden',
		'reloadList' => 'Recargar lista',
	],
	'errors' => [
		'uuid' => 'UUID no conforme.',
		'id' => 'ID no conforme.',
	],
	'actions' => [
		'hardDelete' => 'Desconectar'
	],
	'labels' => [
		'username' => 'Nombre de usuario',
		'typeToken' => 'Tipo',
		'createdAt' => 'Añadido el ',
		'operatingSystem' => 'Sistema operativo',
		'browser' => 'Navegador web',
		'ipAddress' => 'Dirección IP',
		'dateFrom' => 'Fecha de inicio',
		'dateTo' => 'Fecha de fin',
		'login' => 'Inicio de sesión',
		'logout' => 'Desconexión',
		'logoutReason' => 'Motivo de desconexión',
		'manual' => 'Manual',
		'timeout' => 'Caducado',
		'deleted' => 'Eliminado',
		'banned' => 'Bloqueado',
		'undefined' => 'Indefinido',
		'pending' => 'En curso...',
		'duration' => 'Duración'
	],
	'placeholders' => [
		'searchUsername' => 'Buscar por nombre de usuario...',
		'dateFrom' => 'Buscar por fecha de inicio...',
		'dateTo' => 'Buscar por fecha de fin...',
		'searchLogoutReason' => 'Buscar por motivo de desconexión...'
	],
	'audits' => [
	    'deleteToken' => 'Interrupción forzada de la sesión actual',
	],
	'messages' => [
		'noLogsFound' => 'No se encontraron registros.',
		'areYouSureHardDelete' => '¿Estás seguro de que deseas desconectar a <b>%s %s?</b>',
		'validationErrors' => 'Errores de validación.',
		'validationToastErrors' => '%s',
		'deleteTokenSuccess' => '%s %s se ha desconectado con éxito.',
		'deleteTokenError' => 'La desconexión no se ha podido completar.',
		'protectedAdmin' => 'Protegido contra modificaciones.',
		'cannotModifyDeleted' => 'No es posible modificar un administrador eliminado.',
	]
];