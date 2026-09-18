<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => '群组', 
	],
	'panels' => [
		'addGroup' => '添加群组', 
		'listGroup' => '群组列表', 
		'exceptionsPerms' => '权限例外'
	], 
	'options' => [
		'first' => '第一选项', 
		'second' => '第二选项', 
		'thirst' => '第三选项', 
	], 
	'labels' => [
		'id' => 'ID', 
		'groupName' => '群组名称', 
		'groupDescription' => '群组描述', 
		'assignedToGroup' => '已分配到群组', 
		'notAssignedToGroup' => '未分配到群组', 
		'admins' => '搜索管理员', 
		'name' => '群组名称',
		'description' => '群组描述',
		'permissions' => '权限', 
		'query' => '搜索字段'
	], 
	'placeholders' => [
		'groupName' => '请输入群组名称...', 
		'groupDescription' => '请输入群组描述...', 
		'admins' => '输入前三个字母...'
	], 
	'buttons' => [
		'delete' => '删除', 
		'refreshData' => '重新加载数据', 
		'resetData' => '重置数据', 
		'sendData' => '保存数据', 
	], 
	'errors' => [
		'permission' => '权限不符合规范。', 
		'wrongUUID' => 'UUID 格式不正确。', 
		'wrongID' => 'ID 格式不正确', 
	],
	'messages' => [
		'areYouSureDeleteGroup' => '您确定要删除群组 <b>%s</b> 吗？', 
		'areYouSureToReload' => '您确定要重新加载数据吗？', 
		'areYouSureToResetData' => '您确定要清除数据吗？', 
		'noAdminFound' => '未找到管理员。', 
		'noGroupFound' => '未找到群组。', 
		'noDataChanged' => '未进行任何更改。', 
		'validationErrors' => '验证错误。', 
		'validateToastErrors' => '%s', 
		'addError' => '添加群组失败。', 
		'addSuccess' => '群组添加成功。', 
		'editError' => '更新群组失败。',
		'editSuccess' => '群组更新成功。',
		'delError' => '删除群组失败。', 
		'delSuccess' => '群组删除成功。', 
		'saveExceptionsSuccess' => '例外情况已成功添加到 %s %s。', 
		'saveExceptionsError' => '添加例外情况失败。', 
		'protectedAdmin' => '受保护，无法修改。', 
		'cannotModifyDeleted' => '无法修改已删除的管理员。', 
		'hasAdminsAttached' => '无法删除已关联管理员的群组。'
	]
];
