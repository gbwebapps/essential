<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => '登录历史', 
	], 
	'links' => [
		'filters' => '搜索', 
		'resetFilters' => '清除搜索', 
		'resetSorting' => '重置排序', 
		'reloadList' => '重新加载列表', 
	], 
	'errors' => [
		'uuid' => 'UUID 不符合规范。', 
		'id' => 'ID 不符合规范。', 
	], 
	'actions' => [
		'hardDelete' => '断开连接'
	],
	'labels' => [
		'username' => '用户名', 
		'typeToken' => '类型', 
		'createdAt' => '添加时间 ', 
		'operatingSystem' => '操作系统', 
		'browser' => '网页浏览器', 
		'ipAddress' => 'IP 地址', 
		'dateFrom' => '开始日期', 
		'dateTo' => '结束日期', 
		'login' => '登录', 
		'logout' => '退出登录', 
		'logoutReason' => '退出方式', 
		'manual' => '手动', 
		'timeout' => '已过期', 
		'deleted' => '已删除', 
		'banned' => '已封禁', 
		'undefined' => '未定义', 
		'pending' => '进行中...', 
		'duration' => '持续时间'
	],
	'placeholders' => [
		'searchUsername' => '按用户名搜索...', 
		'dateFrom' => '按开始日期搜索...', 
		'dateTo' => '按结束日期搜索...',
		'searchLogoutReason' => '按退出原因搜索...'
	], 
	'audits' => [
	    'deleteToken' => '强制终止当前会话',
	],
	'messages' => [
		'noLogsFound' => '未找到日志。', 
		'areYouSureHardDelete' => '您确定要断开 <b>%s %s</b> 的连接吗？', 
		'validationErrors' => '验证错误。', 
		'validationToastErrors' => '%s', 
		'deleteTokenSuccess' => '%s %s 已成功断开连接。', 
		'deleteTokenError' => '断开连接失败。', 
		'protectedAdmin' => '受保护，无法修改。', 
		'cannotModifyDeleted' => '无法修改已删除的管理员。',
	]
];
