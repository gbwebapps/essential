<?php declare(strict_types = 1);

return [
	'titles' => [
		'index' => 'Grupos',
	],
	'panels' => [
		'addGroup' => 'Añadir grupo',
		'listGroup' => 'Lista de grupos',
		'exceptionsPerms' => 'Excepciones de permisos'
	],
	'options' => [
		'first' => 'Primera opción',
		'second' => 'Segunda opción',
		'thirst' => 'Tercera opción',
	],
	'labels' => [
		'id' => 'ID',
		'groupName' => 'Nombre del grupo',
		'groupDescription' => 'Descripción del grupo',
		'assignedToGroup' => 'Asignado al GRUPO',
		'notAssignedToGroup' => 'No asignado al GRUPO',
		'admins' => 'Buscar administrador',
		'name' => 'Nombre del grupo',
		'description' => 'Descripción del grupo',
		'permissions' => 'Permisos',
		'query' => 'Campo de búsqueda', 
		'groupNameBar' => 'Grupo %s',
	],
	'placeholders' => [
		'groupName' => 'Introduce el nombre del grupo...',
		'groupDescription' => 'Introduce la descripción del grupo...',
		'admins' => 'Escribe las primeras tres letras...'
	],
	'buttons' => [
		'delete' => 'Eliminar',
		'refreshData' => 'Actualizar datos',
		'resetData' => 'Restablecer datos',
		'sendData' => 'Guardar datos',
	],
	'errors' => [
		'permission' => 'Permiso no conforme.',
		'wrongUUID' => 'Formato de UUID incorrecto.',
		'wrongID' => 'Formato de ID incorrecto',
	],
	'audits' => [
	    'addGroup'       => 'Añadiendo grupo %s',
	    'editGroup'      => 'Actualizando grupo %s',
	    'deleteGroup'    => 'Eliminando grupo %s',
	    'saveExceptions' => 'Insertando excepción %s %s',
	],
	'messages' => [
		'areYouSureDeleteGroup' => '¿Estás seguro de que deseas eliminar el grupo <b>%s</b>?',
		'areYouSureToReload' => '¿Estás seguro de que deseas actualizar los datos?',
		'areYouSureToResetData' => '¿Estás seguro de que deseas restablecer los datos?',
		'noAdminFound' => 'Ningún administrador encontrado.',
		'noGroupFound' => 'Ningún grupo encontrado.',
		'noDataChanged' => 'No se han realizado cambios.',
		'validationErrors' => 'Errores de validación.',
		'validateToastErrors' => '%s',
		'addError' => 'La adición del grupo no se ha podido completar.',
		'addSuccess' => 'Grupo añadido con éxito.',
		'editError' => 'La actualización del grupo no se ha podido completar.',
		'editSuccess' => 'Grupo actualizado con éxito.',
		'delError' => 'La eliminación del grupo no se ha podido completar.',
		'delSuccess' => 'Grupo eliminado con éxito.',
		'saveExceptionsSuccess' => 'Excepción añadida con éxito a %s %s.',
		'saveExceptionsError' => 'La adición de la excepción no se ha podido completar.',
		'protectedAdmin' => 'Protegido contra modificaciones.',
		'cannotModifyDeleted' => 'No es posible modificar un administrador eliminado.',
		'hasAdminsAttached' => 'No es posible eliminar un grupo que tiene administradores asociados.'
	]
];