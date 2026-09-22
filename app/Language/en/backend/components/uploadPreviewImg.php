<?php declare(strict_types = 1);

return [
    'title' => 'Image Upload', 
    'labels' => [
        'uuid' => 'UUID', 
        'entity' => 'Entity', 
        'context' => 'Context', 
        'dragAndDrop' => 'Drag images to upload them.',
    ], 
    'buttons' => [
        'uploadImages' => 'Select images',
        'sendImages' => 'Send images',
    ], 
    'audits' => [
        'uploadPreviewImg' => 'Saving preview images.',
    ],
    'messages' => [
        'notImagesSelected' => 'Please select at least one image.',
        'saveImagesSuccess' => 'The images were saved successfully.',
        'saveImagesError' => 'Error during image saving operation.',
        'validationErrors' => 'Validation errors',
        'validationToastErrors' => '%s', 
        'deleteError' => 'Error during image deletion operation.', 
        'deleteSuccess' => 'The image was deleted successfully.', 
        'setCoverError' => 'Error during cover setting operation.', 
        'setCoverSuccess' => 'The cover was set successfully.', 
        'removeCoverError' => 'Error during cover removal operation.', 
        'removeCoverSuccess' => 'The cover was removed successfully.', 
        'imagesRequired' => 'Upload at least one image',
    ],
];