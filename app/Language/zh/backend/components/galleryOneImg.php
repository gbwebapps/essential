<?php declare(strict_types = 1);

return [
	'title' => '图片画廊', 
    'labels' => [
        'removeCover' => '移除封面',
        'setCover' => '设置封面',
        'delete' => '删除图片',
        'viewImage' => '查看图片',
        'noImagesFound' => '未找到图片。',
        'id' => 'ID', 
        'uuid' => 'UUID', 
        'entity' => '实体', 
        'filename' => '文件名', 
        'context' => '上下文', 
    ], 
    'buttons' => [
        'reload' => '重新加载画廊', 
    ], 
    'audits' => [
        'deleteImage' => '删除图片。',
        'setCover'    => '设置封面。',
        'removeCover' => '移除封面。',
    ],
    'messages' => [
        'areYouSureRemoveCover' => '您确定要移除此图片的封面设置吗？',
        'areYouSureSetCover' => '您确定要将此图片设置为封面吗？',
        'areYouSureDeleteImage' => '您确定要删除此图片吗？',
        'setCoverError' => '设置封面时出错。', 
        'setCoverSuccess' => '封面设置成功。', 
        'removeCoverSuccess' => '封面移除成功。', 
        'deleteError' => '删除图片时出错。', 
        'deleteSuccess' => '图片删除成功。', 
        'removeCoverError' => '移除封面时出错。', 
        'validationToastErrors' => '%s', 
    ],
];
