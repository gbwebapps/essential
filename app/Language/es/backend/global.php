<?php declare(strict_types = 1);

return [
    'interface' => [
        'showPassword' => 'Mostrar contraseña',
        'hidePassword' => 'Ocultar contraseña',
        'themes' => 'Temas',
        'changeError' => 'No se pudo cambiar la preferencia. Inténtalo de nuevo.',
    ],
    'menu' => [
        'topRight' => [
            'admins' => 'Administradores',
            'groups' => 'Grupos',
            'audits' => 'Registro de actividad',
            'tokens' => 'Tokens',
            'logs' => 'Accesos',
            'account' => 'Perfil',
            'logout' => 'Salir'
        ],
        'bottomLeft' => [
            'dashboard' => 'Paneles',
            'brands' => 'Marcas',
            'categories' => 'Categorías',
            'products' => 'Productos',
        ],
        'bottomRight' => [
            'settings' => 'Configuración',
            'tools' => 'Herramientas',
            'warehouse' => 'Almacén',
        ],
    ],
    'menuCounts' => [
        'active' => 'Activos',
        'trashed' => 'Eliminados',
        'suspended' => 'Deshabilitados',
    ],
    'links' => [
        'selectAll' => 'Seleccionar todos'
    ],
    'labels' => [
        'select' => 'Seleccionar'
    ],
    'formats' => [
        'conversationalDate' => "EEEE d 'de' MMMM 'de' yyyy 'a las' HH:mm:ss",
    ],
    'buttons' => [
        'modules' => 'Módulos',
        'services' => 'Servicios',
        'backToTop' => 'Volver arriba',
        'expandScreen' => 'Ampliar pantalla',
        'reduceScreen' => 'Reducir pantalla',
        'options' => 'Opciones',
        'undo' => 'Cancelar',
        'export' => 'Exportar',
        'yes' => 'Sí',
        'no' => 'No',
        'close' => 'Cerrar',
        'exportPdf' => 'Exportar a PDF',
        'remove' => 'Eliminar',
    ],
    'errors' => [
        'err403' => 'Sesión de seguridad caducada o acceso no autorizado. Recargando...',
        'err404' => 'La recurso solicitado no se ha encontrado o ha sido eliminado.',
        'err500' => 'Se ha producido un error crítico durante el procesamiento de la solicitud.',
        'err504' => 'El servidor ha tardado demasiado en responder. Operación cancelada.',
        'errNetwork' => 'Conexión de red ausente. Comprueba el estado de tu conexión a internet.',
        'fullscreenError' => 'No se puede cambiar el modo de pantalla completa.',
    ],
    'modals' => [
        'exportPdfTitle' => 'Configuración de exportación PDF',
        'exportPdfOrientation' => 'Orientación',
        'exportPdfOrientationVert' => 'Vertical',
        'exportPdfOrientationHoriz' => 'Horizontal',
        'exportPdfFormat' => 'Formato',
        'exportPdfMargin' => 'Márgenes',
        'exportPdfMarginSup' => 'Superior',
        'exportPdfMarginRgt' => 'Derecho',
        'exportPdfMarginInf' => 'Inferior',
        'exportPdfMarginLft' => 'Izquierdo',
        'exportPdfCompression' => 'Compresión',
        'exportPdfImgQuality' => 'Calidad de imagen',
        'globalTitle' => 'Solicitud de confirmación'
    ],
    'pagination' => [
        'messageLeft' => 'Página %d de %d',
        'messageRight' => 'Del %d al %d de %d',
        'first' => 'Primero',
        'last' => 'Último',
        'next' => 'Siguiente',
        'previous' => 'Anterior',
    ],
    'audits' => [
        'importData' => 'Importación de datos en la tabla %s. Registros insertados: %d, registros actualizados: %d.',
    ],
    'messages' => [
        'getDataError' => 'Error durante la operación de recuperación de la lista.',
        'getUUIDError' => 'Error durante la operación de recuperación del detalle.',
        'UUIDNotFound' => 'Registro no encontrado.',
        'permissionDenied' => 'Acceso denegado.',
        'unsavedChanges' => 'Hay cambios sin guardar. ¿Quieres salir sin guardar?'
    ]
];
