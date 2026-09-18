<?php declare(strict_types = 1);

return [
	'panels' => [
		'main' => '从数据表 <span class="text-primary fw-bold">%s</span> 导出 CSV 数据', 
	], 
    'links' => [
        'export' => '导出 CSV', 
    ],
    'buttons' => [
    	'undo' => '取消', 
    	'export' => '导出', 
    ], 
    'messages' => [
        'validateToastErrors' => '%s',
        'invalidEntity' => '未知的数据表名称。', 
        'noColumnsSelected' => '未选择任何列。', 
        'noDataFound' => '未找到记录。', 
        'exportSuccess' => '已从数据表 %s 导出 %d 条记录。', 
        'processedRows' => '已处理 %d 行', 
    ],
];
