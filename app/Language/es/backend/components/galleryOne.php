<?php declare(strict_types = 1);

return [
	'title' => 'Galería de imágenes',
    'labels' => [
        'removeCover' => 'Quitar portada',
        'setCover' => 'Establecer portada',
        'delete' => 'Eliminar imagen',
        'viewImage' => 'Ver imagen',
        'noImagesFound' => 'No se han encontrado imágenes.',
        'id' => 'ID',
        'uuid' => 'UUID',
        'entity' => 'Entidad',
        'filename' => 'Nombre de archivo',
        'context' => 'Contexto',
    ],
    'buttons' => [
        'reload' => 'Recargar galería',
    ],
    'audits' => [
        'deleteImage' => 'Eliminando imagen.',
        'setCover'    => 'Estableciendo portada.',
        'removeCover' => 'Eliminando portada.',
    ],
    'messages' => [
        'areYouSureRemoveCover' => '¿Estás seguro de que deseas quitar la portada de esta imagen?',
        'areYouSureSetCover' => '¿Estás seguro de que deseas establecer esta imagen como portada?',
        'areYouSureDeleteImage' => '¿Estás seguro de que deseas eliminar esta imagen?',
        'setCoverError' => 'Error durante la operación de establecimiento de portada.',
        'setCoverSuccess' => 'Portada establecida con éxito.',
        'removeCoverSuccess' => 'Portada quitada con éxito.',
        'deleteError' => 'Error durante la operación de eliminación de la imagen.',
        'deleteSuccess' => 'Imagen eliminada con éxito.',
        'removeCoverError' => 'Error durante la operación de retirada de la portada.',
        'validationToastErrors' => '%s',
    ],
];