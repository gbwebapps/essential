<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => '令牌',
	],
	'options' => [
		'first' => '第一选项', 
		'second' => '第二选项', 
		'thirst' => '第三选项', 
		],
	'links' => [
		'filters' => '搜索', 
		'resetFilters' => '清除搜索', 
		'resetSorting' => '重置排序', 
		'reloadList' => '重新加载列表', 
		'export' => '导出 CSV', 
		],
	'labels' => [
		'id' => 'ID',
		'uuid' => 'UUID', 
		'username' => '用户名', 
		'tokenCreate' => '开始日期', 
		'tokenExpire' => '过期日期', 
		'tokenType' => '类型', 
		'operatingSystem' => '操作系统', 
		'browser' => '网页浏览器', 
		'ipAddress' => 'IP 地址', 
		'dateFrom' => '开始日期', 
		'dateTo' => '结束日期',
		'createdAt' => '添加时间 ', 
		'session' => '会话', 
		'activation' => '激活', 
		'cookie' => '记住我'
	],
	'errors' => [
		'uuid' => 'UUID 不符合规范。', 
		'id' => 'ID 不符合规范。', 
	], 
	'actions' => [
		'hardDelete' => '删除'
	], 
	'buttons' => [
		'actions' => '操作'
	], 
	'placeholders' => [
		'searchUsername' => '按用户名搜索...', 
		'searchType' => '按类型搜索...', 
		'dateFrom' => '按开始日期搜索...', 
		'dateTo' => '按结束日期搜索...',
	], 
	'messages' => [
		'validationErrors' => '验证错误。', 
		'validationToastErrors' => '%s', 
		'noTokensFound' => '未找到令牌。', 
		'areYouSureHardDelete' => '您确定要彻底删除 <b>%s %s</b> 的令牌吗？', 
		'deleteTokenSuccess' => '%s %s 的令牌已成功删除。', 
		'deleteTokenError' => '删除令牌失败', 
		'protectedAdmin' => '受保护，无法修改。', 
		'cannotModifyDeleted' => '无法修改已删除的管理员。', 
	]
];
