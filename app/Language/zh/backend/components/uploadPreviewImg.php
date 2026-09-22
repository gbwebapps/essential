<?php declare(strict_types = 1);

return [
    'title' => '上传图片', 
    'labels' => [
        'uuid' => 'UUID', 
        'entity' => '实体', 
        'context' => '上下文', 
        'dragAndDrop' => '拖拽图片到此处上传。'
    ], 
    'buttons' => [
        'uploadImages' => '选择图片',
        'sendImages' => '发送图片',
    ],
    'audits' => [
        'uploadPreviewImg' => '保存预览图片。',
    ], 
    'messages' => [
        'notImagesSelected' => '请至少选择一张图片。',
        'saveImagesSuccess' => '图片已成功保存。',
        'saveImagesError' => '保存图片时出现问题。',
        'validationErrors' => '验证错误',
        'validationToastErrors' => '%s', 
        'deleteError' => '删除图片时出现问题。', 
        'deleteSuccess' => '图片已成功保存。', 
        'setCoverError' => '设置封面时出现问题。', 
        'setCoverSuccess' => '封面已成功设置。', 
        'removeCoverError' => '移除封面时出现问题。', 
        'removeCoverSuccess' => '封面已成功移除。', 
        'imagesRequired' => '请至少上传一张图片',
    ],
];
