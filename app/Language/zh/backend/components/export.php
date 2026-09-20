<?php declare(strict_types = 1);

return [
    'panels' => [
        'main' => '从表 <span class="text-primary fw-bold">%s</span> 导出 CSV 数据',
    ],
    'links' => [
        'export' => '导出 CSV',
    ],
    'labels' => [
        'selectDeselectAll' => '全选 / 取消全选',
        'selectColumns' => '选择要包含在导出中的列。主键将由系统强制包含。',
        'dataElaboration' => '正在准备数据...',
        'runningExportation' => '正在导出...',
    ],
    'buttons' => [
        'undo' => '取消',
        'export' => '导出',
        'operationUndo' => '取消操作',
        'exportationStart' => '开始导出',
    ],
    'messages' => [
        'validateToastErrors' => '%s',
        'invalidEntity' => '未知的表名。',
        'noColumnsSelected' => '未选择任何列。',
        'noDataFound' => '未找到记录。',
        'exportSuccess' => '已从表 %s 导出 %d 条记录。',
        'processedRows' => '已处理 %d 行',
    ],
];