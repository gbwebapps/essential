<?php declare(strict_types = 1);

return [
	'title' => 'Image Gallery', 
    'labels' => [
        'removeCover' => 'Remove cover',
        'setCover' => 'Set cover',
        'delete' => 'Delete image',
        'viewImage' => 'View image',
        'noImagesFound' => 'No images found.',
        'id' => 'ID', 
        'uuid' => 'UUID', 
        'entity' => 'Entity', 
        'filename' => 'Filename', 
        'context' => 'Context', 
    ], 
    'buttons' => [
        'reload' => 'Reload gallery', 
    ], 
    'audits' => [
        'deleteImage' => 'Deleting image.',
        'setCover'    => 'Setting cover.',
        'removeCover' => 'Removing cover.',
    ],
    'messages' => [
        'areYouSureRemoveCover' => 'Are you sure you want to remove the cover from this image?',
        'areYouSureSetCover' => 'Are you sure you want to set the cover for this image?',
        'areYouSureDeleteImage' => 'Are you sure you want to delete this image?',
        'setCoverError' => 'Error during cover setting operation.', 
        'setCoverSuccess' => 'Cover set successfully.', 
        'removeCoverSuccess' => 'Cover removed successfully.', 
        'deleteError' => 'Error during image deletion operation.', 
        'deleteSuccess' => 'Image deleted successfully.', 
        'removeCoverError' => 'Error during cover removal operation.', 
        'validationToastErrors' => '%s', 
    ],
];